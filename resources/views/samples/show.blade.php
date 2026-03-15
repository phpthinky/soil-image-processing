@extends('layouts.app')
@section('title', $sample->sample_name)
@section('content')

<div class="row mb-3">
    <div class="col">
        <a href="{{ route('samples.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to All Samples
        </a>
        @if($sample->isAnalyzed())
        <a href="{{ route('samples.report', $sample) }}" class="btn btn-sm btn-outline-info ms-2">
            <i class="fas fa-microscope"></i> View Test Report
        </a>
        <a href="{{ route('export', ['sample_id' => $sample->id]) }}" class="btn btn-sm btn-success ms-2">
            <i class="fas fa-file-excel"></i> Export to Excel
        </a>
        @endif
    </div>
</div>

{{-- Sample info card --}}
<div class="card mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="fas fa-vial me-2"></i>{{ $sample->sample_name }}</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <p><strong>Farmer:</strong> {{ $sample->farmer_name }}</p>
                <p><strong>Address:</strong> {{ $sample->address }}</p>
                <p><strong>Farm Location:</strong> {{ $sample->location ?? '—' }}</p>
            </div>
            <div class="col-md-4">
                <p><strong>Date Received:</strong> {{ $sample->sample_date->format('F j, Y') }}</p>
                <p><strong>Date Tested:</strong> {{ $sample->date_tested->format('F j, Y') }}</p>
                @if($sample->analyzed_at)
                <p><strong>Analyzed:</strong> {{ $sample->analyzed_at->format('F j, Y g:i A') }}</p>
                @endif
            </div>
            <div class="col-md-4 text-center">
                @if(!is_null($sample->fertility_score))
                    <div class="display-4 fw-bold text-{{ $sample->fertilityColorClass() }}">{{ $sample->fertility_score }}%</div>
                    <p class="text-muted mb-0">Fertility Score</p>
                    @if($sample->recommended_crop)
                    <span class="badge bg-success mt-1">
                        <i class="fas fa-seedling me-1"></i>Top: {{ $sample->recommended_crop }}
                    </span>
                    @endif
                @else
                    <p class="text-muted"><em>Not yet analyzed</em></p>
                    <small class="text-muted">{{ $sample->tests_completed }}/12 tests captured</small>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ── TESTING PROGRESS SECTION ────────────────────────────────────────────── --}}
@if(!$sample->isAnalyzed())
@php
$ph_count = count($readings['ph'] ?? []);
$n_count  = count($readings['nitrogen'] ?? []);
$p_count  = count($readings['phosphorus'] ?? []);
$k_count  = count($readings['potassium'] ?? []);
$totalDone = (int)($sample->tests_completed ?? 0);

$paramCards = [
    'ph' => [
        'label'   => 'Soil pH',
        'icon'    => 'fa-flask',
        'color'   => 'primary',
        'count'   => $ph_count,
        'avgHex'  => $sample->ph_color_hex,
        'route'   => route('ph-test.show', $sample),
        'badge'   => '2-Step',
    ],
    'nitrogen' => [
        'label'  => 'Nitrogen (N)',
        'icon'   => 'fa-leaf',
        'color'  => 'success',
        'count'  => $n_count,
        'avgHex' => $sample->nitrogen_color_hex,
        'route'  => route('parameter-test.show', [$sample, 'nitrogen']),
        'badge'  => null,
    ],
    'phosphorus' => [
        'label'  => 'Phosphorus (P)',
        'icon'   => 'fa-atom',
        'color'  => 'primary',
        'count'  => $p_count,
        'avgHex' => $sample->phosphorus_color_hex,
        'route'  => route('parameter-test.show', [$sample, 'phosphorus']),
        'badge'  => null,
    ],
    'potassium' => [
        'label'  => 'Potassium (K)',
        'icon'   => 'fa-seedling',
        'color'  => 'info',
        'count'  => $k_count,
        'avgHex' => $sample->potassium_color_hex,
        'route'  => route('parameter-test.show', [$sample, 'potassium']),
        'badge'  => null,
    ],
];
@endphp

<div class="card border-warning mb-4">
    <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-microscope me-2"></i>Soil Parameter Testing</h5>
        <span class="badge bg-dark">{{ $totalDone }}/12 captures</span>
    </div>
    <div class="card-body">

        <div class="alert alert-info py-2 mb-4">
            <i class="fas fa-info-circle me-1"></i>
            Each parameter has its own dedicated test page with <strong>3 captures</strong> for accuracy.
            Complete all 4 parameters to compute the soil analysis.
        </div>

        {{-- Parameter cards --}}
        <div class="row g-3 mb-4">
            @foreach($paramCards as $key => $pc)
            @php
                $done   = ($pc['count'] >= 3);
                $active = !$done;
            @endphp
            <div class="col-md-3 col-sm-6">
                <div class="card h-100 {{ $done ? 'border-success' : 'border-' . $pc['color'] }}">
                    <div class="card-header text-center py-2 bg-{{ $done ? 'success' : $pc['color'] }} text-white">
                        <i class="fas {{ $pc['icon'] }} me-1"></i>
                        <strong>{{ $pc['label'] }}</strong>
                        @if($pc['badge'])
                        <span class="badge bg-white text-dark ms-1" style="font-size:.6rem;">{{ $pc['badge'] }}</span>
                        @endif
                    </div>
                    <div class="card-body text-center py-3">
                        <div class="d-flex justify-content-center gap-2 mb-2">
                            @for($i = 1; $i <= 3; $i++)
                            <div style="width:18px;height:18px;border-radius:50%;
                                        background:{{ $pc['count'] >= $i ? ($done ? '#198754' : 'var(--bs-' . $pc['color'] . ')') : '#dee2e6' }};
                                        border:2px solid {{ $pc['count'] >= $i ? ($done ? '#157347' : 'currentColor') : '#adb5bd' }};">
                            </div>
                            @endfor
                        </div>
                        <div class="small mb-2 {{ $done ? 'text-success fw-bold' : 'text-muted' }}">
                            @if($done)
                                <i class="fas fa-check-circle me-1"></i>3/3 Complete
                            @else
                                {{ $pc['count'] }}/3 captures
                            @endif
                        </div>
                        @if($pc['avgHex'])
                        <div style="width:44px;height:24px;background:{{ $pc['avgHex'] }};border:1px solid #ccc;
                                    border-radius:4px;margin:0 auto 4px;"></div>
                        <div class="text-muted" style="font-size:10px;">{{ $pc['avgHex'] }}</div>
                        @endif
                    </div>
                    <div class="card-footer text-center py-2">
                        <a href="{{ $pc['route'] }}"
                           class="btn btn-sm {{ $done ? 'btn-outline-success' : 'btn-' . $pc['color'] }} w-100">
                            @if($done)
                                <i class="fas fa-redo me-1"></i>Re-test
                            @else
                                <i class="fas fa-camera me-1"></i>
                                {{ $pc['count'] > 0 ? 'Continue' : 'Start' }} Test
                            @endif
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="mb-3">
            <div class="d-flex justify-content-between small text-muted mb-1">
                <span>Overall Progress</span>
                <span>{{ $totalDone }}/12 captures</span>
            </div>
            <div class="progress" style="height:10px;">
                <div class="progress-bar bg-success"
                     style="width:{{ round($totalDone/12*100) }}%;transition:width .4s;"></div>
            </div>
        </div>

        @if($sample->allAveraged())
        <div class="alert alert-success py-2 mb-2">
            <i class="fas fa-check-circle me-1"></i>
            All 12 captures complete. Ready to compute the full soil analysis.
        </div>
        <a href="{{ route('samples.show', $sample) }}" class="btn btn-success w-100">
            <i class="fas fa-calculator me-1"></i>Compute &amp; View Results
        </a>
        @endif

    </div>
