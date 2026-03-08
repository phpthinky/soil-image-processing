<?php

namespace App\Http\Controllers;

use App\Models\PhTest;
use App\Models\SoilSample;
use App\Services\ColorScienceService;
use App\Services\PhTestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PhTestController extends Controller
{
    public function __construct(
        private readonly ColorScienceService $colorScience,
        private readonly PhTestService       $phTestService,
    ) {}

    // ── Page ─────────────────────────────────────────────────────

    public function show(SoilSample $sample)
    {
        $this->authorizeAccess($sample);
        $phTest = $sample->phTest ?? new PhTest(['status' => 'step1']);
        return view('ph-test.show', compact('sample', 'phTest'));
    }

    // ── Capture API (POST /api/ph-test/capture) ───────────────────

    public function capture(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sample_id' => 'required|integer|exists:soil_samples,id',
            'step'      => 'required|integer|in:1,2',
            'color_hex' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'r'         => 'required|integer|min:0|max:255',
            'g'         => 'required|integer|min:0|max:255',
            'b'         => 'required|integer|min:0|max:255',
            'snapshot'  => 'nullable|string',  // base64 data-URL from canvas
        ]);

        $sample = SoilSample::findOrFail($validated['sample_id']);
        $this->authorizeAccess($sample);

        $colorHex = strtoupper($validated['color_hex']);
        $step     = (int) $validated['step'];

        // Determine which indicator is in use so we apply its specific color chart.
        // CPR is always used in Step 1; BCG or BTB is assigned after Step 1 completes.
        $indicatorSolution = $step === 1
            ? 'CPR'
            : (($phTest->step2_solution ?? null) ?: 'CPR');

        // Use the indicator-specific chart (CPR/BCG/BTB) instead of the generic
        // PH_COLOR_CHART to avoid the systematic ~0.6 pH overestimate caused by
        // each reagent producing different hues at the same pH value.
        $phResult      = $this->colorScience->phTestColorToPhLevel($colorHex, $indicatorSolution);
        $computedPh    = $phResult['ph'];
        $confidencePct = $phResult['confidence_pct'];

        $phTest = $sample->phTest ?? PhTest::create([
            'sample_id' => $sample->id,
            'status'    => 'step1',
        ]);

        $reading = [
            'hex'            => $colorHex,
            'r'              => $validated['r'],
            'g'              => $validated['g'],
            'b'              => $validated['b'],
            'computed_value' => $computedPh,
            'confidence_pct' => $confidencePct,
            'image'          => null,  // filled in below once we know the test number
        ];

        if ($step === 1) {
            if ($phTest->status !== 'step1' && $phTest->status !== 'retest') {
                return response()->json(['success' => false, 'message' => 'Step 1 already complete.'], 422);
            }
            $readings = $phTest->step1_readings ?? [];
            if (count($readings) >= 3) {
                return response()->json(['success' => false, 'message' => 'Step 1 already has 3 captures.'], 422);
            }
            $testNumber = count($readings) + 1;
            $reading['image'] = $this->saveSnapshot(
                $validated['snapshot'] ?? null, $sample->id, 'ph-step1', $testNumber
            );
            $reading['chart_ph'] = PhTestService::snapToChartPh($computedPh, 'CPR');
            $readings[] = $reading;
            $phTest->step1_readings = $readings;

            if (count($readings) === 3) {
                // Use chart_ph (already snapped to discrete CPR card values) for the
                // average so step1_ph reflects what the physical test card shows,
                // not the raw interpolated value which can carry a calibration offset.
                $values = array_column($readings, 'chart_ph');
                $stats  = $this->phTestService->computeStats($values);
                $next   = $this->phTestService->decideSolution($stats['average']);
                $remark = $this->phTestService->generateStep1Remarks(
                    $stats['average'], $next, $stats['confidence']
                );

                $phTest->step1_ph         = $stats['average'];
                $phTest->step1_chart_ph   = PhTestService::snapToChartPh($stats['average'], 'CPR');
                $phTest->step1_variance   = $stats['variance'];
                $phTest->step1_confidence = $stats['confidence'];
                $phTest->step1_outcome    = $remark['outcome'];
                $phTest->step1_remarks    = $remark['remarks'];
                $phTest->next_solution    = $next;

                if ($next === 'RETEST') {
                    $phTest->status = 'retest';
                } elseif ($next === 'CPR') {
                    // pH in 5.4–5.8 range: CPR result is final per BSWM protocol
                    $phTest->final_ph = $stats['average'];
                    $phTest->status   = 'complete';
                    $avgHex = $this->phTestService->averageHex($readings);

                    foreach ($readings as $i => $rd) {
                        DB::table('soil_color_readings')->upsert([
                            'sample_id'      => $sample->id,
                            'parameter'      => 'ph',
                            'test_number'    => $i + 1,
                            'color_hex'      => $rd['hex'],
                            'captured_image' => $rd['image'] ?? null,
                            'r'              => $rd['r'],
                            'g'              => $rd['g'],
                            'b'              => $rd['b'],
                            'computed_value' => $rd['computed_value'],
                            'captured_at'    => now(),
                        ], ['sample_id', 'parameter', 'test_number'], [
                            'color_hex', 'captured_image', 'r', 'g', 'b', 'computed_value', 'captured_at',
                        ]);
                    }
                    $sample->update([
                        'ph_color_hex'    => $avgHex,
                        'tests_completed' => DB::table('soil_color_readings')
                            ->where('sample_id', $sample->id)->count(),
                    ]);
                } else {
                    $phTest->status         = 'step2';
                    $phTest->step2_solution = $next;
                }
            }

            $phTest->save();

            return response()->json([
                'success'       => true,
                'step'          => 1,
                'count'         => count($readings),
                'computed_value'=> $computedPh,
                'step1_ph'      => $phTest->step1_ph,
                'next_solution' => $phTest->next_solution,
                'status'        => $phTest->status,
                'reload'        => true,
            ]);
        }

        // Step 2
        if ($phTest->status !== 'step2') {
            return response()->json(['success' => false, 'message' => 'Complete Step 1 first.'], 422);
        }
        $readings = $phTest->step2_readings ?? [];
        if (count($readings) >= 3) {
            return response()->json(['success' => false, 'message' => 'Step 2 already has 3 captures.'], 422);
        }
        $testNumber = count($readings) + 1;
        $reading['image']    = $this->saveSnapshot(
            $validated['snapshot'] ?? null, $sample->id, 'ph-step2', $testNumber
        );
        $reading['chart_ph'] = PhTestService::snapToChartPh($computedPh, $phTest->step2_solution);
        $readings[] = $reading;
        $phTest->step2_readings = $readings;

        if (count($readings) === 3) {
            // Use chart_ph (snapped to BCG/BTB card values) for consistency with Step 1.
            $values = array_column($readings, 'chart_ph');
            $stats  = $this->phTestService->computeStats($values);
            $avgHex = $this->phTestService->averageHex($readings);
            $remark = $this->phTestService->generateStep2Remarks(
                (float) $phTest->step1_ph,
                $stats['average'],
                $phTest->step2_solution,
                $stats['confidence']
            );

            $phTest->step2_ph         = $stats['average'];
            $phTest->step2_chart_ph   = PhTestService::snapToChartPh($stats['average'], $phTest->step2_solution);
            $phTest->step2_variance   = $stats['variance'];
            $phTest->step2_confidence = $stats['confidence'];
            $phTest->step2_outcome    = $remark['outcome'];
            $phTest->step2_remarks    = $remark['remarks'];
            $phTest->final_ph         = $stats['average'];
            $phTest->status           = 'complete';

            // Persist to soil_color_readings (3 rows, test_number 1/2/3) so the
            // existing analysis pipeline and report page still work for pH.
            foreach ($readings as $i => $rd) {
                DB::table('soil_color_readings')->upsert([
                    'sample_id'      => $sample->id,
                    'parameter'      => 'ph',
                    'test_number'    => $i + 1,
                    'color_hex'      => $rd['hex'],
                    'captured_image' => $rd['image'] ?? null,
                    'r'              => $rd['r'],
                    'g'              => $rd['g'],
                    'b'              => $rd['b'],
                    'computed_value' => $rd['computed_value'],
                    'captured_at'    => now(),
                ], ['sample_id', 'parameter', 'test_number'], [
                    'color_hex', 'captured_image', 'r', 'g', 'b', 'computed_value', 'captured_at',
                ]);
            }

            // Save averaged pH hex to soil_samples
            $sample->update([
                'ph_color_hex'   => $avgHex,
                'tests_completed' => DB::table('soil_color_readings')
                    ->where('sample_id', $sample->id)->count(),
            ]);
        }

        $phTest->save();

        return response()->json([
            'success'        => true,
            'step'           => 2,
            'count'          => count($readings),
            'computed_value' => $computedPh,
            'step2_ph'       => $phTest->step2_ph,
            'final_ph'       => $phTest->final_ph,
            'status'         => $phTest->status,
            'reload'         => true,
        ]);
    }

    // ── Reset ─────────────────────────────────────────────────────

    public function reset(SoilSample $sample)
    {
        $this->authorizeAccess($sample);
        $sample->phTest?->delete();

        // Clear pH readings from soil_color_readings
        DB::table('soil_color_readings')
            ->where('sample_id', $sample->id)
            ->where('parameter', 'ph')
            ->delete();

        $sample->update([
            'ph_color_hex'   => null,
            'tests_completed' => DB::table('soil_color_readings')
                ->where('sample_id', $sample->id)->count(),
        ]);

        return redirect()->route('ph-test.show', $sample)
            ->with('success', 'pH test reset. You can start over.');
    }

    // ─────────────────────────────────────────────────────────────

    private function authorizeAccess(SoilSample $sample): void
    {
        $user = Auth::user();
        if (!$user->isAdmin() && $sample->user_id !== $user->id) {
            abort(403);
        }
    }

    /**
     * Decode a base64 canvas data-URL and store it as a JPEG under
     * storage/app/public/captures/{sampleId}/{param}-{testNumber}.jpg.
     * Returns the public-disk-relative path, or null on failure.
     */
    private function saveSnapshot(?string $dataUrl, int $sampleId, string $param, int $testNumber): ?string
    {
        if (!$dataUrl || !str_contains($dataUrl, ',')) {
            return null;
        }
        $base64 = substr($dataUrl, strpos($dataUrl, ',') + 1);
        $image  = base64_decode($base64, strict: true);
        if ($image === false) {
            return null;
        }
        // Write directly into public/ — works on shared hosts (e.g. Hostinger) without needing a storage symlink.
        $dir  = public_path("captures/{$sampleId}");
        $file = "{$param}-{$testNumber}.jpg";
        if (!is_dir($dir)) {
            mkdir($dir, 0755, recursive: true);
        }
        file_put_contents("{$dir}/{$file}", $image);

        return "captures/{$sampleId}/{$file}";
    }
}
