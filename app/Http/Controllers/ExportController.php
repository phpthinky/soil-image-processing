<?php

namespace App\Http\Controllers;

use App\Models\SoilSample;
use App\Models\Farmer;
use App\Services\FertilizerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __construct(private readonly FertilizerService $fertilizer) {}

    public function export(Request $request): StreamedResponse
    {
        $sampleId = $request->query('sample_id') ? (int)$request->query('sample_id') : 0;
        $user     = Auth::user();

        if ($sampleId > 0) {
            $query = SoilSample::with('user')->where('id', $sampleId);
            if (!$user->isAdmin()) {
                $query->where('user_id', $user->id);
            }
            $samples = $query->get();
            if ($samples->isEmpty()) {
                abort(403, 'Sample not found or access denied.');
            }
            $filename = "soil_sample_{$sampleId}_" . now()->format('Ymd_His') . '.csv';
        } else {
            $query = SoilSample::with('user');
            if (!$user->isAdmin()) {
                $query->where('user_id', $user->id);
            }
            $samples  = $query->latest()->get();
            $filename = 'soil_samples_export_' . now()->format('Ymd_His') . '.csv';
        }

        return response()->streamDownload(function () use ($samples, $sampleId) {
            echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
            $out = fopen('php://output', 'w');

            fputcsv($out, ['=== SOIL ANALYSIS REPORT ===']);
            fputcsv($out, ['Export Date', now()->format('F j, Y g:i A')]);
            fputcsv($out, ['System', 'Soil Fertility Analyzer — Office of the Municipal Agriculturist']);
            fputcsv($out, ['Phase 2 Import Version', '1.0']);
            fputcsv($out, []);

            $headers = [
                'sample_id', 'sample_name', 'farmer_name', 'address', 'farm_location',
                'date_received', 'date_tested', 'analyzed_at', 'submitted_by',
                'ph_level', 'nitrogen_ppm', 'phosphorus_ppm', 'potassium_ppm', 'fertility_score',
                'ph_status', 'nitrogen_status', 'phosphorus_status', 'potassium_status',
                'ph_color_hex', 'nitrogen_color_hex', 'phosphorus_color_hex', 'potassium_color_hex',
                'ph_test1_hex', 'ph_test1_value', 'ph_test2_hex', 'ph_test2_value', 'ph_test3_hex', 'ph_test3_value',
                'n_test1_hex', 'n_test1_value', 'n_test2_hex', 'n_test2_value', 'n_test3_hex', 'n_test3_value',
                'p_test1_hex', 'p_test1_value', 'p_test2_hex', 'p_test2_value', 'p_test3_hex', 'p_test3_value',
                'k_test1_hex', 'k_test1_value', 'k_test2_hex', 'k_test2_value', 'k_test3_hex', 'k_test3_value',
                'fert_lime_tons_per_ha', 'fert_urea_bags_per_ha', 'fert_tsp_bags_per_ha', 'fert_mop_bags_per_ha',
                'recommended_crop', 'ai_recommendation',
            ];
            fputcsv($out, $headers);

            foreach ($samples as $s) {
                $fr = !is_null($s->ph_level)
                    ? $this->fertilizer->recommend((float)$s->ph_level, (float)$s->nitrogen_level, (float)$s->phosphorus_level, (float)$s->potassium_level)
                    : null;

                $rd = [];
                if (!is_null($s->ph_level)) {
                    $rows = DB::table('soil_color_readings')
                        ->where('sample_id', $s->id)
                        ->orderBy('parameter')->orderBy('test_number')
                        ->get();
                    foreach ($rows as $row) {
                        $rd[$row->parameter][$row->test_number] = ['hex' => $row->color_hex, 'value' => $row->computed_value];
                    }
                }
                $rv = fn($param, $num, $field) => $rd[$param][$num][$field] ?? '';

                fputcsv($out, [
                    $s->id, $s->sample_name, $s->farmer_name, $s->address, $s->location ?? '',
                    $s->sample_date, $s->date_tested, $s->analyzed_at ?? '', $s->user->username ?? '',
                    $s->ph_level ?? '', $s->nitrogen_level ?? '', $s->phosphorus_level ?? '', $s->potassium_level ?? '',
                    $s->fertility_score ?? '',
                    !is_null($s->ph_level)         ? $this->fertilizer->getNutrientStatus('ph',         (float)$s->ph_level)         : '',
                    !is_null($s->nitrogen_level)   ? $this->fertilizer->getNutrientStatus('nitrogen',   (float)$s->nitrogen_level)   : '',
                    !is_null($s->phosphorus_level) ? $this->fertilizer->getNutrientStatus('phosphorus', (float)$s->phosphorus_level) : '',
                    !is_null($s->potassium_level)  ? $this->fertilizer->getNutrientStatus('potassium',  (float)$s->potassium_level)  : '',
                    $s->ph_color_hex ?? '', $s->nitrogen_color_hex ?? '', $s->phosphorus_color_hex ?? '', $s->potassium_color_hex ?? '',
                    $rv('ph',1,'hex'), $rv('ph',1,'value'), $rv('ph',2,'hex'), $rv('ph',2,'value'), $rv('ph',3,'hex'), $rv('ph',3,'value'),
                    $rv('nitrogen',1,'hex'), $rv('nitrogen',1,'value'), $rv('nitrogen',2,'hex'), $rv('nitrogen',2,'value'), $rv('nitrogen',3,'hex'), $rv('nitrogen',3,'value'),
                    $rv('phosphorus',1,'hex'), $rv('phosphorus',1,'value'), $rv('phosphorus',2,'hex'), $rv('phosphorus',2,'value'), $rv('phosphorus',3,'hex'), $rv('phosphorus',3,'value'),
                    $rv('potassium',1,'hex'), $rv('potassium',1,'value'), $rv('potassium',2,'hex'), $rv('potassium',2,'value'), $rv('potassium',3,'hex'), $rv('potassium',3,'value'),
                    $fr['lime_tons'] ?? '', $fr['urea_bags'] ?? '', $fr['tsp_bags'] ?? '', $fr['mop_bags'] ?? '',
                    $s->recommended_crop ?? '', $s->ai_recommendation ?? '',
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, ['=== COLUMN LEGEND ===']);
            fputcsv($out, ['Column', 'Description', 'Units / Notes']);
            foreach ([
                ['ph_level', 'Soil acidity/alkalinity', '0-14 scale'],
                ['nitrogen_ppm', 'Available nitrogen (NO3-N)', 'ppm (mg/kg)'],
                ['phosphorus_ppm', 'Available phosphorus (Bray P1)', 'ppm (mg/kg)'],
                ['potassium_ppm', 'Exchangeable potassium', 'ppm (mg/kg)'],
                ['fertility_score', 'Overall fertility index', '0-100 (weighted N35% P25% K25% pH15%)'],
                ['fert_lime_tons_per_ha', 'Dolomitic lime application rate', 'tons/ha'],
                ['fert_urea_bags_per_ha', 'Urea (46-0-0) application rate', '50-kg bags/ha'],
                ['fert_tsp_bags_per_ha', 'Triple Superphosphate (0-46-0) rate', '50-kg bags/ha'],
                ['fert_mop_bags_per_ha', 'Muriate of Potash (0-0-60) rate', '50-kg bags/ha'],
                ['*_color_hex', 'Averaged color from 3 webcam captures', 'CSS hex #RRGGBB'],
            ] as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, [
            'Content-Type'  => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    // ── Phase 2 Export ────────────────────────────────────────────
    // Columns required for Arduino/Phase 2 data matching.
    public function exportPhase2(Request $request): StreamedResponse
    {
        $user    = Auth::user();
        $samples = SoilSample::with(['farmer', 'user'])
            ->where(function ($q) use ($user) {
                if (!$user->isAdmin()) $q->where('user_id', $user->id);
            })
            ->whereNotNull('ph_level')   // analyzed only
            ->latest()
            ->get();

        $filename = 'phase2_export_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($samples) {
            echo "\xEF\xBB\xBF";
            $out = fopen('php://output', 'w');

            fputcsv($out, ['=== PHASE 2 EXPORT — Soil Fertility Analyzer / OMA ===']);
            fputcsv($out, ['Export Date', now()->format('F j, Y g:i A')]);
            fputcsv($out, ['Note', 'Contains analyzed samples only. farm_id matches Arduino device records.']);
            fputcsv($out, []);

            // Required phase 2 columns
            fputcsv($out, [
                'id',            // Arduino / Farm ID (from farmers.farm_id)
                'user_id',       // Farmer record ID (from farmers.id)
                'sample_name',   // Sample identifier
                'location',      // Barangay / farm location
                'sample_date',   // Date tested (YYYY-MM-DD)
                'ph_level',      // pH (0–14)
                'nitrogen_level',
                'phosphorus_level',
                'potassium_level',
                'recommendations',
                // supplementary
                'farmer_name',
                'address',
                'fertility_score',
                'recommended_crop',
                'analyzed_at',
            ]);

            foreach ($samples as $s) {
                $fr  = $this->fertilizer->recommend(
                    (float)$s->ph_level,
                    (float)$s->nitrogen_level,
                    (float)$s->phosphorus_level,
                    (float)$s->potassium_level
                );
                $rec = $this->fertilizer->summary($fr);

                fputcsv($out, [
                    $s->farmer?->farm_id ?? '',     // id — Arduino record ID
                    $s->farmer?->id      ?? '',     // user_id — Farmer system ID
                    $s->sample_name,
                    $s->location ?? $s->farmer?->farm_location ?? '',
                    $s->date_tested?->format('Y-m-d') ?? $s->sample_date->format('Y-m-d'),
                    number_format((float)$s->ph_level, 2),
                    number_format((float)$s->nitrogen_level, 2),
                    number_format((float)$s->phosphorus_level, 2),
                    number_format((float)$s->potassium_level, 2),
                    $rec,
                    $s->farmer_name,
                    $s->address,
                    $s->fertility_score ?? '',
                    $s->recommended_crop ?? '',
                    $s->analyzed_at?->format('Y-m-d H:i:s') ?? '',
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, ['=== COLUMN NOTES ===']);
            fputcsv($out, ['id',             'Arduino/Phase 2 record ID stored in the Farmer profile (farm_id field)']);
            fputcsv($out, ['user_id',        'Internal Farmer ID — use for matching records in Phase 2 database']);
            fputcsv($out, ['sample_date',    'Date the soil test was performed (date_tested field)']);
            fputcsv($out, ['recommendations','Fertilizer recommendation summary (lime/urea/TSP/MOP per hectare)']);
            fclose($out);
        }, $filename, [
            'Content-Type'  => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }
}