</div>
@endif

{{-- ── ANALYSIS RESULTS ────────────────────────────────────────────────────── --}}
@if($sample->isAnalyzed())
@php
$resultParams = [
    'ph'         => ['label'=>'Soil pH',       'value'=>$sample->ph_level,         'unit'=>'',    'hex'=>$sample->ph_color_hex],
    'nitrogen'   => ['label'=>'Nitrogen (N)',   'value'=>$sample->nitrogen_level,   'unit'=>'ppm', 'hex'=>$sample->nitrogen_color_hex],
    'phosphorus' => ['label'=>'Phosphorus (P)', 'value'=>$sample->phosphorus_level, 'unit'=>'ppm', 'hex'=>$sample->phosphorus_color_hex],
    'potassium'  => ['label'=>'Potassium (K)',  'value'=>$sample->potassium_level,  'unit'=>'ppm', 'hex'=>$sample->potassium_color_hex],
];
$fertilizerSvc = app(\App\Services\FertilizerService::class);
@endphp
<div class="row mb-4">
    <div class="col-12"><h4><i class="fas fa-chart-bar me-2"></i>Soil Analysis Results</h4></div>
    @foreach($resultParams as $key => $rp)
    @php
        $status = $fertilizerSvc->getNutrientStatus($key, (float)$rp['value']);
        $bsColor = match($status) {
            'Acidic', 'Low' => 'danger',
            'Medium'        => 'warning',
            'Optimal'       => 'success',
            'Alkaline'      => 'info',
            'High'          => 'primary',
            default         => 'secondary'
        };
    @endphp
    <div class="col-md-3 mb-3">
        <div class="card h-100 border-{{ $bsColor }}">
            <div class="card-header bg-{{ $bsColor }} text-white text-center py-2">
                <strong>{{ $rp['label'] }}</strong>
            </div>
            <div class="card-body text-center">
                <div class="display-6 fw-bold text-{{ $bsColor }}">
                    {{ number_format($rp['value'], 1) }}
                    <small class="fs-6">{{ $rp['unit'] }}</small>
                </div>
                <div class="my-2">
                    <div style="width:50px;height:25px;background:{{ $rp['hex'] }};border:1px solid #ccc;border-radius:4px;margin:0 auto;"></div>
                    <small class="text-muted">{{ $rp['hex'] }}</small>
                </div>
                <span class="badge bg-{{ $bsColor }}">{{ $status }}</span>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- FERTILIZER RECOMMENDATION --}}
