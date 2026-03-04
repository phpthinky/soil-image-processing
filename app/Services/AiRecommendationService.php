<?php

namespace App\Services;

use App\Models\SoilSample;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiRecommendationService
{
    public function __construct(
        private readonly FertilizerService $fertilizerService
    ) {}

    public function generate(SoilSample $sample, string $topCropsStr): array
    {
        $apiKey = env('ANTHROPIC_API_KEY', '');
        if (empty($apiKey)) {
            return ['success' => false, 'message' => 'AI service is not configured. Set the ANTHROPIC_API_KEY environment variable.'];
        }

        $fertRec     = $this->fertilizerService->recommend(
            (float)$sample->ph_level,
            (float)$sample->nitrogen_level,
            (float)$sample->phosphorus_level,
            (float)$sample->potassium_level
        );
        $fertSummary = $this->fertilizerService->summary($fertRec);

        $phStatus = $this->fertilizerService->getNutrientStatus('ph',         (float)$sample->ph_level);
        $nStatus  = $this->fertilizerService->getNutrientStatus('nitrogen',   (float)$sample->nitrogen_level);
        $pStatus  = $this->fertilizerService->getNutrientStatus('phosphorus', (float)$sample->phosphorus_level);
        $kStatus  = $this->fertilizerService->getNutrientStatus('potassium',  (float)$sample->potassium_level);

        $location = $sample->address . ($sample->location ? ', ' . $sample->location : '');

        $prompt = <<<PROMPT
You are an expert agronomist advising Filipino farmers through the Office of the Municipal Agriculturist (OMA).
Provide practical, actionable advice based on the soil test results below.
Write in clear, plain English suitable for farmers. Keep the total response under 400 words.
Structure your advice in 3 sections:
1. SOIL HEALTH ASSESSMENT — brief interpretation of pH and NPK status
2. FERTILIZER APPLICATION PLAN — confirm/expand the automated recommendation with practical timing and application tips
3. CROP & PLANTING ADVICE — specific tips for the top recommended crops in Philippine conditions

SOIL TEST RESULTS:
- Farmer: {$sample->farmer_name}
- Location: {$location}
- pH Level: {$sample->ph_level} ({$phStatus})
- Nitrogen (N): {$sample->nitrogen_level} ppm ({$nStatus})
- Phosphorus (P): {$sample->phosphorus_level} ppm ({$pStatus})
- Potassium (K): {$sample->potassium_level} ppm ({$kStatus})
- Fertility Score: {$sample->fertility_score}%
- Recommended Crops (top matches): {$topCropsStr}

AUTOMATED FERTILIZER RECOMMENDATION (per hectare):
{$fertSummary}

Respond only with the three-section advice. Do not repeat the input data.
PROMPT;

        try {
            $response = Http::withHeaders([
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type'      => 'application/json',
            ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                'model'      => 'claude-haiku-4-5-20251001',
                'max_tokens' => 1024,
                'messages'   => [['role' => 'user', 'content' => $prompt]],
            ]);

            if ($response->failed()) {
                $err = $response->json('error.message') ?? "HTTP {$response->status()}";
                Log::error("AI recommendation API error: $err");
                return ['success' => false, 'message' => "AI API error: $err"];
            }

            $text = trim($response->json('content.0.text') ?? '');
            if (empty($text)) {
                return ['success' => false, 'message' => 'Empty response from AI service'];
            }

            $sample->update(['ai_recommendation' => $text]);

            return ['success' => true, 'recommendation' => $text];

        } catch (\Exception $e) {
            Log::error("AI recommendation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to connect to AI service'];
        }
    }
}
