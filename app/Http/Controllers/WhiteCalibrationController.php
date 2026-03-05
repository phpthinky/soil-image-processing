<?php

namespace App\Http\Controllers;

use App\Models\SoilSample;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WhiteCalibrationController extends Controller
{
    /**
     * Store a white balance reference capture for a soil sample.
     *
     * The caller places a white card in the capture box and submits the
     * averaged RGB of the centre region.  We persist the three channel
     * values so every subsequent colour reading on this sample can be
     * corrected with:
     *
     *   corrected_R = min(255, raw_R * (255 / white_ref_r))
     *   corrected_G = min(255, raw_G * (255 / white_ref_g))
     *   corrected_B = min(255, raw_B * (255 / white_ref_b))
     */
    public function store(Request $request, SoilSample $sample): JsonResponse
    {
        $user = Auth::user();
        if (!$user->isAdmin() && $sample->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Permission denied'], 403);
        }

        $validated = $request->validate([
            'r' => 'required|integer|min:1|max:255',
            'g' => 'required|integer|min:1|max:255',
            'b' => 'required|integer|min:1|max:255',
        ]);

        $sample->update([
            'white_ref_r' => $validated['r'],
            'white_ref_g' => $validated['g'],
            'white_ref_b' => $validated['b'],
        ]);

        return response()->json([
            'success'   => true,
            'white_ref' => ['r' => $validated['r'], 'g' => $validated['g'], 'b' => $validated['b']],
        ]);
    }
}
