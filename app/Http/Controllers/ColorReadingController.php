<?php

namespace App\Http\Controllers;

use App\Models\SoilColorReading;
use App\Models\SoilSample;
use App\Services\ColorScienceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ColorReadingController extends Controller
{
    public function __construct(private readonly ColorScienceService $colorScience) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sample_id'   => 'required|integer|exists:soil_samples,id',
            'parameter'   => 'required|in:ph,nitrogen,phosphorus,potassium',
            'color_hex'   => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'r'           => 'required|integer|min:0|max:255',
            'g'           => 'required|integer|min:0|max:255',
            'b'           => 'required|integer|min:0|max:255',
            'test_number' => 'required|integer|min:1|max:3',
        ]);

        $sample = SoilSample::findOrFail($validated['sample_id']);
        $user   = Auth::user();

        if (!$user->isAdmin() && $sample->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Permission denied'], 403);
        }

        $colorHex      = strtoupper($validated['color_hex']);
        $computedValue = $this->colorScience->computeForParameter($validated['parameter'], $colorHex);

        // Upsert the individual reading
        DB::table('soil_color_readings')->upsert([
            'sample_id'      => $sample->id,
            'parameter'      => $validated['parameter'],
            'test_number'    => $validated['test_number'],
            'color_hex'      => $colorHex,
            'r'              => $validated['r'],
            'g'              => $validated['g'],
            'b'              => $validated['b'],
            'computed_value' => $computedValue,
            'captured_at'    => now(),
        ], ['sample_id', 'parameter', 'test_number'], [
            'color_hex', 'r', 'g', 'b', 'computed_value', 'captured_at',
        ]);

        // Count & average readings for this parameter
        $agg = DB::table('soil_color_readings')
            ->where('sample_id', $sample->id)
            ->where('parameter', $validated['parameter'])
            ->selectRaw('COUNT(*) as cnt, AVG(r) as avg_r, AVG(g) as avg_g, AVG(b) as avg_b')
            ->first();

        $testsDone = (int) $agg->cnt;
        $avgHex    = null;

        if ($testsDone === 3) {
            $avgR   = (int) round($agg->avg_r);
            $avgG   = (int) round($agg->avg_g);
            $avgB   = (int) round($agg->avg_b);
            $avgHex = sprintf('#%02X%02X%02X', $avgR, $avgG, $avgB);
            $col    = $validated['parameter'] . '_color_hex';
            $sample->update([$col => $avgHex]);
        }

        $totalReadings = DB::table('soil_color_readings')
            ->where('sample_id', $sample->id)
            ->count();

        $sample->update(['tests_completed' => $totalReadings]);

        return response()->json([
            'success'        => true,
            'message'        => ucfirst($validated['parameter']) . " test {$validated['test_number']} saved",
            'parameter'      => $validated['parameter'],
            'test_number'    => $validated['test_number'],
            'tests_done'     => $testsDone,
            'computed_value' => $computedValue,
            'avg_hex'        => $avgHex,
            'total_readings' => $totalReadings,
        ]);
    }
}
