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

                        {{-- Capture progress dots --}}
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

                        {{-- Average color swatch --}}
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

        {{-- Overall progress bar --}}
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

        {{-- Compute button --}}
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
                            $calcParams = [
                                ['key'=>'ph',         'label'=>'Soil pH',   'value'=>$sample->ph_level,         'unit'=>''],
                                ['key'=>'nitrogen',   'label'=>'Nitrogen',  'value'=>$sample->nitrogen_level,   'unit'=>' ppm'],
                                ['key'=>'phosphorus', 'label'=>'Phosphorus','value'=>$sample->phosphorus_level, 'unit'=>' ppm'],
                                ['key'=>'potassium',  'label'=>'Potassium', 'value'=>$sample->potassium_level,  'unit'=>' ppm'],
                            ];
                            @endphp
                            @foreach($calcParams as $cp)
                            @php
                            $st   = $fertilizerSvc->getNutrientStatus($cp['key'], (float)$cp['value']);
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
                                <td class="text-center fw-bold">{{ number_format($cp['value'],1) }}{{ $cp['unit'] }}</td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $stBg }}">{{ $st }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="mt-2 p-2 rounded bg-light small text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Calculator uses 1 ppm ≈ 2 kg/ha soil nutrient availability (0–15 cm depth).
                        Bags are 50 kg each.
                    </div>
                </div>
            </div>{{-- /row --}}

            {{-- Results panel (hidden until Calculate is clicked) --}}
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