@if(!empty($fertRec))
<div class="card mb-4">
    <div class="card-header bg-warning text-dark">
        <h5 class="mb-0">
            <i class="fas fa-spray-can me-2"></i>Fertilizer Recommendation
            <small class="fw-normal ms-2 text-muted" style="font-size:.75rem;">Based on BSWM/PhilRice guidelines (per hectare)</small>
        </h5>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <div class="card text-center h-100 {{ $fertRec['lime_tons'] > 0 ? 'border-danger' : 'border-secondary' }}">
                    <div class="card-body py-3">
                        <i class="fas fa-mountain fa-2x {{ $fertRec['lime_tons'] > 0 ? 'text-danger' : 'text-muted' }} mb-2"></i>
                        <div class="fw-bold fs-4 {{ $fertRec['lime_tons'] > 0 ? 'text-danger' : 'text-muted' }}">{{ number_format($fertRec['lime_tons'],1) }} t/ha</div>
                        <small class="text-muted">Dolomitic Lime</small>
                        <div style="font-size:10px;" class="text-muted mt-1">pH correction</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center h-100 border-success">
                    <div class="card-body py-3">
                        <i class="fas fa-seedling fa-2x text-success mb-2"></i>
                        <div class="fw-bold fs-4 text-success">{{ number_format($fertRec['urea_bags'],1) }} bags/ha</div>
                        <small class="text-muted">Urea (46-0-0)</small>
                        <div style="font-size:10px;" class="text-muted mt-1">Nitrogen source</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center h-100 border-primary">
                    <div class="card-body py-3">
                        <i class="fas fa-atom fa-2x text-primary mb-2"></i>
                        <div class="fw-bold fs-4 text-primary">{{ number_format($fertRec['tsp_bags'],1) }} bags/ha</div>
                        <small class="text-muted">TSP (0-46-0)</small>
                        <div style="font-size:10px;" class="text-muted mt-1">Phosphorus source</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center h-100 border-info">
                    <div class="card-body py-3">
                        <i class="fas fa-flask fa-2x text-info mb-2"></i>
                        <div class="fw-bold fs-4 text-info">{{ number_format($fertRec['mop_bags'],1) }} bags/ha</div>
                        <small class="text-muted">MOP (0-0-60)</small>
                        <div style="font-size:10px;" class="text-muted mt-1">Potassium source</div>
                    </div>
                </div>
            </div>
        </div>
        <ul class="list-group list-group-flush mb-4">
            @foreach($fertRec['notes'] as $note)
            <li class="list-group-item py-1">
                <i class="fas fa-circle-info text-warning me-2"></i>
                <small>{{ $note }}</small>
            </li>
            @endforeach
        </ul>

        {{-- ── Crop-Specific Fertilizer Calculator ─────────────────────── --}}
        <hr class="my-3">
        <h6 class="fw-bold mb-3">
            <i class="fas fa-calculator me-2 text-success"></i>
            Crop-Specific Fertilizer Calculator
            <small class="fw-normal text-muted ms-2" style="font-size:.75rem;">
                Adjusts requirements by crop and farm area
            </small>
        </h6>

        {{-- ── HOW THIS CALCULATOR WORKS info card ──────────────────────── --}}
        <div class="card border-info mb-4">
            <div class="card-header bg-info text-white py-2 d-flex align-items-center justify-content-between"
                 style="cursor:pointer;" onclick="toggleFormulaCard()">
                <span>
                    <i class="fas fa-square-root-alt me-2"></i>
                    <strong>How the threshold &amp; deficit formula works</strong>
                    <small class="ms-2 fw-normal opacity-75">Click to expand / collapse</small>
                </span>
                <i class="fas fa-chevron-down" id="formulaChevron"></i>
            </div>
            <div id="formulaCard" class="d-none">
                <div class="card-body pb-2">

                    {{-- Step 1: why ÷2 --}}
                    <h6 class="fw-bold text-info mb-2">
                        <i class="fas fa-database me-1"></i>
                        Step 1 — Why does the table show 45 ppm when the database stores 90?
                    </h6>
                    <p class="small mb-2">
                        Crop nutrient thresholds in the database are recorded in
                        <strong>kg of pure nutrient per hectare (kg/ha)</strong> — the unit
                        agronomists and fertilizer bags use in the field.
                        Your soil test result is in <strong>ppm (mg/kg)</strong> — the unit
                        a laboratory colorimeter reads.
                        To compare them, the system converts the DB threshold to ppm first:
                    </p>

                    <div class="bg-light border rounded p-3 mb-3 font-monospace small">
                        <div class="row g-0 align-items-center text-center">
                            <div class="col-auto px-2">
                                <div class="fw-bold text-dark">DB value</div>
                                <div class="display-6 fw-bold text-primary">90</div>
                                <div class="text-muted" style="font-size:10px;">kg/ha (stored)</div>
                            </div>
                            <div class="col-auto px-2 fs-4 text-muted">÷</div>
                            <div class="col-auto px-2">
                                <div class="fw-bold text-dark">Conversion</div>
                                <div class="display-6 fw-bold text-secondary">2</div>
                                <div class="text-muted" style="font-size:10px;">1 ppm ≈ 2 kg/ha</div>
                            </div>
                            <div class="col-auto px-2 fs-4 text-muted">=</div>
                            <div class="col-auto px-2">
                                <div class="fw-bold text-dark">Used in table</div>
                                <div class="display-6 fw-bold text-success">45</div>
                                <div class="text-muted" style="font-size:10px;">ppm (threshold)</div>
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="text-muted" style="font-size:11px;">
                            <strong>Why 1 ppm ≈ 2 kg/ha?</strong><br>
                            At a standard 0–15 cm plough depth, 1 hectare of soil weighs ~2,000,000 kg.<br>
                            1 ppm = 1 mg per kg of soil = 1 mg × 2,000,000 kg = 2,000,000 mg = <strong>2 kg per hectare</strong>.
                        </div>
                    </div>

                    {{-- Step 2: LMH classification --}}
                    <h6 class="fw-bold text-info mb-2">
                        <i class="fas fa-layer-group me-1"></i>
                        Step 2 — Reversed LMH classification (High soil = Low fertilizer need)
                    </h6>
                    <p class="small mb-2">
                        The DB stores three thresholds per nutrient per crop.
                        The column names look like "low/med/high" but they actually mean
                        <em>the soil level at which fertilizer need becomes low / medium / high</em>.
                        So <code>n_low = 90 kg/ha (45 ppm)</code> means:
                        <strong>"if soil N ≥ 45 ppm, the crop needs LOW fertilizer amendment."</strong>
                    </p>
                    <div class="table-responsive mb-3">
                        <table class="table table-bordered table-sm text-center align-middle mb-0" style="font-size:.82rem;">
                            <thead class="table-light">
                                <tr>
                                    <th>Condition</th>
                                    <th>Fertilizer Need</th>
                                    <th>Target used for deficit</th>
                                    <th>Meaning</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="table-success">
                                    <td>Soil N <strong>≥ n_low ÷ 2</strong> (e.g. ≥ 45 ppm)</td>
                                    <td><span class="badge bg-success">Low</span></td>
                                    <td>n_low ÷ 2 (45 ppm)</td>
                                    <td>Soil already sufficient — deficit ≈ 0</td>
                                </tr>
                                <tr class="table-warning">
                                    <td>Soil N <strong>≥ n_med ÷ 2</strong> (e.g. ≥ 30 ppm)</td>
                                    <td><span class="badge bg-warning text-dark">Medium</span></td>
                                    <td>n_low ÷ 2 (45 ppm)</td>
                                    <td>Moderate amendment — bring up to sufficiency</td>
                                </tr>
                                <tr class="table-danger">
                                    <td>Soil N <strong>&lt; n_med ÷ 2</strong> (e.g. &lt; 30 ppm)</td>
                                    <td><span class="badge bg-danger">High</span></td>
                                    <td>n_low ÷ 2 (45 ppm)</td>
                                    <td>Significant amendment — bring up to sufficiency</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Step 3: deficit formula --}}
                    <h6 class="fw-bold text-info mb-2">
                        <i class="fas fa-calculator me-1"></i>
                        Step 3 — Deficit &amp; fertilizer product formula
                    </h6>
                    <div class="bg-light border rounded p-3 mb-2" style="font-size:.82rem;">
                        <div class="mb-1">
                            <strong>1. Deficit (ppm)</strong>
                            <code class="ms-2">= max(0, target_ppm − soil_ppm)</code>
                        </div>
                        <div class="mb-1">
                            <strong>2. Pure nutrient deficit (kg/ha)</strong>
                            <code class="ms-2">= deficit_ppm × 2</code>
                        </div>
                        <div class="mb-1">
                            <strong>3. Fertilizer product needed (kg/ha)</strong>
                            <code class="ms-2">= pure_nutrient_kg_ha ÷ fertilizer_fraction</code>
                            <div class="text-muted ms-2 mt-1" style="font-size:10px;">
                                e.g. Urea fraction = 0.46 (46% N) → 74.8 kg N ÷ 0.46 = <strong>162.6 kg Urea/ha</strong>
                            </div>
                        </div>
                        <div>
                            <strong>4. If status = Low</strong>
                            <code class="ms-2">→ Fertilizer product = 0 (soil already sufficient)</code>
                        </div>
                    </div>

                    {{-- Worked example --}}
                    <h6 class="fw-bold text-info mb-2">
                        <i class="fas fa-seedling me-1"></i>
                        Worked example — Garlic, Nitrogen
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0" style="font-size:.82rem;">
                            <tbody>
                                <tr><td class="text-muted" style="width:40%">DB threshold <code>n_low</code></td><td><strong>90 kg/ha</strong></td></tr>
                                <tr><td class="text-muted">Converted to ppm (÷ 2)</td><td><strong>45 ppm</strong> ← what the table shows</td></tr>
                                <tr><td class="text-muted">Soil N reading</td><td><strong>7.60 ppm</strong></td></tr>
                                <tr><td class="text-muted">Classification</td><td>7.60 &lt; 30 ppm → <span class="badge bg-danger">High</span> need</td></tr>
                                <tr><td class="text-muted">Deficit (ppm)</td><td>45 − 7.60 = <strong>37.40 ppm</strong></td></tr>
                                <tr><td class="text-muted">Pure nutrient deficit (kg/ha)</td><td>37.40 × 2 = <strong>74.80 kg N/ha</strong></td></tr>
                                <tr><td class="text-muted">Urea needed (kg/ha)</td><td>74.80 ÷ 0.46 = <strong>162.6 kg Urea/ha</strong></td></tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
        {{-- ── END formula card ─────────────────────────────────────────── --}}

        <form id="fertilizerForm" onsubmit="return false;">
            <div class="row g-3">

                {{-- Left: inputs --}}
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Crop</label>
                        <select class="form-select" id="cropSelect">
                            <option value="">— Select a crop —</option>
                            @foreach($allCrops as $crop)
                            <option value="{{ $crop->id }}">{{ $crop->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Farm Area (hectares)</label>
                        <input type="number" class="form-control" id="areaSize"
                               step="0.01" min="0.01" value="1.00">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Primary Fertilizer Type</label>
                        <select class="form-select" id="fertilizerType">
                            <option value="urea">Urea (46-0-0)</option>
                            <option value="complete">Complete (14-14-14)</option>
                            <option value="ammonium_sulfate">Ammonium Sulfate (21-0-0)</option>
                            <option value="dap">DAP (18-46-0)</option>
                            <option value="mop">Muriate of Potash (0-0-60)</option>
                            <option value="organic">Organic Fertilizer (~2-1.5-1)</option>
                        </select>
                    </div>
                    <button type="button" class="btn btn-success w-100" onclick="calculateFertilizer()">
                        <i class="fas fa-calculator me-1"></i> Calculate Fertilizer Requirement
                    </button>
                </div>

                {{-- Right: current soil status --}}
                <div class="col-md-6">
                    <p class="fw-semibold mb-2">Current Soil Status</p>
                    <table class="table table-bordered table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Parameter</th>
                                <th class="text-center">Value</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                            // Values stored as ppm; display as ppm to match Soil Analysis Results
                            $calcParams = [
                                ['key'=>'ph',         'label'=>'Soil pH',   'value'=>(float)$sample->ph_level,                         'unit'=>''],
                                ['key'=>'nitrogen',   'label'=>'Nitrogen',  'value'=>(float)$sample->nitrogen_level,                   'unit'=>' kg/ha'],
                                ['key'=>'phosphorus', 'label'=>'Phosphorus','value'=>(float)$sample->phosphorus_level,                 'unit'=>' kg/ha'],
                                ['key'=>'potassium',  'label'=>'Potassium', 'value'=>(float)$sample->potassium_level,                  'unit'=>' kg/ha'],
                            ];
                            @endphp
                            @foreach($calcParams as $cp)
                            @php
                            $displayVal = $cp['key'] !== 'ph' ? (float)$cp['value'] : (float)$cp['value'];
                            $st   = $fertilizerSvc->getNutrientStatus($cp['key'], $displayVal);
                            $stBg = match($st) {
                                'Acidic','Low' => 'danger',
                                'Medium'       => 'warning',
                                'Optimal'      => 'success',
                                'Alkaline'     => 'info',
                                'High'         => 'primary',
                                default        => 'secondary',
                            };
                            @endphp
                            <tr>
                                <td>{{ $cp['label'] }}</td>
                                <td class="text-center fw-bold">{{ number_format($displayVal * 2, 1) }}{{ $cp['unit'] }}</td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $stBg }}">{{ $st }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="mt-2 p-2 rounded bg-light small text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        DB thresholds stored in <strong>kg/ha</strong>, divided by 2 to get <strong>ppm</strong> for comparison.
                        High soil = Low fertilizer need. Bags are 50 kg each.
                    </div>
                </div>
            </div>

            {{-- Results panel --}}
            <div id="calcResults" class="d-none mt-4">
                <hr class="mb-3">
                <h6 class="fw-bold mb-3" id="calcResultsTitle"></h6>
                <div class="row g-3" id="calcResultsCards"></div>
                <div class="alert mt-3 mb-0" id="calcResultsAlert"></div>
            </div>
        </form>

    </div>
