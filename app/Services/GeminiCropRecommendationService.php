<?php

namespace App\Services;

use App\Models\SoilSample;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Gemini AI Crop Recommendation Service
 *
 * Calls the Google Gemini API server-side — the API key is NEVER exposed
 * to the frontend. The browser only receives the generated text recommendation.
 */
class GeminiCropRecommendationService
{
    public function __construct(
        private readonly FertilizerService $fertilizerService
    ) {}

    /**
     * Generate Gemini AI crop recommendations for a soil sample.
     *
     * @param  SoilSample   $sample          Analyzed soil sample record
     * @param  string|null  $preferredCrop   Optional crop the farmer wants to prioritize
     * @return array{success: bool, recommendation?: string, message?: string}
     */
    public function generate(SoilSample $sample, ?string $preferredCrop = null): array
    {
        $apiKey = config('services.gemini.api_key', '');
        if (empty($apiKey)) {
            return [
                'success' => false,
                'message' => 'Gemini AI service is not configured. Set the GEMINI_API_KEY environment variable.',
            ];
        }

        $model = config('services.gemini.model', 'gemini-2.0-flash');

        $fertRec     = $this->fertilizerService->recommend(
            (float) $sample->ph_level,
            (float) $sample->nitrogen_level,
            (float) $sample->phosphorus_level,
            (float) $sample->potassium_level
        );
        $fertSummary = $this->fertilizerService->summary($fertRec);

        $phStatus = $this->fertilizerService->getNutrientStatus('ph',         (float) $sample->ph_level);
        $nStatus  = $this->fertilizerService->getNutrientStatus('nitrogen',   (float) $sample->nitrogen_level);
        $pStatus  = $this->fertilizerService->getNutrientStatus('phosphorus', (float) $sample->phosphorus_level);
        $kStatus  = $this->fertilizerService->getNutrientStatus('potassium',  (float) $sample->potassium_level);

        $location = trim($sample->address . ($sample->location ? ', ' . $sample->location : ''));

        $preferredSection = $preferredCrop
            ? "\nFARMER'S PREFERRED CROP: {$preferredCrop} — analyze whether this crop is suitable for the soil and if not, provide amendment steps so the farmer can still grow it.\n"
            : '';

        $prompt = <<<PROMPT
IMPORTANT: You must respond entirely in English. Do not use Filipino, Tagalog, or any other language.

You are a senior agronomist advising the Office of the Municipal Agriculturist (OMA) in the Philippines.
Based on the soil analysis below, recommend suitable Philippine crops and their fertilizer requirements.

SOIL TEST RESULTS:
- Farmer: {$sample->farmer_name}
- Location: {$location}
- pH Level: {$sample->ph_level} ({$phStatus})
- Nitrogen (N): {$sample->nitrogen_level} ppm ({$nStatus})
- Phosphorus (P): {$sample->phosphorus_level} ppm ({$pStatus})
- Potassium (K): {$sample->potassium_level} ppm ({$kStatus})
- Fertility Score: {$sample->fertility_score}%
{$preferredSection}
BASELINE FERTILIZER (per hectare, from automated system):
{$fertSummary}

TASK: List the TOP 10 most suitable Philippine crops for this soil, ranked from best match to least. No grouping required.

For EACH crop provide:
1. Crop name (Philippine common name)
2. Soil compatibility note (1 sentence based on the test results)
3. Fertilizer adjustment (per hectare): Urea bags, TSP bags, MOP bags, and Lime if needed — adjusted from the baseline for this specific crop's NPK demands
4. Application tip (1 sentence: timing or method suited to Philippine conditions)

FORMAT your response as a numbered list. Use this exact format for each crop:

CROP [number]: [Crop Name]
Soil Compatibility: [sentence]
Fertilizer (per ha): Lime [x] t/ha | Urea [x] bags | TSP [x] bags | MOP [x] bags
Application Tip: [sentence]

After the crop list, add a brief SUMMARY (3–4 sentences) with overall planting advice for this soil profile in the Philippine context.

Do not repeat the raw soil numbers. Focus on practical guidance for smallholder farmers. Respond in English only.
PROMPT;

        try {
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->timeout(45)->post("{$endpoint}?key={$apiKey}", [
                'contents' => [
                    [
                        'parts' => [['text' => $prompt]],
                    ],
                ],
                'generationConfig' => [
                    'temperature'     => 0.4,
                    'maxOutputTokens' => 8192,
                ],
            ]);

            if ($response->failed()) {
                $err = $response->json('error.message') ?? "HTTP {$response->status()}";
                Log::error("Gemini crop recommendation API error: {$err}");
                return ['success' => false, 'message' => "Gemini API error: {$err}"];
            }

            $text       = trim($response->json('candidates.0.content.parts.0.text') ?? '');
            $finishReason = $response->json('candidates.0.finishReason') ?? 'UNKNOWN';

            if (empty($text)) {
                Log::warning("Gemini returned empty text. finishReason={$finishReason}", [
                    'raw' => $response->json(),
                ]);
                return ['success' => false, 'message' => "Gemini returned no content (finishReason: {$finishReason})."];
            }

            if ($finishReason === 'MAX_TOKENS') {
                Log::warning('Gemini response was cut off (MAX_TOKENS). Increase maxOutputTokens.');
            }

            $sample->update(['gemini_crop_recommendation' => $text]);

            return ['success' => true, 'recommendation' => $text];

        } catch (\Exception $e) {
            Log::error('Gemini crop recommendation error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to connect to Gemini AI service.'];
        }
    }
}
