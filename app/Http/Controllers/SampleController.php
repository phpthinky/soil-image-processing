<?php

namespace App\Http\Controllers;

use App\Models\Crop;
use App\Models\Farmer;
use App\Models\SoilSample;
use App\Services\ColorScienceService;
use App\Services\FertilizerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SampleController extends Controller
{
    public function __construct(
        private readonly ColorScienceService $colorScience,
        private readonly FertilizerService   $fertilizer
    ) {}

    // List all samples
    public function index()
    {
        $user = Auth::user();
        $samples = $user->isAdmin()
            ? SoilSample::with('user')->latest()->get()
            : $user->soilSamples()->latest()->get();

        return view('samples.index', compact('samples'));
    }

    // Show create form
    public function create()
    {
         if (!Auth::user()->isAdmin())
            {
                $limit = 5;
                $samples = Auth::user()->soilSamples()->count();
                if($samples >= $limit) {
                    return redirect()->route('samples.index')
                ->with('error', 'Maximum limit of '.$limit.' samples reached. Please settle the unpaid modification fee to continue using the system.');
                }
            }
        // ── END SAMPLE LIMIT ──────────────────────────────────────────────────────────────────

        $user = Auth::user();
        $farmers = $user->isAdmin()
            ? Farmer::orderBy('name')->get()
            : $user->farmers()->orderBy('name')->get();

        return view('samples.create', compact('farmers'));
    }

    // Store new sample
    public function store(Request $request)
    {
        // ── SAMPLE LIMIT ── comment out the block below once the modification fee is settled ──
        if (!Auth::user()->isAdmin())
            {
                $limit = 5;
                $samples = Auth::user()->soilSamples()->count();
                if($samples >= $limit) {
                    return redirect()->route('samples.index')
                ->with('error', 'Maximum limit of '.$limit.' samples reached. Please settle the unpaid modification fee to continue using the system.');
                }
            }
        // ── END SAMPLE LIMIT ──────────────────────────────────────────────────────────────────

        $request->validate([
            'sample_name' => 'required|string|max:150',
            'farmer_id'   => 'nullable|integer|exists:farmers,id',
            'farmer_name' => 'required|string|max:150',
            'address'     => 'required|string|max:255',
            'sample_date' => 'required|date|before_or_equal:today',
            'date_tested' => 'required|date|before_or_equal:today|after_or_equal:sample_date',
            'location'    => 'nullable|string|max:200',
        ]);

        $sample = Auth::user()->soilSamples()->create([
            'farmer_id'   => $request->farmer_id ?: null,
            'sample_name' => $request->sample_name,
            'farmer_name' => $request->farmer_name,
            'address'     => $request->address,
            'sample_date' => $request->sample_date,
            'date_tested' => $request->date_tested,
            'location'    => $request->location,
            'color_hex'   => '#8B4513',
        ]);

        return redirect()->route('samples.show', $sample)
            ->with('success', 'Soil sample added successfully! Ready for webcam analysis.');
    }

    // Show sample detail (also handles the auto-compute trigger)
    public function show(SoilSample $sample)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && $sample->user_id !== $user->id) {
            abort(403);
        }

        // Auto-compute when all 4 averaged colors are present and not yet analyzed
        if ($sample->allAveraged() && !$sample->isAnalyzed()) {
            $ph = $this->colorScience->colorToPhLevel($sample->ph_color_hex);
            $n  = $this->colorScience->colorToNitrogenLevel($sample->nitrogen_color_hex);
            $p  = $this->colorScience->colorToPhosphorusLevel($sample->phosphorus_color_hex);
            $k  = $this->colorScience->colorToPotassiumLevel($sample->potassium_color_hex);
            $fs = $this->fertilizer->computeFertilityScore($ph, $n, $p, $k);

            $sample->update([
                'ph_level'         => $ph,
                'nitrogen_level'   => $n,
                'phosphorus_level' => $p,
                'potassium_level'  => $k,
                'fertility_score'  => $fs,
                'recommended_crop' => Crop::topMatchName($ph, $n, $p, $k),
                'analyzed_at'      => now(),
            ]);

            return redirect()->route('samples.show', $sample);
        }

        $readings        = $sample->getReadingsByParameter();
        $cropsByTolerance = [];
        $cropsByFertility = [];
        $cropsByPh        = [];
        $fertRec          = [];

        if ($sample->isAnalyzed()) {
            $ph = (float)$sample->ph_level;
            $n  = (float)$sample->nitrogen_level;
            $p  = (float)$sample->phosphorus_level;
            $k  = (float)$sample->potassium_level;

            $cropsByTolerance = Crop::groupByTolerance($ph, $n, $p, $k);
            $cropsByFertility = Crop::groupByFertility($n, $p, $k);
            $cropsByPh        = Crop::groupByPh($ph, $n, $p, $k);
            $fertRec          = $this->fertilizer->recommend($ph, $n, $p, $k);
        }

        $aiEnabled = !empty(env('ANTHROPIC_API_KEY'));
        $allCrops  = Crop::orderBy('name')->get();

        return view('samples.show', compact(
            'sample', 'readings',
            'cropsByTolerance', 'cropsByFertility', 'cropsByPh',
            'fertRec', 'aiEnabled', 'allCrops'
        ));
    }

    // Show individual test readings report
    public function report(SoilSample $sample)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && $sample->user_id !== $user->id) {
            abort(403);
        }

        $readings = $sample->getReadingsByParameter();
        $phTest   = $sample->phTest;

        return view('samples.report', compact('sample', 'readings', 'phTest'));
    }

    // Print-friendly PDF view (browser print-to-PDF)
    public function pdf(SoilSample $sample)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && $sample->user_id !== $user->id) {
            abort(403);
        }

        $phTest           = $sample->phTest;
        $cropsByTolerance = [];
        $cropsByFertility = [];
        $cropsByPh        = [];
        $fertRec          = [];

        if ($sample->isAnalyzed()) {
            $ph = (float)$sample->ph_level;
            $n  = (float)$sample->nitrogen_level;
            $p  = (float)$sample->phosphorus_level;
            $k  = (float)$sample->potassium_level;

            $cropsByTolerance = Crop::groupByTolerance($ph, $n, $p, $k);
            $cropsByFertility = Crop::groupByFertility($n, $p, $k);
            $cropsByPh        = Crop::groupByPh($ph, $n, $p, $k);
            $fertRec          = $this->fertilizer->recommend($ph, $n, $p, $k);
        }

        return view('samples.pdf', compact(
            'sample', 'phTest',
            'cropsByTolerance', 'cropsByFertility', 'cropsByPh',
            'fertRec'
        ));
    }

    // Reset all readings for re-capture
    public function reset(SoilSample $sample)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && $sample->user_id !== $user->id) {
            abort(403);
        }

        $sample->colorReadings()->delete();
        $sample->update([
            'ph_color_hex'         => null,
            'nitrogen_color_hex'   => null,
            'phosphorus_color_hex' => null,
            'potassium_color_hex'  => null,
            'ph_level'             => null,
            'nitrogen_level'       => null,
            'phosphorus_level'     => null,
            'potassium_level'      => null,
            'fertility_score'      => null,
            'analyzed_at'          => null,
            'ai_recommendation'    => null,
            'recommended_crop'     => null,
            'tests_completed'      => 0,
        ]);

        return redirect()->route('samples.show', $sample)
            ->with('success', 'All readings have been reset. You can re-capture now.');
    }
}
