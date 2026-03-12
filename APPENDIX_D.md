# APPENDIX D
# SOURCE CODE DOCUMENTATION
**Soil Fertility Analyzer — Webcam-Based Colorimetric Soil Testing System**
Office of the Municipal Agriculturist (OMA)

---

This appendix contains the key source code for the major features of the system:

1. [Authentication System](#1-authentication-system)
2. [Farmer Management](#2-farmer-management)
3. [Soil Sample Management](#3-soil-sample-management)
4. [pH Test — BSWM 2-Step Protocol](#4-ph-test--bswm-2-step-protocol)
5. [NPK Color Capture](#5-npk-color-capture)
6. [Color-to-Value Algorithm (CIEDE2000)](#6-color-to-value-algorithm-ciede2000)
7. [Fertilizer Recommendation Engine](#7-fertilizer-recommendation-engine)
8. [Crop Recommendation Algorithm](#8-crop-recommendation-algorithm)
9. [AI Agronomist Recommendation](#9-ai-agronomist-recommendation)
10. [Data Export](#10-data-export)

---

## 1. Authentication System

The system uses username-based session authentication built on Laravel's `Auth` facade. Upon successful login, the user is redirected based on their role (`admin` → Admin Dashboard, others → User Dashboard). Session tokens are regenerated on login and invalidated on logout to prevent session fixation attacks.

**File:** `app/Http/Controllers/Auth/LoginController.php`

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = [
            'username' => $request->username,
            'password' => $request->password,
        ];

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $user = Auth::user();
            return redirect()->intended(
                $user->isAdmin() ? route('admin.dashboard') : route('dashboard')
            );
        }

        return back()->withErrors(['username' => 'Invalid username or password.'])->withInput();
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
```

**File:** `app/Models/User.php`

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    protected $fillable = ['username', 'email', 'password', 'user_type'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }

    public function isAdmin(): bool
    {
        return $this->user_type === 'admin';
    }

    public function soilSamples(): HasMany
    {
        return $this->hasMany(SoilSample::class);
    }

    public function farmers(): HasMany
    {
        return $this->hasMany(Farmer::class);
    }
}
```

---

## 2. Farmer Management

Farmers are registered by technicians and linked to soil samples. The system supports manual entry and CSV bulk import. A JSON endpoint provides autocomplete data for the sample creation form.

**File:** `app/Http/Controllers/FarmerController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Farmer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FarmerController extends Controller
{
    private function farmerQuery()
    {
        $user = Auth::user();
        return $user->isAdmin()
            ? Farmer::with('user')
            : Farmer::where('user_id', $user->id);
    }

    public function index()
    {
        $farmers = $this->farmerQuery()->latest()->get();
        return view('farmers.index', compact('farmers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:150',
            'address'       => 'required|string|max:255',
            'farm_location' => 'nullable|string|max:200',
            'farm_id'       => 'nullable|string|max:100',
        ]);

        Auth::user()->farmers()->create($data);

        return redirect()->route('farmers.index')
            ->with('success', 'Farmer added successfully.');
    }

    public function update(Request $request, Farmer $farmer)
    {
        $this->authorise($farmer);

        $data = $request->validate([
            'name'          => 'required|string|max:150',
            'address'       => 'required|string|max:255',
            'farm_location' => 'nullable|string|max:200',
            'farm_id'       => 'nullable|string|max:100',
        ]);

        $farmer->update($data);

        return redirect()->route('farmers.index')
            ->with('success', 'Farmer updated successfully.');
    }

    // ── CSV Bulk Import ───────────────────────────────────────────

    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file     = $request->file('csv_file');
        $handle   = fopen($file->getRealPath(), 'r');
        $header   = null;
        $imported = 0;
        $skipped  = 0;
        $errors   = [];
        $userId   = Auth::id();

        while (($row = fgetcsv($handle)) !== false) {
            if (empty(array_filter($row))) continue;

            if ($header === null) {
                $header = array_map(fn($h) => strtolower(trim($h)), $row);
                continue;
            }

            $data = array_combine($header, $row);
            $name = trim($data['name'] ?? '');
            if (empty($name)) { $skipped++; continue; }

            try {
                Farmer::create([
                    'user_id'       => $userId,
                    'name'          => $name,
                    'address'       => trim($data['address'] ?? ''),
                    'farm_location' => trim($data['farm_location'] ?? '') ?: null,
                    'farm_id'       => trim($data['farm_id'] ?? '') ?: null,
                ]);
                $imported++;
            } catch (\Throwable $e) {
                $errors[] = "Row for {$name}: " . $e->getMessage();
            }
        }

        fclose($handle);

        return redirect()->route('farmers.index')
            ->with('success', "Imported {$imported} farmer(s). Skipped {$skipped} empty row(s).")
            ->with('import_errors', $errors);
    }

    // ── JSON for autocomplete ──────────────────────────────────────

    public function json()
    {
        $farmers = $this->farmerQuery()
            ->select('id', 'name', 'address', 'farm_location', 'farm_id')
            ->orderBy('name')
            ->get();

        return response()->json($farmers);
    }

    private function authorise(Farmer $farmer): void
    {
        $user = Auth::user();
        if (!$user->isAdmin() && $farmer->user_id !== $user->id) {
            abort(403);
        }
    }
}
```

---

## 3. Soil Sample Management

The `SampleController` handles the full sample lifecycle: creation, the auto-compute trigger (fires when all 4 averaged colors are present), report generation, and deletion. Ownership is enforced on every action.

**File:** `app/Http/Controllers/SampleController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Crop;
use App\Models\Farmer;
use App\Models\SoilSample;
use App\Services\ColorScienceService;
use App\Services\FertilizerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SampleController extends Controller
{
    public function __construct(
        private readonly ColorScienceService $colorScience,
        private readonly FertilizerService   $fertilizer
    ) {}

    public function index()
    {
        $user    = Auth::user();
        $samples = $user->isAdmin()
            ? SoilSample::with('user')->latest()->get()
            : $user->soilSamples()->latest()->get();

        return view('samples.index', compact('samples'));
    }

    public function store(Request $request)
    {
        // Enforce 5-sample limit for non-admin users
        if (!Auth::user()->isAdmin()) {
            $limit = 5;
            if (Auth::user()->soilSamples()->count() >= $limit) {
                return redirect()->route('samples.index')
                    ->with('error', "Maximum limit of {$limit} samples reached.");
            }
        }

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

    // Auto-compute trigger: fires when all 4 parameters are averaged
    public function show(SoilSample $sample)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && $sample->user_id !== $user->id) abort(403);

        if ($sample->allAveraged() && !$sample->isAnalyzed()) {
            // Prefer the 2-step pH test result when complete
            $phTest = $sample->phTest;
            $ph = ($phTest && $phTest->status === 'complete' && $phTest->final_ph)
                ? (float) $phTest->final_ph
                : $this->colorScience->colorToPhLevel($sample->ph_color_hex);

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

        $readings         = $sample->getReadingsByParameter();
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

        $allCrops = Crop::orderBy('name')->get();

        return view('samples.show', compact(
            'sample', 'readings',
            'cropsByTolerance', 'cropsByFertility', 'cropsByPh',
            'fertRec', 'allCrops'
        ));
    }

    // Admin-only deletion with password confirmation
    public function destroy(Request $request, SoilSample $sample)
    {
        if (!Auth::user()->isAdmin()) abort(403);

        $request->validate(['admin_password' => 'required|string']);

        if (!Hash::check($request->admin_password, Auth::user()->password)) {
            return redirect()->route('samples.show', $sample)
                ->with('error', 'Incorrect password. Sample was NOT deleted.');
        }

        $sample->delete(); // booted() observer wipes public/captures/{id}/

        return redirect()->route('samples.index')
            ->with('success', "Sample \"{$sample->sample_name}\" permanently deleted.");
    }
}
```

---

## 4. pH Test — BSWM 2-Step Protocol

The pH test implements the BSWM two-step colorimetric protocol: Step 1 uses CPR (Cresol Red) to determine the approximate pH range, then Step 2 uses either BCG (Bromocresol Green) for acidic soils or BTB (Bromothymol Blue) for near-neutral soils for confirmation. Three webcam captures are taken per step and averaged.

**File:** `app/Http/Controllers/PhTestController.php` *(key method)*

```php
<?php

namespace App\Http\Controllers;

use App\Models\PhTest;
use App\Models\SoilSample;
use App\Services\ColorScienceService;
use App\Services\PhTestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PhTestController extends Controller
{
    public function __construct(
        private readonly ColorScienceService $colorScience,
        private readonly PhTestService       $phTestService,
    ) {}

    public function capture(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sample_id' => 'required|integer|exists:soil_samples,id',
            'step'      => 'required|integer|in:1,2',
            'color_hex' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'r'         => 'required|integer|min:0|max:255',
            'g'         => 'required|integer|min:0|max:255',
            'b'         => 'required|integer|min:0|max:255',
            'snapshot'  => 'nullable|string',  // base64 data-URL from canvas
        ]);

        $sample = SoilSample::findOrFail($validated['sample_id']);
        $this->authorizeAccess($sample);

        $colorHex = strtoupper($validated['color_hex']);
        $step     = (int) $validated['step'];

        $phTest = $sample->phTest ?? PhTest::create([
            'sample_id' => $sample->id,
            'status'    => 'step1',
        ]);

        // Select indicator: CPR for Step 1, BCG or BTB for Step 2
        $indicatorSolution = $step === 1
            ? 'CPR'
            : (($phTest->step2_solution ?? null) ?: 'CPR');

        $phResult      = $this->colorScience->phTestColorToPhLevel($colorHex, $indicatorSolution);
        $computedPh    = $phResult['ph'];
        $confidencePct = $phResult['confidence_pct'];

        $reading = [
            'hex'            => $colorHex,
            'r'              => $validated['r'],
            'g'              => $validated['g'],
            'b'              => $validated['b'],
            'computed_value' => $computedPh,
            'confidence_pct' => $confidencePct,
        ];

        if ($step === 1) {
            $readings = $phTest->step1_readings ?? [];
            if (count($readings) >= 3) {
                return response()->json(['success' => false, 'message' => 'Step 1 already has 3 captures.'], 422);
            }

            $reading['chart_ph'] = PhTestService::snapToChartPh($computedPh, 'CPR');
            $readings[] = $reading;
            $phTest->step1_readings = $readings;

            if (count($readings) === 3) {
                // Average using chart_ph (discrete card values) to reflect physical card reading
                $values = array_column($readings, 'chart_ph');
                $stats  = $this->phTestService->computeStats($values);
                $next   = $this->phTestService->decideSolution($stats['average']);
                $remark = $this->phTestService->generateStep1Remarks(
                    $stats['average'], $next, $stats['confidence']
                );

                $phTest->step1_ph         = $stats['average'];
                $phTest->step1_chart_ph   = PhTestService::snapToChartPh($stats['average'], 'CPR');
                $phTest->step1_variance   = $stats['variance'];
                $phTest->step1_confidence = $stats['confidence'];
                $phTest->step1_outcome    = $remark['outcome'];
                $phTest->step1_remarks    = $remark['remarks'];
                $phTest->next_solution    = $next;

                if ($next === 'RETEST') {
                    $phTest->status = 'retest';
                } elseif ($next === 'CPR') {
                    // pH 5.4–5.8: CPR result is final (no Step 2 needed)
                    $phTest->final_ph = $stats['average'];
                    $phTest->status   = 'complete';
                    $this->persistPhReadings($sample, $readings);
                } else {
                    $phTest->status         = 'step2';
                    $phTest->step2_solution = $next;
                }
            }

            $phTest->save();
            return response()->json(['success' => true, 'step' => 1, 'count' => count($readings),
                'next_solution' => $phTest->next_solution, 'status' => $phTest->status, 'reload' => true]);
        }

        // Step 2 (BCG or BTB)
        if ($phTest->status !== 'step2') {
            return response()->json(['success' => false, 'message' => 'Complete Step 1 first.'], 422);
        }

        $readings = $phTest->step2_readings ?? [];
        $reading['chart_ph'] = PhTestService::snapToChartPh($computedPh, $phTest->step2_solution);
        $readings[] = $reading;
        $phTest->step2_readings = $readings;

        if (count($readings) === 3) {
            $values = array_column($readings, 'chart_ph');
            $stats  = $this->phTestService->computeStats($values);
            $avgHex = $this->phTestService->averageHex($readings);
            $remark = $this->phTestService->generateStep2Remarks(
                (float) $phTest->step1_ph, $stats['average'],
                $phTest->step2_solution, $stats['confidence']
            );

            $phTest->step2_ph         = $stats['average'];
            $phTest->step2_variance   = $stats['variance'];
            $phTest->step2_confidence = $stats['confidence'];
            $phTest->step2_outcome    = $remark['outcome'];
            $phTest->step2_remarks    = $remark['remarks'];
            $phTest->final_ph         = $stats['average'];
            $phTest->status           = 'complete';

            $this->persistPhReadings($sample, $readings);
            $sample->update(['ph_color_hex' => $avgHex]);
        }

        $phTest->save();
        return response()->json(['success' => true, 'step' => 2, 'count' => count($readings),
            'final_ph' => $phTest->final_ph, 'status' => $phTest->status, 'reload' => true]);
    }

    private function persistPhReadings(SoilSample $sample, array $readings): void
    {
        foreach ($readings as $i => $rd) {
            DB::table('soil_color_readings')->upsert([
                'sample_id'      => $sample->id,
                'parameter'      => 'ph',
                'test_number'    => $i + 1,
                'color_hex'      => $rd['hex'],
                'r'              => $rd['r'],
                'g'              => $rd['g'],
                'b'              => $rd['b'],
                'computed_value' => $rd['computed_value'],
                'captured_at'    => now(),
            ], ['sample_id', 'parameter', 'test_number'], [
                'color_hex', 'r', 'g', 'b', 'computed_value', 'captured_at',
            ]);
        }
    }

    private function authorizeAccess(SoilSample $sample): void
    {
        $user = Auth::user();
        if (!$user->isAdmin() && $sample->user_id !== $user->id) abort(403);
    }
}
```

---

## 5. NPK Color Capture

Each N, P, or K capture saves the hex color and RGB values, then counts and averages all 3 readings for the parameter. When the 3rd capture is saved, the averaged hex is stored back to the soil sample, triggering the auto-compute when all 4 parameters are complete.

**File:** `app/Http/Controllers/ColorReadingController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\SoilSample;
use App\Services\ColorScienceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ColorReadingController extends Controller
{
    public function __construct(private readonly ColorScienceService $colorScience) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sample_id'   => 'required|integer|exists:soil_samples,id',
            'parameter'   => 'required|in:ph,nitrogen,phosphorus,potassium',
            'color_hex'   => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'r'           => 'required|integer|min:0|max:255',
            'g'           => 'required|integer|min:0|max:255',
            'b'           => 'required|integer|min:0|max:255',
            'test_number' => 'required|integer|min:1|max:3',
            'snapshot'    => 'nullable|string',  // base64 JPEG from canvas
        ]);

        $sample = SoilSample::findOrFail($validated['sample_id']);

        if (!Auth::user()->isAdmin() && $sample->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Permission denied'], 403);
        }

        $colorHex      = strtoupper($validated['color_hex']);
        $computedValue = $this->colorScience->computeForParameter($validated['parameter'], $colorHex);
        $imagePath     = $this->saveSnapshot(
            $validated['snapshot'] ?? null,
            $sample->id,
            $validated['parameter'],
            $validated['test_number']
        );

        // Upsert the individual reading (allows recapture)
        DB::table('soil_color_readings')->upsert([
            'sample_id'      => $sample->id,
            'parameter'      => $validated['parameter'],
            'test_number'    => $validated['test_number'],
            'color_hex'      => $colorHex,
            'captured_image' => $imagePath,
            'r'              => $validated['r'],
            'g'              => $validated['g'],
            'b'              => $validated['b'],
            'computed_value' => $computedValue,
            'captured_at'    => now(),
        ], ['sample_id', 'parameter', 'test_number'],
           ['color_hex', 'captured_image', 'r', 'g', 'b', 'computed_value', 'captured_at']);

        // Aggregate: count and average RGB across all readings for this parameter
        $agg = DB::table('soil_color_readings')
            ->where('sample_id', $sample->id)
            ->where('parameter', $validated['parameter'])
            ->selectRaw('COUNT(*) as cnt, AVG(r) as avg_r, AVG(g) as avg_g, AVG(b) as avg_b')
            ->first();

        $testsDone = (int) $agg->cnt;
        $avgHex    = null;

        if ($testsDone === 3) {
            // Convert averaged RGB back to hex and store to soil_samples
            $avgR   = (int) round($agg->avg_r);
            $avgG   = (int) round($agg->avg_g);
            $avgB   = (int) round($agg->avg_b);
            $avgHex = sprintf('#%02X%02X%02X', $avgR, $avgG, $avgB);
            $col    = $validated['parameter'] . '_color_hex';
            $sample->update([$col => $avgHex]);
        }

        $totalReadings = DB::table('soil_color_readings')
            ->where('sample_id', $sample->id)->count();
        $sample->update(['tests_completed' => $totalReadings]);

        return response()->json([
            'success'        => true,
            'tests_done'     => $testsDone,
            'computed_value' => $computedValue,
            'avg_hex'        => $avgHex,
            'total_readings' => $totalReadings,
        ]);
    }

    private function saveSnapshot(?string $dataUrl, int $sampleId, string $param, int $testNumber): ?string
    {
        if (!$dataUrl || !str_contains($dataUrl, ',')) return null;
        $base64 = substr($dataUrl, strpos($dataUrl, ',') + 1);
        $image  = base64_decode($base64, strict: true);
        if ($image === false) return null;

        $dir  = public_path("captures/{$sampleId}");
        $file = "{$param}-{$testNumber}.jpg";
        if (!is_dir($dir)) mkdir($dir, 0755, recursive: true);
        file_put_contents("{$dir}/{$file}", $image);

        return "captures/{$sampleId}/{$file}";
    }
}
```

---

## 6. Color-to-Value Algorithm (CIEDE2000)

The `ColorScienceService` converts a captured hex color to a soil parameter value using the CIE L\*a\*b\* color space and the CIEDE2000 perceptual color difference formula. This approach more accurately represents how the human eye perceives color differences compared to simple RGB distance.

**Algorithm:**
1. Convert captured RGB → CIE XYZ → CIE L\*a\*b\*
2. For each reference chart color, compute CIEDE2000 ΔE (perceptual distance)
3. Find the 3 closest chart colors
4. If nearest ΔE ≤ 0.5, return exact chart value; otherwise interpolate using inverse-distance weighting

**File:** `app/Services/ColorScienceService.php`

```php
<?php

namespace App\Services;

use App\Models\NpkColorChart;
use App\Models\PhColorChart;

class ColorScienceService
{
    // ── BSWM Reference Color Charts ─────────────────────────────────

    // CPR indicator: pH 4.8–6.0 (amber-yellow hues, BSWM Step 1)
    public const CPR_COLOR_CHART = [
        '#FF8800' => 4.8, '#D2A65A' => 5.0, '#FFC800' => 5.2,
        '#B0622D' => 5.4, '#EDE800' => 5.6, '#9D2529' => 5.8, '#7E2938' => 6.0,
    ];

    // BCG indicator: pH 4.0–5.2 (yellow-green → teal-green, BSWM Step 2 acidic)
    public const BCG_COLOR_CHART = [
        '#CABB05' => 4.0, '#C1BE07' => 4.2, '#B6C209' => 4.4,
        '#80B21B' => 4.6, '#3C9B32' => 4.8, '#1A8D54' => 5.0, '#008071' => 5.2,
    ];

    // BTB indicator: pH 6.0–7.8 (yellow-green → deep blue, BSWM Step 2 near-neutral)
    public const BTB_COLOR_CHART = [
        '#C9D900' => 6.0, '#0FCA02' => 6.2, '#027419' => 6.4,
        '#022706' => 6.8, '#013251' => 7.2, '#1F0F99' => 7.8,
    ];

    // Nitrogen: pink (low) → dark red/black (high), 0–80 ppm
    public const NITROGEN_COLOR_CHART = [
        '#FFF5F5' => 2.0, '#FFE0E8' => 8.0, '#FFB3C6' => 15.0, '#FF80A0' => 22.0,
        '#FF4D80' => 30.0, '#E6006B' => 40.0, '#CC0066' => 50.0, '#440044' => 80.0,
    ];

    // Phosphorus: white (low) → dark blue (high), 0–55 ppm
    public const PHOSPHORUS_COLOR_CHART = [
        '#FEFEFE' => 1.0, '#EEF8FF' => 3.0, '#D4EEFF' => 5.0, '#A8D8F0' => 8.0,
        '#70BAE8' => 12.0, '#42A5F5' => 18.0, '#1E88E5' => 25.0, '#062A70' => 55.0,
    ];

    // Potassium: black (low) → white (high), 5–120 ppm
    public const POTASSIUM_COLOR_CHART = [
        '#0A0A0A' => 5.0, '#2A2A2A' => 15.0, '#555555' => 25.0, '#808080' => 40.0,
        '#AAAAAA' => 60.0, '#C8C8C8' => 80.0, '#FAFAFA' => 120.0,
    ];

    // ── Public API ───────────────────────────────────────────────────

    public function phTestColorToPhLevel(string $hex, string $solution): array
    {
        return match (strtoupper($solution)) {
            'BCG'   => $this->colorToBcgPhLevel($hex),
            'BTB'   => $this->colorToBtbPhLevel($hex),
            default => $this->colorToCprPhLevel($hex),
        };
    }

    public function colorToNitrogenLevel(string $hex): float
    {
        $chart = NpkColorChart::chartForNutrient('N') ?: self::NITROGEN_COLOR_CHART;
        return round(min(240.0, max(0.0, $this->matchColorToValue($hex, $chart))), 2);
    }

    public function computeForParameter(string $parameter, string $hex): float
    {
        return match ($parameter) {
            'ph'         => $this->colorToPhLevel($hex),
            'nitrogen'   => $this->colorToNitrogenLevel($hex),
            'phosphorus' => $this->colorToPhosphorusLevel($hex),
            'potassium'  => $this->colorToPotassiumLevel($hex),
        };
    }

    // ── RGB → CIE L*a*b* Conversion ──────────────────────────────────

    public function rgbToLab(int $r, int $g, int $b): array
    {
        // Step 1: Linearize sRGB (gamma removal)
        $r /= 255.0; $g /= 255.0; $b /= 255.0;
        $lin = fn($c) => $c > 0.04045 ? pow(($c + 0.055) / 1.055, 2.4) : $c / 12.92;
        $r = $lin($r); $g = $lin($g); $b = $lin($b);

        // Step 2: RGB → CIE XYZ (D65 illuminant)
        $x = $r * 0.4124564 + $g * 0.3575761 + $b * 0.1804375;
        $y = $r * 0.2126729 + $g * 0.7151522 + $b * 0.0721750;
        $z = $r * 0.0193339 + $g * 0.1191920 + $b * 0.9503041;

        // Step 3: Normalize by D65 white point
        $x /= 0.95047; $y /= 1.00000; $z /= 1.08883;

        // Step 4: XYZ → L*a*b*
        $f = fn($t) => $t > 0.008856 ? pow($t, 1.0/3.0) : (7.787 * $t + 16.0/116.0);
        return [
            'L' => round(116.0 * $f($y) - 16.0, 4),
            'a' => round(500.0 * ($f($x) - $f($y)), 4),
            'b' => round(200.0 * ($f($y) - $f($z)), 4),
        ];
    }

    // ── CIEDE2000 Color Difference Formula ───────────────────────────

    public function deltaE2000(array $lab1, array $lab2): float
    {
        [$L1, $a1, $b1] = [$lab1['L'], $lab1['a'], $lab1['b']];
        [$L2, $a2, $b2] = [$lab2['L'], $lab2['a'], $lab2['b']];

        $C1ab = sqrt($a1 ** 2 + $b1 ** 2);
        $C2ab = sqrt($a2 ** 2 + $b2 ** 2);
        $Cab  = ($C1ab + $C2ab) / 2.0;
        $Cab7 = $Cab ** 7;
        $G    = 0.5 * (1.0 - sqrt($Cab7 / ($Cab7 + 25.0 ** 7)));

        $a1p = $a1 * (1.0 + $G); $a2p = $a2 * (1.0 + $G);
        $C1p = sqrt($a1p ** 2 + $b1 ** 2);
        $C2p = sqrt($a2p ** 2 + $b2 ** 2);

        $h1p = ($b1 == 0 && $a1p == 0) ? 0.0 : atan2($b1, $a1p) * 180.0 / M_PI;
        if ($h1p < 0) $h1p += 360.0;
        $h2p = ($b2 == 0 && $a2p == 0) ? 0.0 : atan2($b2, $a2p) * 180.0 / M_PI;
        if ($h2p < 0) $h2p += 360.0;

        $dLp = $L2 - $L1;
        $dCp = $C2p - $C1p;

        if ($C1p * $C2p == 0.0) $dhp = 0.0;
        elseif (abs($h2p - $h1p) <= 180.0) $dhp = $h2p - $h1p;
        elseif ($h2p - $h1p > 180.0) $dhp = $h2p - $h1p - 360.0;
        else $dhp = $h2p - $h1p + 360.0;

        $dHp = 2.0 * sqrt($C1p * $C2p) * sin(deg2rad($dhp / 2.0));

        $Lbp = ($L1 + $L2) / 2.0;
        $Cbp = ($C1p + $C2p) / 2.0;

        if ($C1p * $C2p == 0.0) $Hbp = $h1p + $h2p;
        elseif (abs($h1p - $h2p) <= 180.0) $Hbp = ($h1p + $h2p) / 2.0;
        elseif ($h1p + $h2p < 360.0) $Hbp = ($h1p + $h2p + 360.0) / 2.0;
        else $Hbp = ($h1p + $h2p - 360.0) / 2.0;

        $T = 1.0 - 0.17 * cos(deg2rad($Hbp - 30.0))
               + 0.24 * cos(deg2rad(2.0 * $Hbp))
               + 0.32 * cos(deg2rad(3.0 * $Hbp + 6.0))
               - 0.20 * cos(deg2rad(4.0 * $Hbp - 63.0));

        $SL = 1.0 + 0.015 * ($Lbp - 50.0) ** 2 / sqrt(20.0 + ($Lbp - 50.0) ** 2);
        $SC = 1.0 + 0.045 * $Cbp;
        $SH = 1.0 + 0.015 * $Cbp * $T;

        $Cbp7   = $Cbp ** 7;
        $RC     = 2.0 * sqrt($Cbp7 / ($Cbp7 + 25.0 ** 7));
        $dTheta = 30.0 * exp(-(($Hbp - 275.0) / 25.0) ** 2);
        $RT     = -$RC * sin(deg2rad(2.0 * $dTheta));

        return sqrt(
            ($dLp / $SL) ** 2 + ($dCp / $SC) ** 2 + ($dHp / $SH) ** 2 +
            $RT * ($dCp / $SC) * ($dHp / $SH)
        );
    }

    // ── Color matching with CIEDE2000 interpolation ───────────────────

    private function matchColorToValueWithDeltaE(string $capturedHex, array $chart): array
    {
        $rgb = $this->hexToRgb($capturedHex);
        $lab = $this->rgbToLab($rgb['r'], $rgb['g'], $rgb['b']);

        $distances = [];
        foreach ($chart as $refHex => $refValue) {
            $rRgb        = $this->hexToRgb($refHex);
            $rLab        = $this->rgbToLab($rRgb['r'], $rRgb['g'], $rRgb['b']);
            $distances[] = ['value' => $refValue, 'de' => $this->deltaE2000($lab, $rLab)];
        }
        usort($distances, fn($a, $b) => $a['de'] <=> $b['de']);

        $top3    = array_slice($distances, 0, 3);
        $minDE   = $top3[0]['de'];

        // If exact match, return directly
        if ($minDE <= 0.5) return [$top3[0]['value'], $minDE];

        // Interpolate using inverse-distance weighting of top 3 closest colors
        $weightedSum = 0.0;
        $weightTotal = 0.0;
        foreach ($top3 as $d) {
            $w = 1.0 / max($d['de'], 0.0001);
            $weightedSum += $d['value'] * $w;
            $weightTotal += $w;
        }

        return [$weightTotal > 0 ? $weightedSum / $weightTotal : $top3[0]['value'], $minDE];
    }
}
```

---

## 7. Fertilizer Recommendation Engine

The `FertilizerService` implements the BSWM/PhilRice colorimetric soil test fertilizer recommendation guidelines. It provides discrete threshold-based recommendations for Urea (N), TSP (P), MOP (K), and dolomitic lime, plus a weighted fertility score.

**File:** `app/Services/FertilizerService.php`

```php
<?php

namespace App\Services;

class FertilizerService
{
    public function recommend(float $ph, float $n, float $p, float $k): array
    {
        $rec = ['lime_tons' => 0.0, 'urea_bags' => 0.0, 'tsp_bags' => 0.0, 'mop_bags' => 0.0, 'notes' => []];

        // Lime for pH correction (dolomitic lime, applied ≥ 2 weeks before planting)
        if ($ph < 5.0) {
            $rec['lime_tons'] = 2.0;
            $rec['notes'][] = 'Soil is strongly acidic (pH < 5.0). Apply 2 t/ha dolomitic lime.';
        } elseif ($ph < 5.5) {
            $rec['lime_tons'] = 1.0;
            $rec['notes'][] = 'Soil is moderately acidic (pH 5.0–5.5). Apply 1 t/ha dolomitic lime.';
        } elseif ($ph > 7.5) {
            $rec['notes'][] = 'Soil is alkaline (pH > 7.5). Consider elemental sulfur to lower pH.';
        }

        // Nitrogen — Urea 46-0-0 (50-kg bags per hectare)
        if ($n < 45) {
            $rec['urea_bags'] = 4.0;
            $rec['notes'][] = 'Low nitrogen. Apply Urea in 2 splits: ½ basal + ½ at panicle initiation.';
        } elseif ($n < 160) {
            $rec['urea_bags'] = 2.5;
            $rec['notes'][] = 'Medium nitrogen. Apply Urea: ½ basal + ½ at active tillering.';
        } else {
            $rec['urea_bags'] = 1.0;
            $rec['notes'][] = 'Adequate nitrogen. Apply 1 bag/ha Urea as maintenance.';
        }

        // Phosphorus — TSP 0-46-0 (basal application)
        if ($p < 15) {
            $rec['tsp_bags'] = 2.5;
            $rec['notes'][] = 'Low phosphorus. Apply TSP basally for root development.';
        } elseif ($p < 30) {
            $rec['tsp_bags'] = 1.5;
            $rec['notes'][] = 'Medium phosphorus. Apply TSP basally.';
        } else {
            $rec['tsp_bags'] = 0.0;
            $rec['notes'][] = 'Adequate phosphorus. No TSP needed this season.';
        }

        // Potassium — MOP 0-0-60 (basal application)
        if ($k < 20) {
            $rec['mop_bags'] = 2.0;
            $rec['notes'][] = 'Low potassium. Apply MOP basally.';
        } elseif ($k < 40) {
            $rec['mop_bags'] = 1.0;
            $rec['notes'][] = 'Medium potassium. Apply 1 bag MOP/ha.';
        } else {
            $rec['mop_bags'] = 0.0;
            $rec['notes'][] = 'Adequate potassium. No MOP needed.';
        }

        return $rec;
    }

    // Weighted fertility score: N 35%, P 25%, K 25%, pH 15%
    public function computeFertilityScore(float $ph, float $n, float $p, float $k): int
    {
        $phScore = match (true) {
            $ph >= 6.0 && $ph <= 7.0 => 100,
            $ph >= 5.5 && $ph <= 7.5 => 70,
            $ph >= 5.0 && $ph <= 8.0 => 40,
            default                  => 10,
        };
        $nScore = match (true) {
            $n >= 60 && $n <= 150 => 100,
            $n >= 45 && $n < 60  => 80,
            $n >= 160            => 80,
            $n >= 15             => 50,
            default              => 15,
        };
        $pScore = match (true) {
            $p >= 15 && $p <= 30 => 100,
            $p > 30 && $p <= 50  => 75,
            $p >= 8              => 50,
            default              => 15,
        };
        $kScore = match (true) {
            $k >= 20 && $k <= 40 => 100,
            $k > 40 && $k <= 70  => 75,
            $k >= 10             => 50,
            default              => 15,
        };
        return (int) round($nScore * 0.35 + $pScore * 0.25 + $kScore * 0.25 + $phScore * 0.15);
    }

    public function getNutrientStatus(string $parameter, float $value): string
    {
        $thresholds = [
            'ph'         => ['low_max' => 5.5,  'high_min' => 7.0],
            'nitrogen'   => ['low_max' => 45.0, 'high_min' => 160.0],
            'phosphorus' => ['low_max' => 15.0, 'high_min' => 30.0],
            'potassium'  => ['low_max' => 20.0, 'high_min' => 40.0],
        ];
        if (!isset($thresholds[$parameter])) return 'Medium';
        $t = $thresholds[$parameter];
        if ($parameter === 'ph') {
            if ($value < $t['low_max'])  return 'Acidic';
            if ($value > $t['high_min']) return 'Alkaline';
            return 'Optimal';
        }
        if ($value < $t['low_max'])   return 'Low';
        if ($value >= $t['high_min']) return 'High';
        return 'Medium';
    }
}
```

---

## 8. Crop Recommendation Algorithm

The `Crop` model provides three scoring strategies to match crops against measured soil conditions. Each strategy returns crops ranked by how many parameters (pH, N, P, K) fall within the crop's acceptable min–max range.

**File:** `app/Models/Crop.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Crop extends Model
{
    protected $fillable = [
        'name', 'description',
        'min_ph', 'max_ph',
        'min_nitrogen', 'max_nitrogen',
        'min_phosphorus', 'max_phosphorus',
        'min_potassium', 'max_potassium',
    ];

    /**
     * Strategy 1 — Tolerance Match
     * pH must be within range. Ranked by how many of the 4 parameters match.
     * Represents: "Can plant with the current soil as-is."
     */
    public static function groupByTolerance(float $ph, float $n, float $p, float $k)
    {
        return static::selectRaw("*, (
            CASE WHEN ? BETWEEN min_ph AND max_ph THEN 1 ELSE 0 END +
            CASE WHEN ? BETWEEN min_nitrogen AND max_nitrogen THEN 1 ELSE 0 END +
            CASE WHEN ? BETWEEN min_phosphorus AND max_phosphorus THEN 1 ELSE 0 END +
            CASE WHEN ? BETWEEN min_potassium AND max_potassium THEN 1 ELSE 0 END
        ) AS match_score", [$ph, $n, $p, $k])
            ->whereRaw('? BETWEEN min_ph AND max_ph', [$ph])
            ->orderByDesc('match_score')
            ->orderBy('name')
            ->get();
    }

    /**
     * Strategy 2 — Fertility Match
     * No pH filter. Ranked by NPK compatibility only.
     * Represents: "Crop matches nutrient profile; pH can be amended."
     */
    public static function groupByFertility(float $n, float $p, float $k)
    {
        return static::selectRaw("*, (
            CASE WHEN ? BETWEEN min_nitrogen AND max_nitrogen THEN 1 ELSE 0 END +
            CASE WHEN ? BETWEEN min_phosphorus AND max_phosphorus THEN 1 ELSE 0 END +
            CASE WHEN ? BETWEEN min_potassium AND max_potassium THEN 1 ELSE 0 END
        ) AS npk_score", [$n, $p, $k])
            ->havingRaw('npk_score > 0')
            ->orderByDesc('npk_score')
            ->orderBy('name')
            ->get();
    }

    /**
     * Strategy 3 — pH Threshold
     * All crops whose pH range covers the soil pH, ranked by NPK score.
     * Represents: "Every species that can survive this soil's acidity."
     */
    public static function groupByPh(float $ph, float $n, float $p, float $k)
    {
        return static::selectRaw("*, (
            CASE WHEN ? BETWEEN min_nitrogen AND max_nitrogen THEN 1 ELSE 0 END +
            CASE WHEN ? BETWEEN min_phosphorus AND max_phosphorus THEN 1 ELSE 0 END +
            CASE WHEN ? BETWEEN min_potassium AND max_potassium THEN 1 ELSE 0 END
        ) AS npk_score", [$n, $p, $k])
            ->whereRaw('? BETWEEN min_ph AND max_ph', [$ph])
            ->orderByDesc('npk_score')
            ->orderBy('name')
            ->get();
    }

    /** Returns the name of the single best-matching crop. */
    public static function topMatchName(float $ph, float $n, float $p, float $k): ?string
    {
        $crop = static::selectRaw("name, (
            CASE WHEN ? BETWEEN min_ph AND max_ph THEN 1 ELSE 0 END +
            CASE WHEN ? BETWEEN min_nitrogen AND max_nitrogen THEN 1 ELSE 0 END +
            CASE WHEN ? BETWEEN min_phosphorus AND max_phosphorus THEN 1 ELSE 0 END +
            CASE WHEN ? BETWEEN min_potassium AND max_potassium THEN 1 ELSE 0 END
        ) AS score", [$ph, $n, $p, $k])
            ->orderByDesc('score')
            ->orderBy('name')
            ->first();

        return $crop?->name;
    }
}
```

---

## 9. AI Agronomist Recommendation

The `AiRecommendationService` sends the complete soil test results and top matching crops to the Anthropic Claude API, which returns a structured agronomist-level advisory in three sections: Soil Health Assessment, Fertilizer Application Plan, and Crop & Planting Advice.

**File:** `app/Services/AiRecommendationService.php`

```php
<?php

namespace App\Services;

use App\Models\SoilSample;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiRecommendationService
{
    public function __construct(private readonly FertilizerService $fertilizerService) {}

    public function generate(SoilSample $sample, string $topCropsStr): array
    {
        $apiKey = env('ANTHROPIC_API_KEY', '');
        if (empty($apiKey)) {
            return ['success' => false, 'message' => 'Set the ANTHROPIC_API_KEY environment variable.'];
        }

        $fertRec     = $this->fertilizerService->recommend(
            (float)$sample->ph_level, (float)$sample->nitrogen_level,
            (float)$sample->phosphorus_level, (float)$sample->potassium_level
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
2. FERTILIZER APPLICATION PLAN — confirm/expand the automated recommendation
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
            if (empty($text)) return ['success' => false, 'message' => 'Empty response from AI service'];

            $sample->update(['ai_recommendation' => $text]);
            return ['success' => true, 'recommendation' => $text];

        } catch (\Exception $e) {
            Log::error("AI recommendation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to connect to AI service'];
        }
    }
}
```

---

## 10. Data Export

The `ExportController` generates UTF-8 BOM CSV files compatible with Microsoft Excel. The full export includes all soil readings, computed values, color hex codes, and fertilizer recommendations. The Phase 2 export produces a compact format compatible with Arduino/Phase 2 farm sensor records.

**File:** `app/Http/Controllers/ExportController.php` *(key method)*

```php
<?php

namespace App\Http\Controllers;

use App\Models\SoilSample;
use App\Services\FertilizerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __construct(private readonly FertilizerService $fertilizer) {}

    public function export(Request $request): StreamedResponse
    {
        $user    = Auth::user();
        $query   = SoilSample::with('user');
        if (!$user->isAdmin()) $query->where('user_id', $user->id);
        $samples  = $query->latest()->get();
        $filename = 'soil_samples_export_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($samples) {
            echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
            $out = fopen('php://output', 'w');

            fputcsv($out, ['=== SOIL ANALYSIS REPORT ===']);
            fputcsv($out, ['Export Date', now()->format('F j, Y g:i A')]);
            fputcsv($out, ['System', 'Soil Fertility Analyzer — OMA']);
            fputcsv($out, []);

            fputcsv($out, [
                'sample_id', 'sample_name', 'farmer_name', 'address', 'farm_location',
                'date_received', 'date_tested', 'analyzed_at', 'submitted_by',
                'ph_level', 'nitrogen_ppm', 'phosphorus_ppm', 'potassium_ppm', 'fertility_score',
                'ph_status', 'nitrogen_status', 'phosphorus_status', 'potassium_status',
                'fert_lime_tons_per_ha', 'fert_urea_bags_per_ha', 'fert_tsp_bags_per_ha', 'fert_mop_bags_per_ha',
                'recommended_crop', 'ai_recommendation',
            ]);

            foreach ($samples as $s) {
                $fr = !is_null($s->ph_level)
                    ? $this->fertilizer->recommend(
                        (float)$s->ph_level, (float)$s->nitrogen_level,
                        (float)$s->phosphorus_level, (float)$s->potassium_level
                      )
                    : null;

                fputcsv($out, [
                    $s->id, $s->sample_name, $s->farmer_name, $s->address, $s->location ?? '',
                    $s->sample_date, $s->date_tested, $s->analyzed_at ?? '', $s->user->username ?? '',
                    $s->ph_level ?? '', $s->nitrogen_level ?? '', $s->phosphorus_level ?? '',
                    $s->potassium_level ?? '', $s->fertility_score ?? '',
                    !is_null($s->ph_level) ? $this->fertilizer->getNutrientStatus('ph', (float)$s->ph_level) : '',
                    !is_null($s->nitrogen_level) ? $this->fertilizer->getNutrientStatus('nitrogen', (float)$s->nitrogen_level) : '',
                    !is_null($s->phosphorus_level) ? $this->fertilizer->getNutrientStatus('phosphorus', (float)$s->phosphorus_level) : '',
                    !is_null($s->potassium_level) ? $this->fertilizer->getNutrientStatus('potassium', (float)$s->potassium_level) : '',
                    $fr['lime_tons'] ?? '', $fr['urea_bags'] ?? '', $fr['tsp_bags'] ?? '', $fr['mop_bags'] ?? '',
                    $s->recommended_crop ?? '', $s->ai_recommendation ?? '',
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, ['=== COLUMN NOTES ===']);
            fputcsv($out, ['ph_level',            'Soil acidity/alkalinity (0–14 scale)']);
            fputcsv($out, ['nitrogen_ppm',         'Available nitrogen (NO3-N) in mg/kg']);
            fputcsv($out, ['phosphorus_ppm',       'Available phosphorus (Bray P1) in mg/kg']);
            fputcsv($out, ['potassium_ppm',        'Exchangeable potassium in mg/kg']);
            fputcsv($out, ['fertility_score',      'Overall index: N 35% + P 25% + K 25% + pH 15%']);
            fputcsv($out, ['fert_*_bags_per_ha',   '50-kg commercial bags per hectare (BSWM/PhilRice)']);
            fclose($out);
        }, $filename, [
            'Content-Type'  => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }
}
```

---

*End of Appendix D*
*Soil Fertility Analyzer — Office of the Municipal Agriculturist (OMA)*
