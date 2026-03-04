@extends('layouts.app')
@section('title', $sample->sample_name)
@section('content')

<div class="row mb-3">
    <div class="col">
        <a href="{{ route('samples.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to All Samples
        </a>
        @if($sample->isAnalyzed())
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

@php
$params = [
    'ph'         => ['label' => 'Soil pH',       'unit' => '',    'icon' => 'fa-flask'],
    'nitrogen'   => ['label' => 'Nitrogen (N)',   'unit' => 'ppm', 'icon' => 'fa-leaf'],
    'phosphorus' => ['label' => 'Phosphorus (P)', 'unit' => 'ppm', 'icon' => 'fa-atom'],
    'potassium'  => ['label' => 'Potassium (K)',  'unit' => 'ppm', 'icon' => 'fa-seedling'],
];
@endphp

{{-- WEBCAM CAPTURE SECTION (while not fully analyzed) --}}
@if(!$sample->isAnalyzed())
<div class="card border-warning mb-4">
    <div class="card-header bg-warning text-dark">
        <h5 class="mb-0"><i class="fas fa-camera me-2"></i>Webcam Capture — 3-Test Accuracy System</h5>
    </div>
    <div class="card-body">
        <div class="alert alert-info py-2 mb-3">
            <i class="fas fa-info-circle me-1"></i>
            <strong>How it works:</strong> Capture each indicator strip <strong>3 times</strong>. The system averages all three readings. Place the strip inside the dashed circle before each capture.
        </div>
        <div class="row">
            <div class="col-md-5 text-center mb-3">
                <div style="position:relative; display:inline-block;">
                    <video id="webcam" width="380" height="280" autoplay playsinline style="border:2px solid #ccc; border-radius:8px;"></video>
                    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:80px;height:80px;border:3px dashed rgba(255,255,255,0.85);border-radius:50%;pointer-events:none;box-shadow:0 0 0 1px rgba(0,0,0,0.3);"></div>
                </div>
                <canvas id="snapshot" width="380" height="280" style="display:none;"></canvas>
                <br>
                <button id="startCameraBtn" class="btn btn-outline-secondary mt-2" onclick="startCamera()">
                    <i class="fas fa-video"></i> Start Camera
                </button>
            </div>
            <div class="col-md-7">
                <table class="table table-bordered align-middle table-sm" id="captureTable">
                    <thead class="table-success">
                        <tr>
                            <th>Parameter</th>
                            <th class="text-center">Test 1</th>
                            <th class="text-center">Test 2</th>
                            <th class="text-center">Test 3</th>
                            <th class="text-center">Average</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($params as $key => $meta)
                        @php
                            $r1 = $readings[$key][1] ?? null;
                            $r2 = $readings[$key][2] ?? null;
                            $r3 = $readings[$key][3] ?? null;
                            $avgHex = ($r1 && $r2 && $r3) ? $sample->{$key.'_color_hex'} : null;
                        @endphp
                        <tr id="row-{{ $key }}">
                            <td>
                                <i class="fas {{ $meta['icon'] }} me-1 text-success"></i>
                                <strong>{{ $meta['label'] }}</strong>
                            </td>
                            @for($t = 1; $t <= 3; $t++)
                            @php $rd = $readings[$key][$t] ?? null; @endphp
                            <td class="text-center p-1">
                                @if($rd)
                                <div class="d-flex flex-column align-items-center gap-1">
                                    <div style="width:36px;height:18px;background:{{ $rd->color_hex }};border:1px solid #ccc;border-radius:3px;" title="{{ $rd->color_hex }}"></div>
                                    <small class="text-muted" style="font-size:10px;">{{ $rd->color_hex }}</small>
                                    <button class="btn btn-outline-secondary btn-sm py-0 px-1" style="font-size:10px;" onclick="captureTest('{{ $key }}', {{ $t }})">Redo</button>
                                </div>
                                @else
                                <button class="btn btn-sm btn-success" onclick="captureTest('{{ $key }}', {{ $t }})" id="btn-{{ $key }}-{{ $t }}">
                                    <i class="fas fa-camera"></i> #{{ $t }}
                                </button>
                                @endif
                            </td>
                            @endfor
                            <td class="text-center" id="avg-cell-{{ $key }}">
                                @if($avgHex)
                                <div style="width:40px;height:20px;background:{{ $avgHex }};border:1px solid #999;border-radius:3px;margin:auto;"></div>
                                <small class="text-success fw-bold" style="font-size:10px;">{{ $avgHex }}</small>
                                @else
                                <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                @php $done = (int)($sample->tests_completed ?? 0); @endphp
                <div class="mt-2 mb-3">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>Progress</span>
                        <span id="progressLabel">{{ $done }}/12 tests</span>
                    </div>
                    <div class="progress" style="height:8px;">
                        <div class="progress-bar bg-success" id="progressBar" style="width:{{ round($done/12*100) }}%"></div>
                    </div>
                </div>

                <div id="computeSection" class="{{ $sample->allAveraged() ? '' : 'd-none' }}">
                    <div class="alert alert-success py-2">
                        <i class="fas fa-check-circle me-1"></i>All 4 parameters have 3 readings each. Ready to compute results.
                    </div>
                    <a href="{{ route('samples.show', $sample) }}" class="btn btn-success w-100">
                        <i class="fas fa-calculator me-1"></i>Compute &amp; View Results
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- ANALYSIS RESULTS --}}
@if($sample->isAnalyzed())
@php
$resultParams = [
    'ph'         => ['label'=>'Soil pH',       'value'=>$sample->ph_level,         'unit'=>'',    'low'=>5.5,  'high'=>7.0,  'hex'=>$sample->ph_color_hex],
    'nitrogen'   => ['label'=>'Nitrogen (N)',   'value'=>$sample->nitrogen_level,   'unit'=>'ppm', 'low'=>20.0, 'high'=>40.0, 'hex'=>$sample->nitrogen_color_hex],
    'phosphorus' => ['label'=>'Phosphorus (P)', 'value'=>$sample->phosphorus_level, 'unit'=>'ppm', 'low'=>15.0, 'high'=>30.0, 'hex'=>$sample->phosphorus_color_hex],
    'potassium'  => ['label'=>'Potassium (K)',  'value'=>$sample->potassium_level,  'unit'=>'ppm', 'low'=>20.0, 'high'=>40.0, 'hex'=>$sample->potassium_color_hex],
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
            'Medium' => 'warning',
            'Optimal' => 'success',
            'Alkaline' => 'info',
            'High' => 'primary',
            default => 'secondary'
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
                @if(!empty($readings[$key]))
                <div class="mt-2 pt-2 border-top">
                    <small class="text-muted d-block mb-1">3-Test Readings</small>
                    <div class="d-flex justify-content-center gap-2">
                        @for($t = 1; $t <= 3; $t++)
                        @php $rd = $readings[$key][$t] ?? null; @endphp
                        <div class="text-center">
                            <div style="width:28px;height:14px;background:{{ $rd ? $rd->color_hex : '#eee' }};border:1px solid #ccc;border-radius:2px;"></div>
                            <small style="font-size:9px;" class="text-muted">
                                #{{ $t }}{{ $rd ? ': '.number_format($rd->computed_value, 1) : '' }}
                            </small>
                        </div>
                        @endfor
                    </div>
                </div>
                @endif
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
                        <div style="font-size:10px;" class="text-muted mt-1">for pH correction</div>
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
        <ul class="list-group list-group-flush">
            @foreach($fertRec['notes'] as $note)
            <li class="list-group-item py-1">
                <i class="fas fa-circle-info text-warning me-2"></i>
                <small>{{ $note }}</small>
            </li>
            @endforeach
        </ul>
    </div>
</div>
@endif

{{-- CROP RECOMMENDATIONS --}}
<div class="card mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="fas fa-seedling me-2"></i>Crop Recommendations</h5>
    </div>
    <div class="card-body">
        @if($recommendations->isEmpty())
        <div class="alert alert-warning mb-0">No crop match for these soil conditions. Consider soil amendments to adjust pH or NPK levels.</div>
        @else
        <p class="text-muted small mb-3">Scored by how many parameters fall within the crop's tolerance range (4 = perfect match).</p>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle table-sm">
                <thead class="table-success">
                    <tr><th>#</th><th>Crop</th><th>pH Range</th><th>N (ppm)</th><th>P (ppm)</th><th>K (ppm)</th><th>Match</th></tr>
                </thead>
                <tbody>
                    @foreach($recommendations as $i => $crop)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>
                            <strong>{{ $crop->name }}</strong>
                            @if($i === 0)<span class="badge bg-warning text-dark ms-1">Top Pick</span>@endif
                        </td>
                        <td>{{ $crop->min_ph }} – {{ $crop->max_ph }}</td>
                        <td>{{ $crop->min_nitrogen }} – {{ $crop->max_nitrogen }}</td>
                        <td>{{ $crop->min_phosphorus }} – {{ $crop->max_phosphorus }}</td>
                        <td>{{ $crop->min_potassium }} – {{ $crop->max_potassium }}</td>
                        <td>
                            @php $s = $crop->match_score; $mc = $s==4?'success':($s>=3?'warning':($s>=2?'info':'danger')); @endphp
                            <span class="badge bg-{{ $mc }}">{{ $s }}/4</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

{{-- AI RECOMMENDATION --}}
<div class="card mb-4" id="aiSection">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="fas fa-robot me-2"></i>AI Agronomic Advisor</h5>
    </div>
    <div class="card-body">
        @if(!empty($sample->ai_recommendation))
        <div id="aiRecommendationText" class="p-3 bg-light rounded" style="white-space:pre-wrap;">{{ $sample->ai_recommendation }}</div>
        <div class="mt-2 text-end">
            <button class="btn btn-sm btn-outline-dark" onclick="generateAI()">
                <i class="fas fa-sync me-1"></i> Regenerate
            </button>
        </div>
        @else
        <p class="text-muted">Get AI-powered agronomic advice tailored to your exact soil readings, crop recommendations, and local conditions.</p>
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

<div class="row mb-4">
    <div class="col text-end">
        <a href="{{ route('samples.reset', $sample) }}"
           class="btn btn-outline-warning"
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
@if(!$sample->isAnalyzed())
<script>
const sampleId = {{ $sample->id }};
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
let videoStream = null;
let totalReadings = {{ (int)($sample->tests_completed ?? 0) }};
const testsDone = {
    ph: {{ count($readings['ph']) }},
    nitrogen: {{ count($readings['nitrogen']) }},
    phosphorus: {{ count($readings['phosphorus']) }},
    potassium: {{ count($readings['potassium']) }},
};

function startCamera() {
    navigator.mediaDevices.getUserMedia({ video: { width: 380, height: 280 }, audio: false })
        .then(stream => {
            videoStream = stream;
            document.getElementById('webcam').srcObject = stream;
            const btn = document.getElementById('startCameraBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-check-circle"></i> Camera Active';
            btn.classList.replace('btn-outline-secondary', 'btn-success');
        })
        .catch(err => alert('Could not access webcam: ' + err.message));
}

function captureTest(parameter, testNumber) {
    if (!videoStream) { alert('Please start the camera first.'); return; }
    const video  = document.getElementById('webcam');
    const canvas = document.getElementById('snapshot');
    const ctx    = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    const cx = Math.floor(canvas.width / 2) - 40;
    const cy = Math.floor(canvas.height / 2) - 40;
    const data = ctx.getImageData(cx, cy, 80, 80).data;
    let r = 0, g = 0, b = 0, n = 0;
    for (let i = 0; i < data.length; i += 4) { r += data[i]; g += data[i+1]; b += data[i+2]; n++; }
    r = Math.round(r/n); g = Math.round(g/n); b = Math.round(b/n);
    const hex = '#' + [r,g,b].map(v => v.toString(16).padStart(2,'0')).join('').toUpperCase();

    const btn = document.getElementById(`btn-${parameter}-${testNumber}`);
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; }

    fetch('{{ route("color-readings.store") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ sample_id: sampleId, parameter, color_hex: hex, r, g, b, test_number: testNumber })
    })
    .then(res => res.json())
    .then(resp => {
        if (!resp.success) { alert('Error: ' + resp.message); return; }
        testsDone[parameter] = resp.tests_done;
        totalReadings = resp.total_readings;
        document.getElementById('progressLabel').textContent = totalReadings + '/12 tests';
        document.getElementById('progressBar').style.width = Math.round(totalReadings / 12 * 100) + '%';
        if (resp.avg_hex) {
            const avgCell = document.getElementById(`avg-cell-${parameter}`);
            if (avgCell) {
                avgCell.innerHTML = `<div style="width:40px;height:20px;background:${resp.avg_hex};border:1px solid #999;border-radius:3px;margin:auto;"></div>
                    <small class="text-success fw-bold" style="font-size:10px;">${resp.avg_hex}</small>`;
            }
        }
        const allDone = Object.values(testsDone).every(c => c >= 3);
        if (allDone || totalReadings >= 12) {
            document.getElementById('computeSection').classList.remove('d-none');
        }
        setTimeout(() => location.reload(), 600);
    })
    .catch(() => {
        alert('Network error — please try again.');
        if (btn) { btn.disabled = false; btn.innerHTML = `<i class="fas fa-camera"></i> #${testNumber}`; }
    });
}
</script>
@endif

@if($sample->isAnalyzed())
<script>
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
</script>
@endif
@endsection
