<?php

namespace App\Http\Controllers;

use App\Models\SoilSample;
use App\Services\GeminiCropRecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GeminiCropRecommendationController extends Controller
{
    public function __construct(
        private readonly GeminiCropRecommendationService $geminiService
    ) {}

    /**
     * Generate Gemini AI crop recommendations for a soil sample.
     *
     * Accepts an optional preferred_crop field so farmers can ask whether
     * a specific crop they want to grow is compatible with their soil.
     *
     * The GEMINI_API_KEY is read server-side and is never returned in any response.
     */
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'sample_id'      => 'required|integer|exists:soil_samples,id',
            'preferred_crop' => 'nullable|string|max:100',
        ]);

        $sample = SoilSample::findOrFail($request->sample_id);
        $user   = Auth::user();

        if (!$user->isAdmin() && $sample->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Permission denied.'], 403);
        }

        if (!$sample->isAnalyzed()) {
            return response()->json([
                'success' => false,
                'message' => 'Sample has not been fully analyzed yet. Complete all 12 captures first.',
            ], 422);
        }

        $preferredCrop = filled($request->preferred_crop) ? strip_tags(trim($request->preferred_crop)) : null;

        $result = $this->geminiService->generate($sample, $preferredCrop);

        return response()->json($result, $result['success'] ? 200 : 500);
    }
}
