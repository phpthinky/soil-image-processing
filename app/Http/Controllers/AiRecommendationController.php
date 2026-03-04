<?php

namespace App\Http\Controllers;

use App\Models\Crop;
use App\Models\SoilSample;
use App\Services\AiRecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiRecommendationController extends Controller
{
    public function __construct(private readonly AiRecommendationService $aiService) {}

    public function generate(Request $request): JsonResponse
    {
        $request->validate(['sample_id' => 'required|integer|exists:soil_samples,id']);

        $sample = SoilSample::findOrFail($request->sample_id);
        $user   = Auth::user();

        if (!$user->isAdmin() && $sample->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Permission denied'], 403);
        }

        if (!$sample->isAnalyzed()) {
            return response()->json(['success' => false, 'message' => 'Sample has not been analyzed yet'], 422);
        }

        $topCrops    = Crop::selectRaw("name, (
            CASE WHEN ? BETWEEN min_ph AND max_ph THEN 1 ELSE 0 END +
            CASE WHEN ? BETWEEN min_nitrogen AND max_nitrogen THEN 1 ELSE 0 END +
            CASE WHEN ? BETWEEN min_phosphorus AND max_phosphorus THEN 1 ELSE 0 END +
            CASE WHEN ? BETWEEN min_potassium AND max_potassium THEN 1 ELSE 0 END
        ) AS score", [
            $sample->ph_level, $sample->nitrogen_level,
            $sample->phosphorus_level, $sample->potassium_level,
        ])->orderByDesc('score')->orderBy('name')->limit(3)->pluck('name');

        $topCropsStr = $topCrops->isNotEmpty() ? $topCrops->implode(', ') : 'None matched';

        $result = $this->aiService->generate($sample, $topCropsStr);

        return response()->json($result, $result['success'] ? 200 : 500);
    }
}