</div>
@endif

{{-- ── CROP RECOMMENDATIONS ────────────────────────────────────────────────── --}}
<div class="card mb-4">
    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-seedling me-2"></i>Crop Recommendations</h5>
        @if($sample->isAnalyzed())
        <a href="{{ route('samples.pdf', $sample) }}" target="_blank" class="btn btn-sm btn-light">
            <i class="fas fa-print me-1"></i>Print / Save as PDF
        </a>
        @endif
    </div>
    <div class="card-body">
        @if(!$sample->isAnalyzed())
            <p class="text-muted mb-0">Complete all 4 soil tests to see crop recommendations.</p>
        @else

        {{-- Legend --}}
        <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
            <small class="text-muted me-1"><i class="fas fa-circle-info me-1"></i>Fertilizer need:</small>
            <span class="badge bg-success">Low</span><small class="text-muted">Soil is sufficient</small>
            <span class="badge bg-warning text-dark">Medium</span><small class="text-muted">Minor amendment</small>
            <span class="badge bg-danger">High</span><small class="text-muted">Significant amendment needed</small>
            <span class="ms-2 badge bg-secondary">N/A</span><small class="text-muted">No data</small>
        </div>

        {{-- Score + formula info --}}
        <div class="alert alert-light border py-2 mb-3 small">
            <i class="fas fa-info-circle text-info me-1"></i>
            <strong>Overall Score</strong> = how many of the 4 parameters (pH, N, P, K) are already sufficient.
            Status thresholds are stored in <strong>kg/ha</strong> in the database and converted to
            <strong>ppm (÷ 2)</strong> before comparing with your soil reading
            — e.g. <code>n_low = 90 kg/ha → 45 ppm</code>.
            A <strong>Low</strong> badge means no amendment required for that nutrient.
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle mb-0" id="cropRecoTable">
                <thead class="table-success">
                    <tr>
                        <th style="cursor:pointer;" onclick="sortCropTable(0)"># <i class="fas fa-sort ms-1 text-muted" style="font-size:.7rem;"></i></th>
                        <th style="cursor:pointer;" onclick="sortCropTable(1)">Crop <i class="fas fa-sort ms-1 text-muted" style="font-size:.7rem;"></i></th>
                        <th class="text-center text-nowrap" style="cursor:pointer;" onclick="sortCropTable(2)">pH Status <i class="fas fa-sort ms-1 text-muted" style="font-size:.7rem;"></i></th>
                        <th class="text-center text-nowrap" style="cursor:pointer;" onclick="sortCropTable(3)">N Status <i class="fas fa-sort ms-1 text-muted" style="font-size:.7rem;"></i></th>
                        <th class="text-center text-nowrap" style="cursor:pointer;" onclick="sortCropTable(4)">P Status <i class="fas fa-sort ms-1 text-muted" style="font-size:.7rem;"></i></th>
                        <th class="text-center text-nowrap" style="cursor:pointer;" onclick="sortCropTable(5)">K Status <i class="fas fa-sort ms-1 text-muted" style="font-size:.7rem;"></i></th>
                        <th class="text-center text-nowrap" style="cursor:pointer;" onclick="sortCropTable(6)">Overall Score <i class="fas fa-sort ms-1 text-muted" style="font-size:.7rem;"></i></th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $ph    = (float) $sample->ph_level;
                        $soilN = (float) $sample->nitrogen_level;
                        $soilP = (float) $sample->phosphorus_level;
                        $soilK = (float) $sample->potassium_level;

                        $statusColor = function(string $s): string {
                            return match($s) {
                                'Low'    => 'success',
                                'Medium' => 'warning',
                                'High'   => 'danger',
                                default  => 'secondary',
                            };
                        };

                        $scoredCrops = [];
                        foreach ($allCrops as $crop) {
                            $fert = $cropFertData[$crop->id] ?? null;

                            // pH: within range = Low, near midpoint ±0.8 = Medium, else High
                            $phMid = (($crop->ph_low ?? 6) + ($crop->ph_high ?? 7)) / 2;
                            if ($ph >= ($crop->ph_low ?? 0) && $ph <= ($crop->ph_high ?? 14)) {
                                $phSt = 'Low';
                            } elseif (abs($ph - $phMid) <= 0.8) {
                                $phSt = 'Medium';
                            } else {
                                $phSt = 'High';
                            }

                            $nSt = $fert['n']['status'] ?? 'N/A';
                            $pSt = $fert['p']['status'] ?? 'N/A';
                            $kSt = $fert['k']['status'] ?? 'N/A';

                            $score = count(array_filter([$phSt, $nSt, $pSt, $kSt], fn($s) => $s === 'Low'));

                            $scoredCrops[] = [
                                'crop'  => $crop,
                                'phSt'  => $phSt,
                                'nSt'   => $nSt,
                                'pSt'   => $pSt,
                                'kSt'   => $kSt,
                                'score' => $score,
                                'fert'  => $fert,
                            ];
                        }
                        usort($scoredCrops, fn($a, $b) => $b['score'] <=> $a['score']);
                    @endphp

                    @foreach($scoredCrops as $i => $row)
                    @php
                        $pct      = round($row['score'] / 4 * 100);
                        $barColor = $pct >= 75 ? 'success' : ($pct >= 50 ? 'warning' : ($pct >= 25 ? 'info' : 'danger'));

                        $highNeeds = [];
                        $medNeeds  = [];
                        if ($row['phSt'] === 'High')   $highNeeds[] = 'pH';
                        if ($row['nSt']  === 'High')   $highNeeds[] = 'Nitrogen';
                        if ($row['pSt']  === 'High')   $highNeeds[] = 'Phosphorus';
                        if ($row['kSt']  === 'High')   $highNeeds[] = 'Potassium';
                        if ($row['phSt'] === 'Medium') $medNeeds[]  = 'pH';
                        if ($row['nSt']  === 'Medium') $medNeeds[]  = 'Nitrogen';
                        if ($row['pSt']  === 'Medium') $medNeeds[]  = 'Phosphorus';
                        if ($row['kSt']  === 'Medium') $medNeeds[]  = 'Potassium';

                        if ($row['score'] === 4) {
                            $remark = 'Excellent soil match — no amendments needed.';
                        } elseif (!empty($highNeeds) && !empty($medNeeds)) {
                            $remark = 'Significant ' . implode(', ', $highNeeds) . ' amendment needed; moderate ' . implode(', ', $medNeeds) . ' adjustment recommended.';
                        } elseif (!empty($highNeeds)) {
                            $remark = 'Significant ' . implode(', ', $highNeeds) . ' amendment needed before planting.';
                        } elseif (!empty($medNeeds)) {
                            $remark = 'Moderate ' . implode(', ', $medNeeds) . ' adjustment recommended.';
                        } else {
                            $remark = 'Soil is well-suited; minor fine-tuning may improve yield.';
                        }
                    @endphp
                    <tr class="{{ $i === 0 ? 'table-warning' : '' }}">
                        <td class="text-muted small">{{ $i + 1 }}</td>
                        <td>
                            <strong>{{ $row['crop']->name }}</strong>
                            @if($i === 0)
                                <span class="badge bg-warning text-dark ms-1">
                                    <i class="fas fa-star me-1" style="font-size:.65rem;"></i>Top Pick
                                </span>
                            @elseif($i < 3)
                                <span class="badge bg-success ms-1" style="font-size:.65rem;">Recommended</span>
                            @endif
                        </td>
                        <td class="text-center"><span class="badge bg-{{ $statusColor($row['phSt']) }}">{{ $row['phSt'] }}</span></td>
                        <td class="text-center"><span class="badge bg-{{ $statusColor($row['nSt']) }}">{{ $row['nSt'] }}</span></td>
                        <td class="text-center"><span class="badge bg-{{ $statusColor($row['pSt']) }}">{{ $row['pSt'] }}</span></td>
                        <td class="text-center"><span class="badge bg-{{ $statusColor($row['kSt']) }}">{{ $row['kSt'] }}</span></td>
                        <td class="text-center" style="min-width:110px;">
                            <div class="d-flex align-items-center gap-1">
                                <div class="progress flex-grow-1" style="height:8px;">
                                    <div class="progress-bar bg-{{ $barColor }}"
                                         style="width:{{ $pct }}%;transition:width .4s;"></div>
                                </div>
                                <small class="text-muted fw-bold" style="min-width:28px;">{{ $pct }}%</small>
                            </div>
                        </td>
                        <td class="small text-muted">{{ $remark }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-2 text-muted small">
            <i class="fas fa-circle-info me-1"></i>
            Sorted by highest overall score. Thresholds: DB kg/ha ÷ 2 = ppm used for comparison.
            <strong>Low</strong> = soil sufficient, <strong>High</strong> = significant fertilizer needed.
        </div>
        @endif
    </div>
</div>

{{-- ── GEMINI AI CROP RECOMMENDATIONS ─────────────────────────────────── --}}
<div class="card mb-4" id="geminiSection">
    <div class="card-header d-flex justify-content-between align-items-center"
         style="background:linear-gradient(135deg,#1a73e8 0%,#34a853 60%,#fbbc04 100%);color:#fff;">
        <h5 class="mb-0">
            <i class="fas fa-robot me-2"></i>
            Gemini AI — Philippine Crop Recommendations
        </h5>
        @if($geminiEnabled)
            <span class="badge bg-light text-dark">
                <i class="fas fa-circle text-success me-1" style="font-size:.6rem;"></i>Gemini Ready
            </span>
        @else
            <span class="badge bg-secondary">
                <i class="fas fa-circle me-1" style="font-size:.6rem;"></i>API Not Configured
            </span>
        @endif
    </div>
    <div class="card-body">
        @if(!$sample->isAnalyzed())
            <p class="text-muted mb-0">Complete all 4 soil tests first to enable Gemini crop recommendations.</p>
        @elseif(!$geminiEnabled)
            <div class="alert alert-warning mb-3">
                <h6 class="alert-heading mb-2"><i class="fas fa-key me-2"></i>Gemini API Key Required</h6>
                <p class="mb-2">This feature uses <strong>Google Gemini AI</strong> to recommend up to 10 suitable Philippine crops grouped by season and crop type, with per-crop fertilizer calculations.</p>
                <ol class="mb-2 small">
                    <li>Create an account at <strong>aistudio.google.com</strong></li>
                    <li>Generate an API key from Google AI Studio</li>
                    <li>Add <code>GEMINI_API_KEY=your-key-here</code> to the server's <code>.env</code> file</li>
                    <li>Restart the application server</li>
                </ol>
                <hr class="my-2">
                <p class="mb-0 small text-muted">
                    <i class="fas fa-shield-alt me-1"></i>
                    The API key is stored server-side only and is <strong>never sent to the browser</strong>.
                </p>
            </div>
            <button class="btn btn-secondary" disabled>
                <i class="fas fa-seedling me-1"></i> Get Gemini Crop Recommendations
                <span class="ms-2 badge bg-light text-dark" style="font-size:.7rem;">Not Available</span>
            </button>
        @else
            <p class="text-muted mb-3">
                Gemini AI will recommend up to <strong>10 Philippine crops</strong> suited to your soil,
                grouped by <em>season</em> (Wet / Dry / Year-round) and <em>crop type</em>
                (Grain, Vegetable, Root Crop, Legume, Fruit, Cash Crop) — each with a tailored fertilizer plan.
            </p>
            <div class="card border-0 bg-light p-3 mb-3">
                <label class="form-label fw-semibold mb-1">
                    <i class="fas fa-hand-pointer me-1 text-success"></i>
                    Farmer's Preferred Crop <span class="text-muted fw-normal">(optional)</span>
                </label>
                <div class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <select class="form-select" id="geminiPreferredCrop">
                            <option value="">— No preference, recommend best crops —</option>
                            @foreach($allCrops as $crop)
                                <option value="{{ $crop->name }}">{{ $crop->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-7 text-muted small">
                        <i class="fas fa-info-circle me-1"></i>
                        Select a crop if the farmer has a specific one in mind. Gemini will assess its
                        soil compatibility and suggest amendments if needed.
                    </div>
                </div>
            </div>
            @if(!empty($sample->gemini_crop_recommendation))
            <div id="geminiStoredResult" class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-semibold text-success">
                        <i class="fas fa-check-circle me-1"></i> Gemini Recommendation (saved)
                    </span>
                    <button class="btn btn-sm btn-outline-primary" onclick="generateGemini()" id="geminiRegenerateBtn">
                        <i class="fas fa-sync me-1"></i> Regenerate
                    </button>
                </div>
                <div id="geminiRecommendationText"
                     class="p-3 rounded border bg-white"
                     style="white-space:pre-wrap;font-size:.92rem;line-height:1.6;">{{ $sample->gemini_crop_recommendation }}</div>
            </div>
            @endif
            <div id="geminiGenerateArea" class="{{ !empty($sample->gemini_crop_recommendation) ? 'd-none' : '' }}">
                <button class="btn btn-lg"
                        style="background:linear-gradient(135deg,#1a73e8,#34a853);color:#fff;"
                        onclick="generateGemini()" id="geminiBtn">
                    <i class="fas fa-seedling me-2"></i>Get Gemini Crop Recommendations
                </button>
            </div>
            <div id="geminiLoading" class="d-none mt-3">
                <div class="spinner-border spinner-border-sm me-2" style="color:#1a73e8;"></div>
                Consulting Gemini AI — analyzing soil profile and matching Philippine crops...
            </div>
            <div id="geminiResult" class="mt-3 p-3 rounded border bg-white d-none"
                 style="white-space:pre-wrap;font-size:.92rem;line-height:1.6;"></div>
            <div id="geminiError" class="alert alert-danger mt-3 d-none"></div>
        @endif
    </div>
</div>

<div class="row mb-4">
    <div class="col text-end">
        <a href="{{ route('samples.report', $sample) }}" class="btn btn-outline-info">
            <i class="fas fa-microscope me-1"></i> Test Report
        </a>
        <a href="{{ route('samples.reset', $sample) }}"
           class="btn btn-outline-warning ms-2"
           onclick="return confirm('This will reset ALL readings for this sample. Continue?');">
            <i class="fas fa-redo me-1"></i> Re-capture All
        </a>
        @if(Auth::user()->isAdmin())
        <button type="button" class="btn btn-danger ms-2"
                data-bs-toggle="modal" data-bs-target="#deleteSampleModal">
            <i class="fas fa-trash me-1"></i> Delete Sample
        </button>
        @endif
        <a href="{{ route('export', ['sample_id' => $sample->id]) }}" class="btn btn-success ms-2">
            <i class="fas fa-file-excel me-1"></i> Export to Excel
        </a>
        <a href="{{ route('samples.create') }}" class="btn btn-primary ms-2">
            <i class="fas fa-plus-circle me-1"></i> New Sample
        </a>
    </div>
</div>
@endif

{{-- ── Delete Sample Modal (admin only) ───────────────────────── --}}
@if(Auth::user()->isAdmin())
<div class="modal fade" id="deleteSampleModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-danger">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>Delete Sample Permanently
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('samples.destroy', $sample) }}">
                @csrf
                @method('DELETE')
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <strong>This cannot be undone.</strong> Deleting this sample will permanently remove:
                        <ul class="mb-0 mt-1 small">
                            <li>All soil test readings (pH, N, P, K)</li>
                            <li>All captured webcam photos</li>
                            <li>The analysis result and fertilizer recommendation</li>
                        </ul>
                    </div>
                    <p class="mb-1">Sample to delete:</p>
                    <div class="p-2 rounded bg-light border mb-3 fw-semibold">
                        {{ $sample->sample_name }} — {{ $sample->farmer_name }}
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Enter your admin password to confirm:</label>
                        <input type="password" class="form-control" name="admin_password"
                               placeholder="Admin password" autocomplete="current-password" required>
                        @if(session('error'))
                        <div class="text-danger small mt-1">{{ session('error') }}</div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i> Delete Permanently
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection

@section('scripts')
@if($sample->isAnalyzed())
<script>
const CROP_FERT_DATA = @json($cropFertData ?? []);

const FERT_TYPE = {
    urea:             { n: 0.46, p: 0,     k: 0,    name: 'Urea (46-0-0)',                suppP: 'TSP (0-46-0)', suppK: 'MOP (0-0-60)' },
    complete:         { n: 0.14, p: 0.14,  k: 0.14, name: 'Complete (14-14-14)',           suppP: null,           suppK: null },
    ammonium_sulfate: { n: 0.21, p: 0,     k: 0,    name: 'Ammonium Sulfate (21-0-0)',    suppP: 'TSP (0-46-0)', suppK: 'MOP (0-0-60)' },
    dap:              { n: 0.18, p: 0.46,  k: 0,    name: 'DAP (18-46-0)',                suppP: null,           suppK: 'MOP (0-0-60)' },
    mop:              { n: 0,    p: 0,     k: 0.60, name: 'Muriate of Potash (0-0-60)',    suppP: 'TSP (0-46-0)', suppK: null },
    organic:          { n: 0.02, p: 0.015, k: 0.01, name: 'Organic Fertilizer (~2-1.5-1)', suppP: null,          suppK: null },
};

const SUPP_TSP_FRACTION = 0.46;
const SUPP_MOP_FRACTION = 0.60;
const SOIL_PH = {{ (float)$sample->ph_level }};

// ── Formula card toggle ────────────────────────────────────────────────────
function toggleFormulaCard() {
    const card    = document.getElementById('formulaCard');
    const chevron = document.getElementById('formulaChevron');
    const hidden  = card.classList.toggle('d-none');
    chevron.className = hidden ? 'fas fa-chevron-down' : 'fas fa-chevron-up';
}

// ── Fertilizer Calculator ──────────────────────────────────────────────────
function calculateFertilizer() {
    const cropId = document.getElementById('cropSelect').value;
    const area   = parseFloat(document.getElementById('areaSize').value) || 1;
    const fType  = document.getElementById('fertilizerType').value;

    if (!cropId) { alert('Please select a crop first.'); return; }

    const npk  = CROP_FERT_DATA[cropId];
    const fert = FERT_TYPE[fType];
    if (!npk) { alert('Fertilizer data not available for this crop.'); return; }

    const fmt = n => Number(n).toFixed(2);

    // Pure nutrient deficit kg/ha (from server-computed deficit_ppm × 2)
    const defNKgHa = npk.n.deficit_ppm * 2;
    const defPKgHa = npk.p.deficit_ppm * 2;
    const defKKgHa = npk.k.deficit_ppm * 2;

    // Primary fertilizer kg/ha driven by most limiting nutrient
    let primaryKgHa = 0, limitedBy = '';
    if (fert.n > 0 && fert.p > 0 && fert.k > 0) {
        const byN = defNKgHa / fert.n;
        const byP = defPKgHa / fert.p;
        const byK = defKKgHa / fert.k;
        primaryKgHa = Math.max(byN, byP, byK);
        if (primaryKgHa === byN)      limitedBy = 'Nitrogen';
        else if (primaryKgHa === byP) limitedBy = 'Phosphorus';
        else                          limitedBy = 'Potassium';
    } else if (fert.n > 0) { primaryKgHa = defNKgHa / fert.n; limitedBy = 'Nitrogen'; }
    else if (fert.p > 0)   { primaryKgHa = defPKgHa / fert.p; limitedBy = 'Phosphorus'; }
    else if (fert.k > 0)   { primaryKgHa = defKKgHa / fert.k; limitedBy = 'Potassium'; }

    const primaryKgTotal = primaryKgHa * area;

    // Supplemental
    const suppPKgHa = (fert.p === 0 && defPKgHa > 0) ? defPKgHa / SUPP_TSP_FRACTION : 0;
    const suppKKgHa = (fert.k === 0 && defKKgHa > 0) ? defKKgHa / SUPP_MOP_FRACTION : 0;

    // Fertilizer product kg/ha per nutrient — zero if status is Low (sufficient)
    const nFertProdKgHa = npk.n.status !== 'Low' && defNKgHa > 0
        ? (fert.n > 0 ? defNKgHa / fert.n : defNKgHa / 0.46) : 0;
    const pFertProdKgHa = npk.p.status !== 'Low' && defPKgHa > 0
        ? (fert.p > 0 ? defPKgHa / fert.p : defPKgHa / 0.46) : 0;
    const kFertProdKgHa = npk.k.status !== 'Low' && defKKgHa > 0
        ? (fert.k > 0 ? defKKgHa / fert.k : defKKgHa / 0.60) : 0;

    // Lime
    let limeTons = 0, limeNote = '';
    if      (SOIL_PH < 5.0) { limeTons = 2.0; limeNote = 'Strongly acidic (pH < 5.0) — apply 2 t/ha dolomitic lime before planting.'; }
    else if (SOIL_PH < 5.5) { limeTons = 1.0; limeNote = 'Moderately acidic (pH 5.0–5.5) — apply 1 t/ha dolomitic lime.'; }
    else if (SOIL_PH > 7.5) { limeNote = 'Alkaline soil (pH > 7.5) — consider organic matter or elemental sulfur to lower pH.'; }

    // Summary cards
    let cards = '';
    if (limeTons > 0) {
        cards += card('fa-mountain', 'danger', 'Dolomitic Lime',
            `${fmt(limeTons * area)} tonnes`, `${fmt(limeTons)} t/ha × ${fmt(area)} ha`, 'pH correction');
    }
    cards += card('fa-seedling', 'success', fert.name,
        `${fmt(primaryKgTotal)} kg`, `${fmt(primaryKgHa)} kg/ha × ${fmt(area)} ha`,
        `Limited by: ${limitedBy || 'N/A'}`);
    if (suppPKgHa > 0) {
        cards += card('fa-atom', 'primary', 'TSP (0-46-0) — Supp. P',
            `${fmt(suppPKgHa * area)} kg`, `${fmt(suppPKgHa)} kg/ha × ${fmt(area)} ha`, 'Phosphorus supplement');
    }
    if (suppKKgHa > 0) {
        cards += card('fa-flask', 'info', 'MOP (0-0-60) — Supp. K',
            `${fmt(suppKKgHa * area)} kg`, `${fmt(suppKKgHa)} kg/ha × ${fmt(area)} ha`, 'Potassium supplement');
    }

    // Status badge helper
    const statusBadge = s => {
        const map = { Low: 'success', Medium: 'warning', High: 'danger' };
        return `<span class="badge bg-${map[s] ?? 'secondary'}">${s}</span>`;
    };

    // Deficit detail table
    const rows = [
        { label: 'Nitrogen (N)',   d: npk.n, defKgHa: defNKgHa, fertProdKgHa: nFertProdKgHa,
          fertName: fert.n > 0 ? fert.name : 'Urea (46-0-0)' },
        { label: 'Phosphorus (P)', d: npk.p, defKgHa: defPKgHa, fertProdKgHa: pFertProdKgHa,
          fertName: fert.p > 0 ? fert.name : 'TSP (0-46-0)' },
        { label: 'Potassium (K)',  d: npk.k, defKgHa: defKKgHa, fertProdKgHa: kFertProdKgHa,
          fertName: fert.k > 0 ? fert.name : 'MOP (0-0-60)' },
    ];

    cards += `
    <div class="col-12 mt-3">
        <div class="card border-info mb-0">
            <div class="card-header bg-info text-white py-2 small">
                <i class="fas fa-square-root-alt me-1"></i>
                <strong>Deficit breakdown</strong>
                <span class="ms-2 opacity-75">
                    DB threshold (kg/ha) ÷ 2 = ppm threshold used for comparison
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle mb-0" style="font-size:.82rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Nutrient</th>
                                <th class="text-center">Current Soil Nutrients<br><small class="text-muted">(kg/ha)</small></th>
                                <th class="text-center">Crop Target<br><small class="text-muted">(kg/ha stored)</small></th>
                                <th class="text-center d-none">Threshold<br><small class="text-muted">(÷2 = ppm)</small></th>
                                <th class="text-center d-none">Deficit<br><small class="text-muted">(ppm)</small></th>
                                <th class="text-center d-none">Pure Nutrient<br><small class="text-muted">deficit (kg/ha)</small></th>
                                <th class="text-center">Fertilizer Product<br><small class="text-muted">needed (kg/ha)</small></th>
                                <th class="text-center">Need</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rows.map(r => `
                            <tr>
                                <td class="fw-semibold">${r.label}</td>
                                <td class="text-center">${fmt(r.d.soil_ppm * 2)}</td>
                                <td class="text-center text-primary fw-bold">
                                    ${fmt(r.d.target_ppm * 2)}
                                    <div class="text-muted fw-normal" style="font-size:10px;">in DB</div>
                                </td>
                                <td class="text-center d-none text-info fw-bold">
                                    ${fmt(r.d.target_ppm)}
                                    <div class="text-muted fw-normal" style="font-size:10px;">${fmt(r.d.target_ppm * 2)} ÷ 2</div>
                                </td>
                                <td class="text-center fw-bold d-none ${r.d.deficit_ppm > 0 ? 'text-danger' : 'text-success'}">
                                    ${fmt(r.d.deficit_ppm)}
                                </td>
                                <td class="text-center d-none text-muted">
                                    ${fmt(r.defKgHa)}
                                    <div style="font-size:10px;">${fmt(r.d.deficit_ppm)} × 2</div>
                                </td>
                                <td class="text-center fw-bold ${r.fertProdKgHa > 0 ? 'text-danger' : 'text-success'}">
                                    ${r.fertProdKgHa > 0
                                        ? `${fmt(r.fertProdKgHa)} kg
                                           <div class="text-muted fw-normal" style="font-size:10px;">
                                               ${fmt(r.defKgHa)} ÷ frac. via ${r.fertName}
                                           </div>`
                                        : `0 <div class="text-success fw-normal" style="font-size:10px;">Sufficient</div>`
                                    }
                                </td>
                                <td class="text-center">${statusBadge(r.d.status)}</td>
                            </tr>`).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>`;

    // Render
    const cropLabel = document.getElementById('cropSelect').selectedOptions[0].text;
    document.getElementById('calcResultsTitle').innerHTML =
        `<i class="fas fa-check-circle text-success me-1"></i>` +
        `Result for <strong>${cropLabel}</strong> — ` +
        `<strong>${fmt(area)} ha</strong> using <strong>${fert.name}</strong>`;
    document.getElementById('calcResultsCards').innerHTML = cards;

    const alertEl = document.getElementById('calcResultsAlert');
    alertEl.className = 'alert mt-3 mb-0 alert-' + (limeNote
        ? (SOIL_PH < 5.0 ? 'danger' : (SOIL_PH < 5.5 ? 'warning' : 'info'))
        : 'success');
    alertEl.innerHTML = limeNote
        ? `<i class="fas fa-exclamation-triangle me-1"></i>${limeNote}`
        : `<i class="fas fa-check-circle me-1"></i>Soil pH (${SOIL_PH}) is acceptable — no lime amendment needed.`;

    document.getElementById('calcResults').classList.remove('d-none');
    document.getElementById('calcResults').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function card(icon, color, title, value, sub, footnote) {
    return `
    <div class="col-md-3 col-sm-6">
        <div class="card text-center h-100 border-${color}">
            <div class="card-body py-3">
                <i class="fas ${icon} fa-2x text-${color} mb-2"></i>
                <div class="fw-bold fs-5 text-${color}">${value}</div>
                <div class="fw-semibold small">${title}</div>
                <div class="text-muted" style="font-size:11px;">${sub}</div>
                <div class="text-muted" style="font-size:10px;">${footnote}</div>
            </div>
        </div>
    </div>`;
}

// ── Crop Table Sort ────────────────────────────────────────────────────────
let cropSortDir = {};
function sortCropTable(colIndex) {
    const table = document.getElementById('cropRecoTable');
    if (!table) return;
    const tbody = table.querySelector('tbody');
    const rows  = Array.from(tbody.querySelectorAll('tr'));
    const dir   = (cropSortDir[colIndex] === 'asc') ? 'desc' : 'asc';
    cropSortDir  = {};
    cropSortDir[colIndex] = dir;

    const statusOrder = { Low: 0, Medium: 1, High: 2, 'N/A': 3 };

    rows.sort((a, b) => {
        const aCell = a.cells[colIndex]?.textContent.trim() ?? '';
        const bCell = b.cells[colIndex]?.textContent.trim() ?? '';
        if (colIndex === 0 || colIndex === 6) {
            return dir === 'asc'
                ? (parseFloat(aCell)||0) - (parseFloat(bCell)||0)
                : (parseFloat(bCell)||0) - (parseFloat(aCell)||0);
        }
        if ([2,3,4,5].includes(colIndex)) {
            const aO = statusOrder[aCell] ?? 9, bO = statusOrder[bCell] ?? 9;
            return dir === 'asc' ? aO - bO : bO - aO;
        }
        return dir === 'asc' ? aCell.localeCompare(bCell) : bCell.localeCompare(aCell);
    });

    rows.forEach((r, i) => { r.cells[0].textContent = i + 1; tbody.appendChild(r); });
}

// ── Gemini ────────────────────────────────────────────────────────────────
let geminiInFlight = false;
function generateGemini() {
    if (geminiInFlight) return;
    geminiInFlight = true;
    const btn          = document.getElementById('geminiBtn');
    const regenBtn     = document.getElementById('geminiRegenerateBtn');
    const loading      = document.getElementById('geminiLoading');
    const result       = document.getElementById('geminiResult');
    const errDiv       = document.getElementById('geminiError');
    const storedDiv    = document.getElementById('geminiStoredResult');
    const generateArea = document.getElementById('geminiGenerateArea');
    const cropSelect   = document.getElementById('geminiPreferredCrop');
    if (btn)     btn.disabled = true;
    if (regenBtn) regenBtn.disabled = true;
    if (loading) loading.classList.remove('d-none');
    if (result)  result.classList.add('d-none');
    if (errDiv)  errDiv.classList.add('d-none');
    fetch('{{ route("gemini-crop-recommendations.generate") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ sample_id: {{ $sample->id }}, preferred_crop: cropSelect?.value.trim() || null })
    })
    .then(r => r.json())
    .then(data => {
        if (loading) loading.classList.add('d-none');
        if (data.success) {
            if (result) { result.textContent = data.recommendation; result.classList.remove('d-none'); }
            const storedText = document.getElementById('geminiRecommendationText');
            if (storedText) { storedText.textContent = data.recommendation; if (storedDiv) storedDiv.classList.remove('d-none'); }
            if (generateArea) generateArea.classList.add('d-none');
            setTimeout(() => {
                const target = result && !result.classList.contains('d-none') ? result
                             : storedDiv && !storedDiv.classList.contains('d-none') ? storedDiv : null;
                if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 50);
        } else {
            if (errDiv) { errDiv.textContent = 'Gemini Error: ' + data.message; errDiv.classList.remove('d-none'); }
        }
        if (btn)     btn.disabled = false;
        if (regenBtn) regenBtn.disabled = false;
        geminiInFlight = false;
    })
    .catch(() => {
        if (loading) loading.classList.add('d-none');
        if (errDiv) { errDiv.textContent = 'Network error contacting Gemini AI service.'; errDiv.classList.remove('d-none'); }
        if (btn)     btn.disabled = false;
        if (regenBtn) regenBtn.disabled = false;
        geminiInFlight = false;
    });
}
</script>
@endif
@endsection