<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PhColorChart;
use Illuminate\Http\Request;

class PhColorChartController extends Controller
{
    public function index()
    {
        $charts = PhColorChart::orderBy('indicator')
            ->orderBy('ph_value')
            ->orderBy('hex_value')
            ->get()
            ->groupBy('indicator');

        return view('admin.ph-color-charts', compact('charts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'indicator' => 'required|in:CPR,BCG,BTB',
            'ph_value'  => 'required|numeric|min:0|max:14',
            'hex_value' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $hex = strtoupper($request->hex_value);

        if (PhColorChart::where('indicator', $request->indicator)->where('hex_value', $hex)->exists()) {
            return redirect()->route('admin.ph-color-charts')
                ->with('error', 'That hex color already exists for ' . $request->indicator . '.');
        }

        PhColorChart::create([
            'indicator' => $request->indicator,
            'ph_value'  => $request->ph_value,
            'hex_value' => $hex,
            'active'    => true,
        ]);

        return redirect()->route('admin.ph-color-charts')
            ->with('success', 'Color entry added to ' . $request->indicator . ' chart.');
    }

    public function toggle(PhColorChart $phColorChart)
    {
        $phColorChart->update(['active' => !$phColorChart->active]);

        $state = $phColorChart->active ? 'activated' : 'deactivated';

        return redirect()->route('admin.ph-color-charts')
            ->with('success', "Entry {$phColorChart->hex_value} ({$phColorChart->indicator}) {$state}.");
    }

    public function destroy(PhColorChart $phColorChart)
    {
        $label = "{$phColorChart->hex_value} / {$phColorChart->indicator} pH {$phColorChart->ph_value}";
        $phColorChart->delete();

        return redirect()->route('admin.ph-color-charts')
            ->with('success', "Color entry \"{$label}\" deleted permanently.");
    }
}
