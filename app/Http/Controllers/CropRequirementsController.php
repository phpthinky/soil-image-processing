<?php

namespace App\Http\Controllers;

use App\Models\Crop;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CropRequirementsController extends Controller
{
    public function index()
    {
        $crops = Crop::orderBy('name')->get();



        return view('crops.requirements', compact('crops'));
    }

    public function export(): StreamedResponse
    {
        $crops    = Crop::orderBy('name')->get();
        $filename = 'crop_requirements_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($crops) {
            echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
            $out = fopen('php://output', 'w');

            fputcsv($out, ['=== CROP pH & NPK REQUIREMENTS REFERENCE ===']);
            fputcsv($out, ['Export Date', now()->format('F j, Y g:i A')]);
            fputcsv($out, ['System', 'Soil Fertility Analyzer — Office of the Municipal Agriculturist']);
            fputcsv($out, ['Total Crops', $crops->count()]);
            fputcsv($out, []);

            fputcsv($out, [
                '#', 'Crop Name', 'Description',
                'pH Min', 'pH Med','pH Max',
                'N Min (ppm)', 'N Med (ppm)','N Max (ppm)',
                'P Min (ppm)', 'P Med (ppm)', 'P Max (ppm)',
                'K Min (ppm)', 'K Med (ppm)', 'K Max (ppm)',
            ]);

            foreach ($crops as $i => $crop) {
                fputcsv($out, [
                    $i + 1,
                    $crop->name,
                    $crop->description ?? '',
                    number_format((float) $crop->min_ph, 2),
                    number_format((float) ($crop->min_ph + $crop->max_ph /2), 2),
                    number_format((float) $crop->max_ph, 2),
                    number_format((float) $crop->min_nitrogen, 2),
                    number_format((float) ($crop->min_nitrogen + $crop->max_nitrogen /2), 2),
                    number_format((float) $crop->max_nitrogen, 2),
                    number_format((float) $crop->min_phosphorus, 2),
                    number_format((float) ($crop->min_phosphorus + $crop->max_phosphorus/2), 2),
                    number_format((float) $crop->max_phosphorus, 2),
                    number_format((float) $crop->min_potassium, 2),
                    number_format((float) ($crop->min_potassium + $crop->max_potassium /2), 2),
                    number_format((float) $crop->max_potassium, 2),
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, ['=== COLUMN NOTES ===']);
            fputcsv($out, ['pH',         'Soil acidity/alkalinity on the 0–14 scale']);
            fputcsv($out, ['N (ppm)',    'Available nitrogen (NO3-N) in mg/kg']);
            fputcsv($out, ['P (ppm)',    'Available phosphorus (Bray P1) in mg/kg']);
            fputcsv($out, ['K (ppm)',    'Exchangeable potassium in mg/kg']);
            fputcsv($out, ['Usage',      'Compare soil test results to Min/Max ranges to find matching crops']);

            fclose($out);
        }, $filename, [
            'Content-Type'  => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }
}