{{-- CROP RECOMMENDATIONS --}}
<div class="card mb-4">
    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-seedling me-2"></i>Crop Recommendations</h5>
        @if($sample->isAnalyzed())
        <a href="{{ route('samples.pdf', $sample) }}" target="_blank"
           class="btn btn-sm btn-light">
            <i class="fas fa-print me-1"></i>Print / Save as PDF
        </a>
        @endif
    </div>
    <div class="card-body">

    @if(!$sample->isAnalyzed())
        <p class="text-muted mb-0">Complete all 4 soil tests to see crop recommendations.</p>
    @else

    {{-- Tab navigation --}}
    <ul class="nav nav-tabs mb-3" id="cropTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-tolerance" data-bs-toggle="tab"
                    data-bs-target="#pane-tolerance" type="button">
                <i class="fas fa-check-double me-1 text-success"></i>
                Tolerance Match
                <span class="badge bg-success ms-1">{{ count($cropsByTolerance) }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-fertility" data-bs-toggle="tab"
                    data-bs-target="#pane-fertility" type="button">
                <i class="fas fa-leaf me-1 text-warning"></i>
                Fertility Score
                <span class="badge bg-warning text-dark ms-1">{{ count($cropsByFertility) }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-ph" data-bs-toggle="tab"
                    data-bs-target="#pane-ph" type="button">
                <i class="fas fa-flask me-1 text-info"></i>
                pH Threshold
                <span class="badge bg-info text-white ms-1">{{ count($cropsByPh) }}</span>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="cropTabContent">

        {{-- ── Group 1: Tolerance Match ──────────────────────────────── --}}
        <div class="tab-pane fade show active" id="pane-tolerance" role="tabpanel">
            <p class="text-muted small mb-2">
                <i class="fas fa-info-circle me-1"></i>
                Crops where the soil <strong>pH is within range</strong> and scored by how many
                of the 4 parameters (pH + N + P + K) match.. <strong>Can be planted with current soil as-is.</strong>
            </p>
            @if(count($cropsByTolerance) === 0)
                <div class="alert alert-warning mb-0">No crops match this soil's pH. Consider a lime or sulfur amendment.</div>
            @else
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle table-sm mb-0">
                    <thead class="table-success">
                        <tr><th>#</th><th>Crop</th><th>pH Range</th><th>N (ppm)</th><th>P (ppm)</th><th>K (ppm)</th><th>Score</th></tr>
                    </thead>
                    <tbody>
                        @foreach($cropsByTolerance as $i => $crop)
                        @php $s = $crop->match_score; $mc = $s==4?'success':($s>=3?'warning':($s>=2?'info':'secondary')); @endphp
                        <tr>
                            <td>{{ $i+1 }}</td>
                            <td>
                                <strong>{{ $crop->name }}</strong>
                                @if($i===0)<span class="badge bg-warning text-dark ms-1">Top Pick</span>@endif
                            </td>
                            <td><small>{{ $crop->min_ph }}–{{ $crop->max_ph }}</small></td>
                            <td><small>{{ $crop->min_nitrogen }}–{{ $crop->max_nitrogen }}</small></td>
                            <td><small>{{ $crop->min_phosphorus }}–{{ $crop->max_phosphorus }}</small></td>
                            <td><small>{{ $crop->min_potassium }}–{{ $crop->max_potassium }}</small></td>
                            <td><span class="badge bg-{{ $mc }}">{{ $s }}/4</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        {{-- ── Group 2: Fertility Score ──────────────────────────────── --}}
        <div class="tab-pane fade" id="pane-fertility" role="tabpanel">
            <p class="text-muted small mb-2">
                <i class="fas fa-info-circle me-1"></i>
                Crops ranked by <strong>NPK compatibility only</strong> — pH is not filtered.
                Includes crops whose pH requirement differs slightly; a
                <strong>lime or sulfur amendment</strong> can fix the pH..
            </p>
            @if(count($cropsByFertility) === 0)
                <div class="alert alert-warning mb-0">No NPK data available yet.</div>
            @else
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle table-sm mb-0">
                    <thead class="table-warning">
                        <tr><th>#</th><th>Crop</th><th>pH Range</th><th>N (ppm)</th><th>P (ppm)</th><th>K (ppm)</th><th>NPK Score</th><th>pH Match?</th></tr>
                    </thead>
                    <tbody>
                        @foreach($cropsByFertility as $i => $crop)
                        @php
                            $ns = $crop->npk_score;
                            $pct = round($ns / 3 * 100);
                            $nc = $ns==3?'success':($ns>=2?'warning':'info');
                            $phOk = $sample->ph_level >= $crop->min_ph && $sample->ph_level <= $crop->max_ph;
                        @endphp
                        <tr>
                            <td>{{ $i+1 }}</td>
                            <td>
                                <strong>{{ $crop->name }}</strong>
                                @if($i===0)<span class="badge bg-warning text-dark ms-1">Top Pick</span>@endif
                            </td>
                            <td><small>{{ $crop->min_ph }}–{{ $crop->max_ph }}</small></td>
                            <td><small>{{ $crop->min_nitrogen }}–{{ $crop->max_nitrogen }}</small></td>
                            <td><small>{{ $crop->min_phosphorus }}–{{ $crop->max_phosphorus }}</small></td>
                            <td><small>{{ $crop->min_potassium }}–{{ $crop->max_potassium }}</small></td>
                            <td><span class="badge bg-{{ $nc }}">{{ $pct }}%</span></td>
                            <td>
                                @if($phOk)
                                    <span class="badge bg-success"><i class="fas fa-check me-1"></i>Yes</span>
                                @else
                                    <span class="badge bg-danger"><i class="fas fa-times me-1"></i>Needs fix</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        {{-- ── Group 3: pH Threshold ─────────────────────────────────── --}}
        <div class="tab-pane fade" id="pane-ph" role="tabpanel">
            <p class="text-muted small mb-2">
                <i class="fas fa-info-circle me-1"></i>
                All crops whose <strong>pH tolerance covers pH {{ number_format($sample->ph_level,1) }}</strong>,
                sorted by NPK score. Shows every species that can survive this soil's acidity level.
                Nutrient amendments may still be needed..
            </p>
            @if(count($cropsByPh) === 0)
                <div class="alert alert-warning mb-0">No crops tolerate this pH level. Consider soil pH amendment.</div>
            @else
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle table-sm mb-0">
                    <thead class="table-info">
                        <tr><th>#</th><th>Crop</th><th>pH Range</th><th>N (ppm)</th><th>P (ppm)</th><th>K (ppm)</th><th>NPK Score</th></tr>
                    </thead>
                    <tbody>
                        @foreach($cropsByPh as $i => $crop)
                        @php $ns=$crop->npk_score; $nc=$ns==3?'success':($ns>=2?'warning':($ns>=1?'info':'secondary')); @endphp
                        <tr>
                            <td>{{ $i+1 }}</td>
                            <td>
                                <strong>{{ $crop->name }}</strong>
                                @if($i===0)<span class="badge bg-warning text-dark ms-1">Top Pick</span>@endif
                            </td>
                            <td><small>{{ $crop->min_ph }}–{{ $crop->max_ph }}</small></td>
                            <td><small>{{ $crop->min_nitrogen }}–{{ $crop->max_nitrogen }}</small></td>
                            <td><small>{{ $crop->min_phosphorus }}–{{ $crop->max_phosphorus }}</small></td>
                            <td><small>{{ $crop->min_potassium }}–{{ $crop->max_potassium }}</small></td>
                            <td><span class="badge bg-{{ $nc }}">{{ $ns }}/3</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

    </div>{{-- /tab-content --}}
    @endif
    </div>
</div>

{{-- AI RECOMMENDATION --}}
<div class="card mb-4" id="aiSection">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-robot me-2"></i>AI Agronomic Advisor</h5>
        @if($aiEnabled)
            <span class="badge bg-success"><i class="fas fa-circle me-1" style="font-size:.6rem;"></i>API Ready</span>
        @else
            <span class="badge bg-secondary"><i class="fas fa-circle me-1" style="font-size:.6rem;"></i>API Not Configured</span>
        @endif
    </div>
    <div class="card-body">

        @if(!$aiEnabled)
        {{-- API key not configured --}}
        <div class="alert alert-warning mb-3">
            <h6 class="alert-heading mb-2"><i class="fas fa-key me-2"></i>API Key Required</h6>
            <p class="mb-2">The AI Agronomic Advisor uses <strong>Claude AI by Anthropic</strong> to generate personalized advice based on your soil test results.</p>
            <p class="mb-1">To enable this feature, a system administrator must:</p>
            <ol class="mb-2 small">
                <li>Create an account at <strong>console.anthropic.com</strong></li>
                <li>Add billing information and subscribe to an Anthropic plan</li>
                <li>Generate an API key from the Anthropic Console</li>
                <li>Add <code>ANTHROPIC_API_KEY=sk-ant-...</code> to the system's <code>.env</code> file and restart the server</li>
            </ol>
            <hr class="my-2">
            <p class="mb-0 small text-muted">
                <i class="fas fa-credit-card me-1"></i>
                <strong>Billing notice:</strong> This feature requires an active Anthropic subscription with a valid payment method on file. API usage is billed per request based on the number of tokens processed. Contact your system administrator to configure access.
            </p>
        </div>
        <button class="btn btn-secondary" disabled>
            <i class="fas fa-robot me-1"></i> Generate AI Recommendation
            <span class="ms-2 badge bg-light text-dark" style="font-size:.7rem;">Not Available</span>
        </button>

        @elseif(!empty($sample->ai_recommendation))
        {{-- AI already generated --}}
        <div id="aiRecommendationText" class="p-3 bg-light rounded" style="white-space:pre-wrap;">{{ $sample->ai_recommendation }}</div>
        <div class="mt-2 text-end">
            <button class="btn btn-sm btn-outline-dark" onclick="generateAI()">
                <i class="fas fa-sync me-1"></i> Regenerate
            </button>
        </div>

        @else
        {{-- API ready, not yet generated --}}
        <p class="text-muted mb-2">Get AI-powered agronomic advice tailored to your exact soil readings, crop recommendations, and local conditions.</p>
        <div class="alert alert-light border small mb-3">
            <i class="fas fa-info-circle text-info me-1"></i>
            Powered by <strong>Claude AI (Anthropic)</strong>. Each request consumes API tokens billed to your Anthropic account. Ensure active billing before generating.
        </div>
        <button class="btn btn-dark" onclick="generateAI()" id="aiBtn">
            <i class="fas fa-robot me-1"></i> Generate AI Recommendation
        </button>
        <div id="aiLoading" class="d-none mt-3">
            <div class="spinner-border spinner-border-sm text-dark me-2"></div>Consulting AI agronomist...
        </div>
        <div id="aiResult" class="mt-3 p-3 bg-light rounded d-none" style="white-space:pre-wrap;"></div>
        <div id="aiError" class="alert alert-danger mt-3 d-none"></div>
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
            {{-- API key not configured --}}
            <div class="alert alert-warning mb-3">
                <h6 class="alert-heading mb-2"><i class="fas fa-key me-2"></i>Gemini API Key Required</h6>
                <p class="mb-2">
                    This feature uses <strong>Google Gemini AI</strong> to recommend up to 10 suitable Philippine
                    crops grouped by season and crop type, with per-crop fertilizer calculations.
                </p>
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
            {{-- API ready --}}
            <p class="text-muted mb-3">
                Gemini AI will recommend up to <strong>10 Philippine crops</strong> suited to your soil,
                grouped by <em>season</em> (Wet / Dry / Year-round) and <em>crop type</em>
                (Grain, Vegetable, Root Crop, Legume, Fruit, Cash Crop) — each with a
                tailored fertilizer plan.
            </p>

            {{-- Optional preferred-crop selector --}}
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

            {{-- Stored recommendation display --}}
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

            {{-- Generate button --}}
            <div id="geminiGenerateArea" class="{{ !empty($sample->gemini_crop_recommendation) ? 'd-none' : '' }}">
                <button class="btn btn-lg"
                        style="background:linear-gradient(135deg,#1a73e8,#34a853);color:#fff;"
                        onclick="generateGemini()" id="geminiBtn">
                    <i class="fas fa-seedling me-2"></i>Get Gemini Crop Recommendations
                </button>
            </div>

            {{-- Loading / result / error --}}
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
        <a href="{{ route('export', ['sample_id' => $sample->id]) }}" class="btn btn-success ms-2">
            <i class="fas fa-file-excel me-1"></i> Export to Excel
        </a>
        <a href="{{ route('samples.create') }}" class="btn btn-primary ms-2">
            <i class="fas fa-plus-circle me-1"></i> New Sample
        </a>
    </div>
</div>
@endif

@endsection

@section('scripts')

@if($sample->isAnalyzed())
<script>
// ── Fertilizer Calculator ──────────────────────────────────────────────────
// Crop NPK targets (ppm) sourced from the Crop seeder / database.
// Target = max_nitrogen/phosphorus/potassium (upper bound of optimal soil range).
// Deficit = max(0, target_ppm - soil_ppm); converted to kg/ha via ×2.
const CROP_NPK = {
    @foreach($allCrops as $crop)
    {{ $crop->id }}: {
        n: {{ (float)$crop->max_nitrogen }},
        p: {{ (float)$crop->max_phosphorus }},
        k: {{ (float)$crop->max_potassium }},
        label: @json($crop->name)
    },
    @endforeach
};

// Fertilizer nutrient analysis (fraction) and supplemental fertilizers needed
const FERT_TYPE = {
    urea:             { n: 0.46, p: 0,    k: 0,    name: 'Urea (46-0-0)',              suppP: 'TSP (0-46-0)', suppK: 'MOP (0-0-60)' },
    complete:         { n: 0.14, p: 0.14, k: 0.14, name: 'Complete (14-14-14)',        suppP: null,           suppK: null },
    ammonium_sulfate: { n: 0.21, p: 0,    k: 0,    name: 'Ammonium Sulfate (21-0-0)', suppP: 'TSP (0-46-0)', suppK: 'MOP (0-0-60)' },
    dap:              { n: 0.18, p: 0.46, k: 0,    name: 'DAP (18-46-0)',             suppP: null,            suppK: 'MOP (0-0-60)' },
    mop:              { n: 0,    p: 0,    k: 0.60, name: 'Muriate of Potash (0-0-60)', suppP: 'TSP (0-46-0)', suppK: null },
    organic:          { n: 0.02, p: 0.015,k: 0.01, name: 'Organic Fertilizer (~2-1.5-1)', suppP: null,        suppK: null },
};

const SOIL_N  = {{ (float)$sample->nitrogen_level }};
const SOIL_P  = {{ (float)$sample->phosphorus_level }};
const SOIL_K  = {{ (float)$sample->potassium_level }};
const SOIL_PH = {{ (float)$sample->ph_level }};

function calculateFertilizer() {
    const crop  = document.getElementById('cropSelect').value;
    const area  = parseFloat(document.getElementById('areaSize').value) || 1;
    const fType = document.getElementById('fertilizerType').value;

    if (!crop) { alert('Please select a crop first.'); return; }

    const req   = CROP_NPK[crop];
    const fert  = FERT_TYPE[fType];
    const BAG   = 50; // kg per bag

    // req.n/p/k are in ppm (from DB). Soil readings are also ppm.
    // Deficit in ppm → convert to kg/ha via ×2 (1 ppm ≈ 2 kg/ha at 0–15 cm depth).
    const defNppm = Math.max(0, req.n - SOIL_N);
    const defPppm = Math.max(0, req.p - SOIL_P);
    const defKppm = Math.max(0, req.k - SOIL_K);
    const defN = defNppm * 2;
    const defP = defPppm * 2;
    const defK = defKppm * 2;
    // For the deficit table, keep ppm values for display
    const soilN = SOIL_N, soilP = SOIL_P, soilK = SOIL_K;

    // Bags of primary fertilizer needed (per ha) — cover the most limiting nutrient
    let primaryBagsHa = 0, limitedBy = '';
    if (fert.n > 0 && fert.p > 0 && fert.k > 0) {
        // Multi-nutrient (e.g. 14-14-14): drive by the nutrient needing the most bags
        const byN = fert.n > 0 ? defN / (BAG * fert.n) : 0;
        const byP = fert.p > 0 ? defP / (BAG * fert.p) : 0;
        const byK = fert.k > 0 ? defK / (BAG * fert.k) : 0;
        primaryBagsHa = Math.max(byN, byP, byK);
        if (primaryBagsHa === byN) limitedBy = 'Nitrogen';
        else if (primaryBagsHa === byP) limitedBy = 'Phosphorus';
        else limitedBy = 'Potassium';
    } else if (fert.n > 0) { primaryBagsHa = defN / (BAG * fert.n); limitedBy = 'Nitrogen'; }
    else if (fert.p > 0)   { primaryBagsHa = defP / (BAG * fert.p); limitedBy = 'Phosphorus'; }
    else if (fert.k > 0)   { primaryBagsHa = defK / (BAG * fert.k); limitedBy = 'Potassium'; }

    const primaryBagsTotal = primaryBagsHa * area;

    // Lime recommendation (from soil pH)
    let limeTons = 0, limeNote = '';
    if (SOIL_PH < 5.0)      { limeTons = 2.0; limeNote = 'Strongly acidic (pH < 5.0) — apply 2 t/ha dolomitic lime before planting.'; }
    else if (SOIL_PH < 5.5) { limeTons = 1.0; limeNote = 'Moderately acidic (pH 5.0–5.5) — apply 1 t/ha dolomitic lime.'; }
    else if (SOIL_PH > 7.5) { limeNote = 'Alkaline soil (pH > 7.5) — consider organic matter or elemental sulfur to lower pH.'; }

    // Supplemental fertilizers needed (for single-nutrient primary)
    const suppPBagsHa = (fert.p === 0 && defP > 0) ? defP / (BAG * 0.46) : 0; // TSP
    const suppKBagsHa = (fert.k === 0 && defK > 0) ? defK / (BAG * 0.60) : 0; // MOP

    // Build result cards HTML
    const fmt = (n) => n.toFixed(2);
    let cards = '';

    if (limeTons > 0) {
        cards += card('fa-mountain', 'danger',
            'Dolomitic Lime',
            `${fmt(limeTons * area)} tonnes`,
            `${fmt(limeTons)} t/ha × ${fmt(area)} ha`, 'pH correction');
    }

    cards += card('fa-seedling', 'success',
        fert.name,
        `${fmt(primaryBagsTotal)} bags`,
        `${fmt(primaryBagsHa)} bags/ha × ${fmt(area)} ha`,
        `Limited by: ${limitedBy || 'N/A'}`);

    if (suppPBagsHa > 0) {
        cards += card('fa-atom', 'primary',
            'TSP (0-46-0) — Supp. P',
            `${fmt(suppPBagsHa * area)} bags`,
            `${fmt(suppPBagsHa)} bags/ha × ${fmt(area)} ha`, 'Phosphorus supplement');
    }
    if (suppKBagsHa > 0) {
        cards += card('fa-flask', 'info',
            'MOP (0-0-60) — Supp. K',
            `${fmt(suppKBagsHa * area)} bags`,
            `${fmt(suppKBagsHa)} bags/ha × ${fmt(area)} ha`, 'Potassium supplement');
    }

    // Deficits row
    cards += `
    <div class="col-12">
        <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle mb-0">
                <thead class="table-light"><tr>
                    <th>Nutrient</th>
                    <th class="text-center">Crop Target (ppm)</th>
                    <th class="text-center">Current Soil (ppm)</th>
                    <th class="text-center">Deficit (ppm)</th>
                    <th class="text-center">Deficit (kg/ha)</th>
                </tr></thead>
                <tbody>
                    <tr>
                        <td>Nitrogen (N)</td>
                        <td class="text-center">${req.n}</td>
                        <td class="text-center">${fmt(soilN)}</td>
                        <td class="text-center fw-bold ${defNppm > 0 ? 'text-danger' : 'text-success'}">${fmt(defNppm)}</td>
                        <td class="text-center text-muted">${fmt(defN)}</td>
                    </tr>
                    <tr>
                        <td>Phosphorus (P)</td>
                        <td class="text-center">${req.p}</td>
                        <td class="text-center">${fmt(soilP)}</td>
                        <td class="text-center fw-bold ${defPppm > 0 ? 'text-danger' : 'text-success'}">${fmt(defPppm)}</td>
                        <td class="text-center text-muted">${fmt(defP)}</td>
                    </tr>
                    <tr>
                        <td>Potassium (K)</td>
                        <td class="text-center">${req.k}</td>
                        <td class="text-center">${fmt(soilK)}</td>
                        <td class="text-center fw-bold ${defKppm > 0 ? 'text-danger' : 'text-success'}">${fmt(defKppm)}</td>
                        <td class="text-center text-muted">${fmt(defK)}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>`;

    // Show results
    document.getElementById('calcResultsTitle').innerHTML =
        `<i class="fas fa-check-circle text-success me-1"></i>` +
        `Result for <strong>${req.label}</strong> — ` +
        `<strong>${fmt(area)} ha</strong> using <strong>${fert.name}</strong>`;
    document.getElementById('calcResultsCards').innerHTML = cards;

    const alertEl = document.getElementById('calcResultsAlert');
    alertEl.className = 'alert mt-3 mb-0 alert-' + (limeNote ? (SOIL_PH < 5.0 ? 'danger' : 'warning') : 'success');
    alertEl.innerHTML = limeNote
        ? `<i class="fas fa-exclamation-triangle me-1"></i>${limeNote}`
        : `<i class="fas fa-check-circle me-1"></i>Soil pH (${SOIL_PH}) is within an acceptable range. No lime amendment needed.`;

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

function generateAI() {
    const btn     = document.getElementById('aiBtn');
    const loading = document.getElementById('aiLoading');
    const result  = document.getElementById('aiResult');
    const errDiv  = document.getElementById('aiError');
    if (btn)     btn.disabled = true;
    if (loading) loading.classList.remove('d-none');
    if (result)  result.classList.add('d-none');
    if (errDiv)  errDiv.classList.add('d-none');

    fetch('{{ route("ai-recommendation.generate") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ sample_id: {{ $sample->id }} })
    })
    .then(res => res.json())
    .then(data => {
        if (loading) loading.classList.add('d-none');
        if (data.success) {
            if (result) { result.textContent = data.recommendation; result.classList.remove('d-none'); }
            const existing = document.getElementById('aiRecommendationText');
            if (existing) existing.textContent = data.recommendation;
        } else {
            if (errDiv) { errDiv.textContent = 'AI Error: ' + data.message; errDiv.classList.remove('d-none'); }
            if (btn) btn.disabled = false;
        }
    })
    .catch(() => {
        if (loading) loading.classList.add('d-none');
        if (errDiv) { errDiv.textContent = 'Network error contacting AI service.'; errDiv.classList.remove('d-none'); }
        if (btn) btn.disabled = false;
    });
}

// ── Gemini AI Crop Recommendations ────────────────────────────────────────
function generateGemini() {
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

    const preferredCrop = cropSelect ? cropSelect.value.trim() : '';

    fetch('{{ route("gemini-crop-recommendations.generate") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            sample_id:      {{ $sample->id }},
            preferred_crop: preferredCrop || null
        })
    })
    .then(res => res.json())
    .then(data => {
        if (loading) loading.classList.add('d-none');
        if (data.success) {
            // Show in new result panel
            if (result) {
                result.textContent = data.recommendation;
                result.classList.remove('d-none');
            }
            // Also update stored result panel if it exists
            const storedText = document.getElementById('geminiRecommendationText');
            if (storedText) {
                storedText.textContent = data.recommendation;
                if (storedDiv) storedDiv.classList.remove('d-none');
            }
            // Hide the primary generate button after first successful call
            if (generateArea) generateArea.classList.add('d-none');
        } else {
            if (errDiv) {
                errDiv.textContent = 'Gemini Error: ' + data.message;
                errDiv.classList.remove('d-none');
            }
        }
        if (btn)     btn.disabled = false;
        if (regenBtn) regenBtn.disabled = false;
    })
    .catch(() => {
        if (loading) loading.classList.add('d-none');
        if (errDiv) {
            errDiv.textContent = 'Network error contacting Gemini AI service.';
            errDiv.classList.remove('d-none');
        }
        if (btn)     btn.disabled = false;
        if (regenBtn) regenBtn.disabled = false;
    });
}
</script>
@endif
@endsection
