@extends('layouts.app')
@section('title', 'Help & Guidelines')

@section('styles')
<style>
    .help-toc a { text-decoration: none; color: #2e7d32; font-size: .9rem; }
    .help-toc a:hover { text-decoration: underline; }
    .help-toc li { padding: .25rem 0; }
    .section-anchor { scroll-margin-top: 80px; }
    .step-badge {
        display: inline-flex; align-items: center; justify-content: center;
        width: 28px; height: 28px; border-radius: 50%;
        background: #2e7d32; color: #fff; font-weight: 700;
        font-size: .85rem; flex-shrink: 0; margin-right: 10px;
    }
    .step-row { display: flex; align-items: flex-start; margin-bottom: .6rem; }
    .step-row p { margin: 0; line-height: 1.5; }
    .outcome-badge { font-size: .78rem; padding: .2rem .55rem; border-radius: 20px; font-weight: 600; }
    .help-nav { position: sticky; top: 80px; }
    code.key { background: #eee; border: 1px solid #ccc; border-radius: 4px; padding: 1px 6px; font-size: .85rem; }
</style>
@endsection

@section('content')

<div class="row">

    {{-- ── Left: Table of Contents (sticky) ──────────────────── --}}
    <div class="col-lg-3 d-none d-lg-block">
        <div class="help-nav card p-3">
            <h6 class="fw-bold text-success mb-3">
                <i class="fas fa-list-ul me-1"></i> Contents
            </h6>
            <ul class="list-unstyled help-toc mb-0">
                <li><a href="#overview"><i class="fas fa-circle fa-xs me-1 text-muted"></i> Overview</a></li>
                <li><a href="#getting-started"><i class="fas fa-circle fa-xs me-1 text-muted"></i> Getting Started</a></li>
                <li><a href="#ph-test"><i class="fas fa-circle fa-xs me-1 text-muted"></i> pH Test (2-Step)</a></li>
                <li class="ms-3"><a href="#ph-step1"><i class="fas fa-circle fa-xs me-1 text-muted"></i> Step 1 — CPR</a></li>
                <li class="ms-3"><a href="#ph-step2"><i class="fas fa-circle fa-xs me-1 text-muted"></i> Step 2 — BCG / BTB</a></li>
                <li class="ms-3"><a href="#ph-outcomes"><i class="fas fa-circle fa-xs me-1 text-muted"></i> Outcome Codes</a></li>
                <li><a href="#npk-tests"><i class="fas fa-circle fa-xs me-1 text-muted"></i> N / P / K Tests</a></li>
                <li><a href="#capture-guide"><i class="fas fa-circle fa-xs me-1 text-muted"></i> Capture Guide</a></li>
                <li><a href="#fertilizer-calc"><i class="fas fa-circle fa-xs me-1 text-muted"></i> Fertilizer Calculator</a></li>
                <li class="ms-3"><a href="#fert-formulas"><i class="fas fa-circle fa-xs me-1 text-muted"></i> Formulas</a></li>
                <li class="ms-3"><a href="#fert-thresholds"><i class="fas fa-circle fa-xs me-1 text-muted"></i> BSWM Thresholds</a></li>
                <li><a href="#crop-requirements"><i class="fas fa-circle fa-xs me-1 text-muted"></i> Crop Requirements</a></li>
                <li><a href="#samples-workflow"><i class="fas fa-circle fa-xs me-1 text-muted"></i> Sample Workflow</a></li>
                <li><a href="#roles"><i class="fas fa-circle fa-xs me-1 text-muted"></i> User Roles</a></li>
                <li><a href="#reference"><i class="fas fa-circle fa-xs me-1 text-muted"></i> Quick Reference</a></li>
                <li><a href="#troubleshooting"><i class="fas fa-circle fa-xs me-1 text-muted"></i> Troubleshooting</a></li>
            </ul>
        </div>
    </div>

    {{-- ── Right: Help content ─────────────────────────────────── --}}
    <div class="col-lg-9">

        <div class="d-flex align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-success mb-0">
                    <i class="fas fa-circle-question me-2"></i>Help &amp; Guidelines
                </h3>
                <p class="text-muted mb-0 small">BSWM Soil Fertility Analyzer — Technician Reference</p>
            </div>
        </div>

        {{-- ── OVERVIEW ──────────────────────────────────────────── --}}
        <div id="overview" class="section-anchor card mb-4">
            <div class="card-header bg-success text-white">
                <i class="fas fa-seedling me-2"></i>Overview
            </div>
            <div class="card-body">
                <p>
                    The <strong>Soil Fertility Analyzer</strong> guides field technicians through the Bureau of Soils
                    and Water Management (BSWM) colorimetric procedure to measure soil <strong>pH</strong>,
                    <strong>Nitrogen (N)</strong>, <strong>Phosphorus (P)</strong>, and <strong>Potassium (K)</strong>
                    using a portable test kit and camera-based color capture.
                </p>
                <div class="row g-3 mt-1">
                    <div class="col-sm-6 col-md-3">
                        <div class="border rounded p-2 text-center">
                            <i class="fas fa-vial fa-2x text-success mb-1"></i>
                            <div class="small fw-bold">4 Parameters</div>
                            <div class="text-muted" style="font-size:.75rem;">pH · N · P · K</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="border rounded p-2 text-center">
                            <i class="fas fa-camera fa-2x text-primary mb-1"></i>
                            <div class="small fw-bold">3 Captures / Test</div>
                            <div class="text-muted" style="font-size:.75rem;">Averaged for accuracy</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="border rounded p-2 text-center">
                            <i class="fas fa-clock fa-2x text-warning mb-1"></i>
                            <div class="small fw-bold">8 min Timer</div>
                            <div class="text-muted" style="font-size:.75rem;">Per reaction step</div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="border rounded p-2 text-center">
                            <i class="fas fa-leaf fa-2x text-info mb-1"></i>
                            <div class="small fw-bold">Auto Fertilizer Rec.</div>
                            <div class="text-muted" style="font-size:.75rem;">Crop-specific kg/ha</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── GETTING STARTED ───────────────────────────────────── --}}
        <div id="getting-started" class="section-anchor card mb-4">
            <div class="card-header bg-success text-white">
                <i class="fas fa-play-circle me-2"></i>Getting Started
            </div>
            <div class="card-body">
                <ol class="mb-0">
                    <li class="mb-2">Log in with your assigned username and password.</li>
                    <li class="mb-2">Go to <strong>Soil Samples → New Sample</strong> and fill in the farmer details, field location, and date tested.</li>
                    <li class="mb-2">Open the sample you just created and start the testing workflow in order:
                        <span class="badge bg-secondary me-1">pH</span>
                        <span class="badge bg-success me-1">Nitrogen</span>
                        <span class="badge bg-primary me-1">Phosphorus</span>
                        <span class="badge bg-info">Potassium</span>
                    </li>
                    <li class="mb-2">After all four parameters are done, review the <strong>Fertilizer Recommendation</strong> and use the calculator for crop-specific rates.</li>
                    <li>Export or print the sample report as needed.</li>
                </ol>
            </div>
        </div>

        {{-- ── pH TEST ───────────────────────────────────────────── --}}
        <div id="ph-test" class="section-anchor card mb-4">
            <div class="card-header bg-dark text-white">
                <i class="fas fa-flask me-2"></i>pH Test — BSWM 2-Step Protocol
            </div>
            <div class="card-body">

                <div class="alert alert-warning mb-4">
                    <i class="fas fa-triangle-exclamation me-1"></i>
                    <strong>Important:</strong> The pH test always starts with <strong>CPR (Comparative Point Reference)</strong>
                    solution to determine the reading range, then uses a confirmatory solution (BCG or BTB) if needed.
                </div>

                {{-- Step 1 --}}
                <h6 id="ph-step1" class="section-anchor fw-bold text-dark mb-3">
                    <span class="badge bg-secondary me-1">Step 1</span> CPR (Comparative Point Reference)
                </h6>
                <p class="text-muted small">Determines the approximate pH range of the soil.</p>
                @php
                $step1 = [
                    'Transfer soil sample to the 1st scratch mark (~0.5 g) in a clean dry test tube.',
                    'Fill with <strong>CPR Solution</strong> to the 2nd scratch mark (~1 mL).',
                    'Mix well by tapping into your palm for <strong>1 minute</strong>.',
                    'Let stand for <strong>2 minutes</strong>, then mix again for 1 minute.',
                    'Let stand for <strong>5 minutes</strong>.',
                    'Insert the test tube into the image capturing box for color capture.',
                    'Take <strong>3 captures</strong> — the system averages them for accuracy.',
                ];
                @endphp
                @foreach($step1 as $i => $s)
                <div class="step-row">
                    <span class="step-badge">{{ $i + 1 }}</span>
                    <p>{!! $s !!}</p>
                </div>
                @endforeach

                <div class="alert alert-info mt-3 mb-4">
                    <strong>After Step 1 capture</strong>, the system automatically decides:
                    <ul class="mb-0 mt-1">
                        <li>pH &le; 5.4 → proceed with <strong>BCG</strong> (Blue Cresol Green)</li>
                        <li>pH 5.4–5.8 → <strong>CPR result is final</strong> (no Step 2 needed)</li>
                        <li>pH &gt; 5.8 → proceed with <strong>BTB</strong> (Bromothymol Blue)</li>
                        <li>pH outside 4.0–7.6 → <strong>RETEST</strong> with fresh sample</li>
                    </ul>
                </div>

                {{-- Step 2 --}}
                <h6 id="ph-step2" class="section-anchor fw-bold text-dark mb-3">
                    <span class="badge bg-secondary me-1">Step 2</span> BCG or BTB (Confirmatory)
                </h6>
                <p class="text-muted small">Confirms the pH value with a narrower-range indicator solution.</p>
                @php
                $step2 = [
                    'Use a <strong>fresh test tube</strong> — do not reuse the Step 1 tube.',
                    'Transfer a new soil portion to the 1st scratch mark.',
                    'Fill with the <strong>assigned solution</strong> (BCG or BTB) to the 2nd scratch mark.',
                    'Mix for <strong>1 minute</strong>, stand <strong>2 minutes</strong>, mix <strong>1 minute</strong>, stand <strong>5 minutes</strong>.',
                    'Insert the test tube into the image capturing box for color capture.',
                    'Take <strong>3 captures</strong>.',
                    'The system records the final pH from the averaged Step 2 color.',
                ];
                @endphp
                @foreach($step2 as $i => $s)
                <div class="step-row">
                    <span class="step-badge" style="background:#1565c0;">{{ $i + 1 }}</span>
                    <p>{!! $s !!}</p>
                </div>
                @endforeach

                {{-- Outcome codes --}}
                <h6 id="ph-outcomes" class="section-anchor fw-bold text-dark mt-4 mb-3">
                    pH Outcome Codes
                </h6>
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th><th>Step</th><th>Meaning</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="badge bg-primary outcome-badge">win-bcg</span></td>
                            <td>Step 1</td>
                            <td>pH ≤ 5.4 — BCG confirmatory step needed</td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-info outcome-badge">win-btb</span></td>
                            <td>Step 1</td>
                            <td>pH &gt; 5.8 — BTB confirmatory step needed</td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-success outcome-badge">win-cpr</span></td>
                            <td>Step 1</td>
                            <td>pH 5.4–5.8 — CPR result is final; no Step 2</td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-warning text-dark outcome-badge">retest</span></td>
                            <td>Step 1</td>
                            <td>pH outside 4.0–7.6 — sample must be retested</td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-danger outcome-badge">high-acid</span></td>
                            <td>Step 1</td>
                            <td>CPR pH &lt; 4.0 — strongly acidic, retest</td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-secondary outcome-badge">alkaline</span></td>
                            <td>Step 1</td>
                            <td>CPR pH &gt; 7.6 — alkaline/outside range, retest</td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-success outcome-badge">confirmed</span></td>
                            <td>Step 2</td>
                            <td>Step 1 &amp; Step 2 pH values agree (Δ ≤ 0.3)</td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-warning text-dark outcome-badge">borderline</span></td>
                            <td>Step 2</td>
                            <td>Small discrepancy (Δ 0.3–0.5); Step 2 value used</td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-danger outcome-badge">inconsistent</span></td>
                            <td>Step 2</td>
                            <td>Large discrepancy (Δ &gt; 0.5); repeat recommended</td>
                        </tr>
                    </tbody>
                </table>

            </div>
        </div>

        {{-- ── N/P/K TESTS ───────────────────────────────────────── --}}
        <div id="npk-tests" class="section-anchor card mb-4">
            <div class="card-header text-white" style="background:#1b5e20;">
                <i class="fas fa-atom me-2"></i>Nitrogen · Phosphorus · Potassium Tests
            </div>
            <div class="card-body">
                <p>N, P, and K each have their own test page with the same 3-capture workflow but different reagents.</p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle">
                        <thead class="table-success">
                            <tr>
                                <th>Parameter</th>
                                <th>Reagent</th>
                                <th>Unit</th>
                                <th>Color Scheme</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><i class="fas fa-leaf text-success me-1"></i><strong>Nitrogen (N)</strong></td>
                                <td>Nitrogen Reagent (N-Reagent)</td>
                                <td>ppm</td>
                                <td><span class="badge bg-success">Green</span></td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-atom text-primary me-1"></i><strong>Phosphorus (P)</strong></td>
                                <td>Phosphorus Reagent (P-Reagent)</td>
                                <td>ppm</td>
                                <td><span class="badge bg-primary">Blue</span></td>
                            </tr>
                            <tr>
                                <td><i class="fas fa-seedling text-info me-1"></i><strong>Potassium (K)</strong></td>
                                <td>Potassium Reagent (K-Reagent)</td>
                                <td>ppm</td>
                                <td><span class="badge bg-info">Teal</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h6 class="fw-bold mt-3 mb-2">Protocol (same for all three):</h6>
                @php
                $npkSteps = [
                    'Transfer soil sample to the 1st scratch mark (~0.5 g) in a clean dry test tube.',
                    'Fill with the <strong>appropriate reagent</strong> to the 2nd scratch mark (~1 mL).',
                    'Mix well by tapping into your palm for <strong>1 minute</strong>.',
                    'Let stand for <strong>2 minutes</strong>, then mix again for 1 minute.',
                    'Let stand for <strong>5 minutes</strong>.',
                    'Insert the test tube into the image capturing box for color capture.',
                    'Take <strong>3 captures</strong> — the system will average and compute the ppm value.',
                ];
                @endphp
                @foreach($npkSteps as $i => $s)
                <div class="step-row">
                    <span class="step-badge">{{ $i + 1 }}</span>
                    <p>{!! $s !!}</p>
                </div>
                @endforeach
            </div>
        </div>

        {{-- ── CAPTURE GUIDE ─────────────────────────────────────── --}}
        <div id="capture-guide" class="section-anchor card mb-4">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-camera me-2"></i>Image Capture Guide
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <h6 class="fw-bold">Before capturing:</h6>
                        <ul class="mb-0">
                            <li>Click <strong>Start Camera</strong> and allow browser access.</li>
                            <li>Place the test tube inside the <strong>image capturing box</strong>.</li>
                            <li>Ensure the box lid is closed for consistent lighting.</li>
                            <li>The <strong>dashed circle crosshair</strong> on the camera feed shows the sampling zone — center the test tube color within it.</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold">Taking captures:</h6>
                        <ul class="mb-0">
                            <li>Captures must be taken <strong>sequentially</strong> (1 → 2 → 3).</li>
                            <li>Click <strong>Capture 1</strong>, wait for "Done", then Capture 2, etc.</li>
                            <li>Each reading is stored immediately to the server.</li>
                            <li>You can <strong>redo</strong> an individual capture using the <i class="fas fa-redo fa-xs"></i> button.</li>
                            <li>After all 3 captures the system shows the <strong>averaged color swatch</strong> and computed value.</li>
                        </ul>
                    </div>
                </div>
                <div class="alert alert-warning mt-3 mb-0">
                    <i class="fas fa-triangle-exclamation me-1"></i>
                    <strong>Tip:</strong> Always use the <strong>8-minute reaction timer</strong> before capturing. Premature reading can produce inaccurate results.
                </div>
            </div>
        </div>

        {{-- ── FERTILIZER CALCULATOR ─────────────────────────────── --}}
        <div id="fertilizer-calc" class="section-anchor card mb-4">
            <div class="card-header bg-warning text-dark">
                <i class="fas fa-calculator me-2"></i>Fertilizer Calculator
            </div>
            <div class="card-body">
                <p>
                    Found on the <strong>Sample Detail</strong> page (scroll to <em>Fertilizer Recommendation</em>).
                    Use this after all 4 test parameters are complete.
                </p>
                <ol>
                    <li class="mb-1">Select the <strong>Crop</strong> from the dropdown (linked to the crops database).</li>
                    <li class="mb-1">Enter the <strong>Farm Area</strong> in hectares.</li>
                    <li class="mb-1">Choose a <strong>Primary Fertilizer Type</strong> (e.g., Urea 46-0-0, Complete 14-14-14).</li>
                    <li class="mb-1">Click <strong>Calculate Fertilizer</strong> to see results.</li>
                </ol>

                {{-- ── Formulas ──────────────────────────────────────── --}}
                <h6 id="fert-formulas" class="section-anchor fw-bold mt-4 mb-2">
                    <i class="fas fa-superscript me-1 text-warning"></i>Calculation Formulas
                </h6>

                <div class="alert alert-secondary small mb-3">
                    <strong>Step 1 — Convert crop NPK targets from ppm to kg/ha</strong><br>
                    The crop's required nutrient level is stored in ppm. To convert:
                    <div class="bg-white border rounded p-2 mt-2 font-monospace">
                        Target<sub>kg/ha</sub> = Target<sub>ppm</sub> × 2
                    </div>
                    <div class="text-muted mt-1">
                        Basis: 1 ppm ≈ 2 kg/ha at 0–15 cm sampling depth
                        (soil bulk density ~1.33 g/cm³ × 15 cm × 10,000 m²/ha ÷ 1,000,000 ≈ 2 kg/ha per ppm).
                    </div>
                </div>

                <div class="alert alert-secondary small mb-3">
                    <strong>Step 2 — Nutrient Deficit</strong><br>
                    The deficit is the gap between what the crop needs and what the soil already has.
                    A negative deficit means no fertilizer is needed for that nutrient.
                    <div class="bg-white border rounded p-2 mt-2 font-monospace">
                        Deficit<sub>ppm</sub> = max(0, &nbsp;Target<sub>ppm</sub> − Soil<sub>ppm</sub>)<br>
                        Deficit<sub>kg/ha</sub> = Deficit<sub>ppm</sub> × 2
                    </div>
                </div>

                <div class="alert alert-secondary small mb-3">
                    <strong>Step 3 — Primary Fertilizer Rate (bags/ha)</strong><br>
                    The system calculates how many 50-kg bags of the <em>chosen fertilizer</em> are needed per hectare,
                    computed independently for each nutrient the fertilizer can supply, then takes the
                    <strong>maximum</strong> (the most limiting nutrient drives the rate):
                    <div class="bg-white border rounded p-2 mt-2 font-monospace">
                        BagsForN = Deficit<sub>N, kg/ha</sub> ÷ (50 × N_grade)<br>
                        BagsForP = Deficit<sub>P, kg/ha</sub> ÷ (50 × P_grade)<br>
                        BagsForK = Deficit<sub>K, kg/ha</sub> ÷ (50 × K_grade)<br>
                        <br>
                        Primary bags/ha = max(BagsForN, BagsForP, BagsForK)
                    </div>
                    <div class="text-muted mt-1">
                        <em>N_grade, P_grade, K_grade</em> are the fractional nutrient contents of the fertilizer
                        (e.g., Urea 46-0-0 → N_grade = 0.46, P_grade = 0, K_grade = 0).
                        Division by zero is skipped for grades = 0.
                    </div>
                </div>

                <div class="alert alert-secondary small mb-3">
                    <strong>Step 4 — Supplemental Fertilizers (TSP &amp; MOP)</strong><br>
                    After the primary fertilizer is applied, the system checks whether the remaining P and K deficits
                    are still unmet. If so, supplemental fertilizers are added:
                    <div class="bg-white border rounded p-2 mt-2 font-monospace">
                        Remaining P deficit = Deficit<sub>P, kg/ha</sub> − (Primary bags/ha × 50 × 0.46)<br>
                        <span class="text-muted">if Remaining P &gt; 0:</span><br>
                        &nbsp;&nbsp;TSP bags/ha = Remaining P ÷ (50 × 0.46)<br>
                        <br>
                        Remaining K deficit = Deficit<sub>K, kg/ha</sub> − (Primary bags/ha × 50 × 0.60)<br>
                        <span class="text-muted">if Remaining K &gt; 0:</span><br>
                        &nbsp;&nbsp;MOP bags/ha = Remaining K ÷ (50 × 0.60)
                    </div>
                    <div class="text-muted mt-1">
                        TSP grade = 0.46 (Triple Superphosphate 0-46-0) &nbsp;|&nbsp;
                        MOP grade = 0.60 (Muriate of Potash 0-0-60)
                    </div>
                </div>

                <div class="alert alert-secondary small mb-3">
                    <strong>Step 5 — Total Fertilizer for Farm Area</strong>
                    <div class="bg-white border rounded p-2 mt-2 font-monospace">
                        Total kg = (bags/ha × 50 kg/bag) × Area<sub>ha</sub>
                    </div>
                </div>

                <div class="alert alert-secondary small mb-3">
                    <strong>Lime for pH Correction (BSWM Thresholds)</strong><br>
                    Applied as dolomitic lime to correct soil acidity before planting:
                    <div class="bg-white border rounded p-2 mt-2 font-monospace">
                        pH &lt; 5.0 → 2.0 t/ha &nbsp;(strongly acidic)<br>
                        pH 5.0–5.5 → 1.0 t/ha &nbsp;(moderately acidic)<br>
                        pH ≥ 5.5 → no lime needed
                    </div>
                    <div class="text-muted mt-1">
                        Total lime (tonnes) = Lime rate (t/ha) × Farm area (ha)
                    </div>
                </div>

                {{-- BSWM Thresholds ───────────────────────────── --}}
                <h6 id="fert-thresholds" class="section-anchor fw-bold mt-4 mb-2">
                    <i class="fas fa-table me-1 text-warning"></i>BSWM Nutrient Thresholds &amp; Fertilizer Rates
                </h6>
                <p class="small text-muted">
                    These thresholds are used by the <strong>Quick Recommendation</strong> (top of the sample page)
                    based on BSWM/PhilRice colorimetric soil test guidelines.
                    The <em>Fertilizer Calculator</em> below it uses the crop-specific formulas above instead.
                </p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-warning">
                            <tr>
                                <th>Nutrient</th>
                                <th>Level</th>
                                <th>Soil Range</th>
                                <th>Recommended Rate</th>
                                <th>Application Timing</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="table-danger">
                                <td rowspan="3"><strong>Nitrogen</strong><br><small class="text-muted">Urea 46-0-0</small></td>
                                <td><span class="badge bg-danger">Low</span></td>
                                <td>&lt; 45 ppm</td>
                                <td>4.0 bags/ha</td>
                                <td>½ basal + ½ at panicle initiation</td>
                            </tr>
                            <tr class="table-warning">
                                <td><span class="badge bg-warning text-dark">Medium</span></td>
                                <td>45–160 ppm</td>
                                <td>2.5 bags/ha</td>
                                <td>½ basal + ½ at active tillering</td>
                            </tr>
                            <tr class="table-success">
                                <td><span class="badge bg-success">High</span></td>
                                <td>≥ 160 ppm</td>
                                <td>1.0 bag/ha</td>
                                <td>Maintenance only</td>
                            </tr>
                            <tr class="table-danger">
                                <td rowspan="3"><strong>Phosphorus</strong><br><small class="text-muted">TSP 0-46-0</small></td>
                                <td><span class="badge bg-danger">Low</span></td>
                                <td>&lt; 15 ppm</td>
                                <td>2.5 bags/ha</td>
                                <td>Basal (at planting)</td>
                            </tr>
                            <tr class="table-warning">
                                <td><span class="badge bg-warning text-dark">Medium</span></td>
                                <td>15–30 ppm</td>
                                <td>1.5 bags/ha</td>
                                <td>Basal</td>
                            </tr>
                            <tr class="table-success">
                                <td><span class="badge bg-success">High</span></td>
                                <td>≥ 30 ppm</td>
                                <td>0 bags/ha</td>
                                <td>None needed</td>
                            </tr>
                            <tr class="table-danger">
                                <td rowspan="3"><strong>Potassium</strong><br><small class="text-muted">MOP 0-0-60</small></td>
                                <td><span class="badge bg-danger">Low</span></td>
                                <td>&lt; 20 ppm</td>
                                <td>2.0 bags/ha</td>
                                <td>Basal</td>
                            </tr>
                            <tr class="table-warning">
                                <td><span class="badge bg-warning text-dark">Medium</span></td>
                                <td>20–40 ppm</td>
                                <td>1.0 bag/ha</td>
                                <td>Basal</td>
                            </tr>
                            <tr class="table-success">
                                <td><span class="badge bg-success">High</span></td>
                                <td>≥ 40 ppm</td>
                                <td>0 bags/ha</td>
                                <td>None needed</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-info small mt-2 mb-0">
                    <i class="fas fa-circle-info me-1"></i>
                    All fertilizer rates above are per hectare using 50-kg commercial bags.
                    Source: BSWM/PhilRice Soil Fertility Management Guidelines.
                </div>
            </div>
        </div>

        {{-- ── CROP REQUIREMENTS REFERENCE ───────────────────────── --}}
        <div id="crop-requirements" class="section-anchor card mb-4">
            <div class="card-header text-white" style="background:#1b5e20;">
                <i class="fas fa-leaf me-2"></i>Crop pH &amp; NPK Requirements Reference
            </div>
            <div class="card-body">
                <p>
                    The <a href="{{ route('crops.requirements') }}" class="text-success fw-semibold">
                        <i class="fas fa-arrow-up-right-from-square fa-xs"></i> Crop Requirements
                    </a> page lists all crops in the system with their acceptable pH, N, P, and K ranges.
                    Use it to <strong>manually verify</strong> the fertilizer calculator results in Excel.
                </p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <h6 class="fw-bold"><i class="fas fa-file-csv text-warning me-1"></i>Export to Excel workflow</h6>
                            <ol class="mb-0 small">
                                <li>Open <strong>Crop Requirements</strong> from the sidebar.</li>
                                <li>Click <strong>Export to Excel / CSV</strong> to download the reference table.</li>
                                <li>Open the downloaded CSV in Excel or Google Sheets.</li>
                                <li>Compare your soil test values (pH, N ppm, P ppm, K ppm) against the Min–Max columns for each crop.</li>
                                <li>Apply the formulas in this guide to verify the calculator output.</li>
                            </ol>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <h6 class="fw-bold"><i class="fas fa-table-cells text-success me-1"></i>Manual verification in Excel</h6>
                            <p class="small mb-2">Example Excel formulas (soil in row 2, crop requirements in reference sheet):</p>
                            <div class="bg-light border rounded p-2 font-monospace small">
                                <div>=MAX(0, (C2-B2)*2)</div>
                                <div class="text-muted">Deficit kg/ha for N</div>
                                <br>
                                <div>=D2/(50*0.46)</div>
                                <div class="text-muted">TSP bags/ha for P deficit</div>
                                <br>
                                <div>=E2/(50*0.60)</div>
                                <div class="text-muted">MOP bags/ha for K deficit</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── SAMPLE WORKFLOW ───────────────────────────────────── --}}
        <div id="samples-workflow" class="section-anchor card mb-4">
            <div class="card-header bg-success text-white">
                <i class="fas fa-diagram-project me-2"></i>Full Sample Workflow
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                    <div class="text-center">
                        <div class="badge bg-secondary p-2" style="font-size:.8rem;">Create Sample</div>
                    </div>
                    <i class="fas fa-arrow-right text-muted"></i>
                    <div class="text-center">
                        <div class="badge bg-dark p-2" style="font-size:.8rem;">pH Test (CPR)</div>
                    </div>
                    <i class="fas fa-arrow-right text-muted"></i>
                    <div class="text-center">
                        <div class="badge bg-dark p-2" style="font-size:.8rem;">pH Step 2 (if needed)</div>
                    </div>
                    <i class="fas fa-arrow-right text-muted"></i>
                    <div class="text-center">
                        <div class="badge bg-success p-2" style="font-size:.8rem;">Nitrogen</div>
                    </div>
                    <i class="fas fa-arrow-right text-muted"></i>
                    <div class="text-center">
                        <div class="badge bg-primary p-2" style="font-size:.8rem;">Phosphorus</div>
                    </div>
                    <i class="fas fa-arrow-right text-muted"></i>
                    <div class="text-center">
                        <div class="badge bg-info p-2" style="font-size:.8rem;">Potassium</div>
                    </div>
                    <i class="fas fa-arrow-right text-muted"></i>
                    <div class="text-center">
                        <div class="badge bg-warning text-dark p-2" style="font-size:.8rem;">Fertilizer Rec.</div>
                    </div>
                </div>
                <p class="text-muted small mb-0">
                    Each test page shows a <strong>progress pill bar</strong> at the top so you always know which step is active and which are complete
                    (<i class="fas fa-check-circle text-success fa-xs"></i> = done,
                    <i class="fas fa-circle-dot fa-xs text-primary"></i> = active,
                    <i class="fas fa-circle fa-xs text-muted"></i> = pending).
                </p>
            </div>
        </div>

        {{-- ── USER ROLES ─────────────────────────────────────────── --}}
        <div id="roles" class="section-anchor card mb-4">
            <div class="card-header bg-secondary text-white">
                <i class="fas fa-users me-2"></i>User Roles
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <h6 class="fw-bold"><i class="fas fa-user-shield text-danger me-1"></i>Admin</h6>
                            <ul class="mb-0 small">
                                <li>Can view <strong>all</strong> soil samples from all technicians</li>
                                <li>Can manage user accounts (create, deactivate)</li>
                                <li>Can export full dataset and Phase 2 data</li>
                                <li>Has access to the Admin Dashboard with aggregate stats</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <h6 class="fw-bold"><i class="fas fa-user text-success me-1"></i>Technician</h6>
                            <ul class="mb-0 small">
                                <li>Can view and test <strong>their own</strong> samples only</li>
                                <li>Can create new samples and manage farmers</li>
                                <li>Runs all 4 BSWM tests per sample</li>
                                <li>Can export their own sample reports</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── QUICK REFERENCE ───────────────────────────────────── --}}
        <div id="reference" class="section-anchor card mb-4">
            <div class="card-header bg-info text-white">
                <i class="fas fa-book-open me-2"></i>Quick Reference Numbers
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-info">
                            <tr><th>Item</th><th>Value</th><th>Notes</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>Soil sample volume</td><td>~0.5 g</td><td>1st scratch mark in test tube</td></tr>
                            <tr><td>Reagent volume</td><td>~1 mL</td><td>2nd scratch mark</td></tr>
                            <tr><td>Reaction timer</td><td>8 min total</td><td>1 min mix + 2 wait + 1 mix + 5 wait</td></tr>
                            <tr><td>Captures per test</td><td>3</td><td>Averaged via RGB mean</td></tr>
                            <tr><td>Crosshair zone</td><td>70 × 70 px</td><td>Center of 320×240 camera frame</td></tr>
                            <tr><td>ppm → kg/ha factor</td><td>× 2</td><td>At 0–15 cm sampling depth</td></tr>
                            <tr><td>Commercial fertilizer bag</td><td>50 kg</td><td>Standard Philippine bag size</td></tr>
                            <tr><td>BCG range</td><td>pH 4.0–5.8</td><td>Acidic soils</td></tr>
                            <tr><td>BTB range</td><td>pH 5.8–7.6</td><td>Near-neutral to neutral soils</td></tr>
                            <tr><td>CPR final range</td><td>pH 5.4–5.8</td><td>No Step 2 needed</td></tr>
                            <tr><td>Retest threshold</td><td>pH &lt; 4.0 or &gt; 7.6</td><td>Outside indicator range</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ── TROUBLESHOOTING ───────────────────────────────────── --}}
        <div id="troubleshooting" class="section-anchor card mb-4">
            <div class="card-header bg-danger text-white">
                <i class="fas fa-wrench me-2"></i>Troubleshooting
            </div>
            <div class="card-body">
                <div class="accordion accordion-flush" id="troubleAccordion">

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#t1">
                                Camera does not start
                            </button>
                        </h2>
                        <div id="t1" class="accordion-collapse collapse" data-bs-parent="#troubleAccordion">
                            <div class="accordion-body small">
                                <ul class="mb-0">
                                    <li>Make sure you clicked "Allow" when the browser asked for camera permission.</li>
                                    <li>Check that no other application (e.g., video call software) is using the camera.</li>
                                    <li>Try refreshing the page and clicking <strong>Start Camera</strong> again.</li>
                                    <li>Use Chrome or Edge for best webcam support.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#t2">
                                Capture button does not become active
                            </button>
                        </h2>
                        <div id="t2" class="accordion-collapse collapse" data-bs-parent="#troubleAccordion">
                            <div class="accordion-body small">
                                Capture buttons unlock <strong>sequentially</strong>. Capture 2 only becomes available after Capture 1 is saved, and Capture 3 after Capture 2. Make sure the previous capture completed successfully (look for the green "Done" text).
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#t3">
                                pH test shows "RETEST" outcome
                            </button>
                        </h2>
                        <div id="t3" class="accordion-collapse collapse" data-bs-parent="#troubleAccordion">
                            <div class="accordion-body small">
                                <ul class="mb-0">
                                    <li>The computed pH from CPR is outside the 4.0–7.6 readable range.</li>
                                    <li>Prepare a fresh soil sample and repeat the CPR step.</li>
                                    <li>Ensure the test tube was clean and dry before use.</li>
                                    <li>Verify the reagent is not expired or contaminated.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#t4">
                                Step 2 outcome shows "inconsistent"
                            </button>
                        </h2>
                        <div id="t4" class="accordion-collapse collapse" data-bs-parent="#troubleAccordion">
                            <div class="accordion-body small">
                                The difference between Step 1 (CPR) and Step 2 (BCG/BTB) pH values exceeds 0.5 units. This usually means:
                                <ul class="mb-0 mt-1">
                                    <li>The soil sample preparation was inconsistent between steps.</li>
                                    <li>The wrong Step 2 solution was used (BCG vs BTB).</li>
                                    <li>Timing was not followed (not enough mixing or standing time).</li>
                                </ul>
                                Use the Step 2 pH value as the final result, but note the inconsistency in your report.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#t5">
                                Fertilizer calculator shows 0 bags needed
                            </button>
                        </h2>
                        <div id="t5" class="accordion-collapse collapse" data-bs-parent="#troubleAccordion">
                            <div class="accordion-body small">
                                This means the soil's measured nutrient level <strong>meets or exceeds</strong> what the selected crop requires — no additional fertilizer is needed for that nutrient. Check that you selected the correct crop and that all 3 N/P/K tests are completed (not just pH).
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="text-muted small text-center mb-4">
            <i class="fas fa-info-circle me-1"></i>
            For technical issues or account problems, contact your system administrator.
        </div>

    </div>{{-- /col --}}
</div>{{-- /row --}}

@endsection
