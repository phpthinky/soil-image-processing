<?php

namespace App\Http\Controllers;

use App\Models\SoilSample;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ParameterTestController extends Controller
{
    private const PARAMS = [
        'nitrogen' => [
            'label'    => 'Nitrogen (N)',
            'unit'     => 'ppm',
            'icon'     => 'fa-leaf',
            'color'    => 'success',
            'reagent'  => 'Nitrogen Reagent (N-Reagent)',
            'next'     => 'phosphorus',
            'prev'     => null,
            'low_max'  => 45.0,
            'high_min' => 160.0,
        ],
        'phosphorus' => [
            'label'    => 'Phosphorus (P)',
            'unit'     => 'ppm',
            'icon'     => 'fa-atom',
            'color'    => 'primary',
            'reagent'  => 'Phosphorus Reagent (P-Reagent)',
            'next'     => 'potassium',
            'prev'     => 'nitrogen',
            'low_max'  => 15.0,
            'high_min' => 30.0,
        ],
        'potassium' => [
            'label'    => 'Potassium (K)',
            'unit'     => 'ppm',
            'icon'     => 'fa-seedling',
            'color'    => 'info',
            'reagent'  => 'Potassium Reagent (K-Reagent)',
            'next'     => null,
            'prev'     => 'phosphorus',
            'low_max'  => 20.0,
            'high_min' => 40.0,
        ],
    ];

    public function show(SoilSample $sample, string $parameter)
    {
        if (!isset(self::PARAMS[$parameter])) abort(404);

        $user = Auth::user();
        if (!$user->isAdmin() && $sample->user_id !== $user->id) {
            abort(403);
        }

        $meta = self::PARAMS[$parameter];

        $readings = DB::table('soil_color_readings')
            ->where('sample_id', $sample->id)
            ->where('parameter', $parameter)
            ->orderBy('test_number')
            ->get()
            ->keyBy('test_number');

        $captureCount = $readings->count();
        $avgHex       = $sample->{$parameter . '_color_hex'};

        return view('parameter-test.show', compact(
            'sample', 'parameter', 'meta', 'readings', 'captureCount', 'avgHex'
        ));
    }
}
