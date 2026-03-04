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

class PhTestController extends Controller
{
    public function __construct(
        private readonly ColorScienceService $colorScience,
        private readonly PhTestService       $phTestService,
    ) {}

    // ── Page ─────────────────────────────────────────────────────

    public function show(SoilSample $sample)
    {
        $this->authorize($sample);
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
        ]);

        $sample = SoilSample::findOrFail($validated['sample_id']);
        $this->authorize($sample);

        $colorHex     = strtoupper($validated['color_hex']);
        $step         = (int) $validated['step'];
        $computedPh   = $this->colorScience->colorToPhLevel($colorHex);

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
        ];

        if ($step === 1) {
            if ($phTest->status !== 'step1' && $phTest->status !== 'retest') {
                return response()->json(['success' => false, 'message' => 'Step 1 already complete.'], 422);
            }
            $readings = $phTest->step1_readings ?? [];
            if (count($readings) >= 3) {
                return response()->json(['success' => false, 'message' => 'Step 1 already has 3 captures.'], 422);
            }
            $readings[] = $reading;
            $phTest->step1_readings = $readings;

            if (count($readings) === 3) {
                $values = array_column($readings, 'computed_value');
                $stats  = $this->phTestService->computeStats($values);
                $next   = $this->phTestService->decideSolution($stats['average']);
                $remark = $this->phTestService->generateStep1Remarks(
                    $stats['average'], $next, $stats['confidence']
                );

                $phTest->step1_ph         = $stats['average'];
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
                            'r'              => $rd['r'],
                            'g'              => $rd['g'],
                            'b'              => $rd['b'],
                            'computed_value' => $rd['computed_value'],
                            'captured_at'    => now(),
                        ], ['sample_id', 'parameter', 'test_number'], [
                            'color_hex', 'r', 'g', 'b', 'computed_value', 'captured_at',
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
        $readings[] = $reading;
        $phTest->step2_readings = $readings;

        if (count($readings) === 3) {
            $values = array_column($readings, 'computed_value');
            $stats  = $this->phTestService->computeStats($values);
            $avgHex = $this->phTestService->averageHex($readings);
            $remark = $this->phTestService->generateStep2Remarks(
                (float) $phTest->step1_ph,
                $stats['average'],
                $phTest->step2_solution,
                $stats['confidence']
            );

            $phTest->step2_ph         = $stats['average'];
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
                    'r'              => $rd['r'],
                    'g'              => $rd['g'],
                    'b'              => $rd['b'],
                    'computed_value' => $rd['computed_value'],
                    'captured_at'    => now(),
                ], ['sample_id', 'parameter', 'test_number'], [
                    'color_hex', 'r', 'g', 'b', 'computed_value', 'captured_at',
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
        $this->authorize($sample);
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

    private function authorize(SoilSample $sample): void
    {
        $user = Auth::user();
        if (!$user->isAdmin() && $sample->user_id !== $user->id) {
            abort(403);
        }
    }
}
