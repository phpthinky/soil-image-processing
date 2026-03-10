<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NpkColorChart;
use Illuminate\Http\Request;

class NpkColorChartController extends Controller
{
    public function index()
    {
        $charts = NpkColorChart::orderBy('nutrient')
            ->orderBy('ppm_value')
            ->orderBy('hex_value')
            ->get()
            ->groupBy('nutrient');

        return view('admin.npk-color-charts', compact('charts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nutrient'  => 'required|in:N,P,K',
            'ppm_value' => 'required|numeric|min:0|max:9999',
            'hex_value' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'category'  => 'required|in:low,medium,high',
        ]);

        $hex = strtoupper($request->hex_value);

        if (NpkColorChart::where('nutrient', $request->nutrient)->where('hex_value', $hex)->exists()) {
            return redirect()->route('admin.npk-color-charts')
                ->with('error', 'That hex color already exists for ' . $request->nutrient . '.');
        }

        NpkColorChart::create([
            'nutrient'  => $request->nutrient,
            'ppm_value' => $request->ppm_value,
            'hex_value' => $hex,
            'category'  => $request->category,
            'active'    => true,
        ]);

        return redirect()->route('admin.npk-color-charts')
            ->with('success', 'Color entry added to ' . $request->nutrient . ' chart.');
    }

    public function toggle(NpkColorChart $npkColorChart)
    {
        $npkColorChart->update(['active' => !$npkColorChart->active]);

        $state = $npkColorChart->active ? 'activated' : 'deactivated';

        return redirect()->route('admin.npk-color-charts')
            ->with('success', "Entry {$npkColorChart->hex_value} ({$npkColorChart->nutrient}) {$state}.");
    }

    public function destroy(NpkColorChart $npkColorChart)
    {
        $label = "{$npkColorChart->hex_value} / {$npkColorChart->nutrient} {$npkColorChart->ppm_value} ppm";
        $npkColorChart->delete();

        return redirect()->route('admin.npk-color-charts')
            ->with('success', "Color entry \"{$label}\" deleted permanently.");
    }
}
