@extends('layouts.app')
@section('title', $meta['label'] . ' Test — ' . $sample->sample_name)
@section('content')

{{-- ── Breadcrumb ──────────────────────────────────────────────────── --}}
<div class="row mb-3 align-items-center">
    <div class="col">
        <a href="{{ route('samples.show', $sample) }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Sample
        </a>
        @if($meta['prev'])
        <a href="{{ route('parameter-test.show', [$sample, $meta['prev']]) }}"
           class="btn btn-sm btn-outline-secondary ms-1">
            <i class="fas fa-chevron-left me-1"></i>
            {{ ucfirst($meta['prev']) }}
        </a>
        @endif
    </div>
    <div class="col-auto">
        @if($meta['next'])
        <a href="{{ route('parameter-test.show', [$sample, $meta['next']]) }}"
           class="btn btn-sm btn-outline-{{ $meta['color'] }}">
            {{ ucfirst($meta['next']) }}
            <i class="fas fa-chevron-right ms-1"></i>
        </a>
        @else
        <a href="{{ route('samples.show', $sample) }}"
           class="btn btn-sm btn-{{ $meta['color'] }}">
            <i class="fas fa-check-circle me-1"></i> Done — Back to Sample
        </a>
        @endif
    </div>
</div>

{{-- ── Sample info strip ─────────────────────────────────────────── --}}
<div class="card mb-4">
    <div class="card-body py-2">
        <div class="row align-items-center">
            <div class="col-md-8">
                <strong class="text-success">
                    <i class="fas fa-vial me-1"></i>{{ $sample->sample_name }}
                </strong>
                <span class="mx-2 text-muted">·</span>{{ $sample->farmer_name }}
                <span class="mx-2 text-muted">·</span>{{ $sample->address }}
            </div>
            <div class="col-md-4 text-md-end">
                <span class="text-muted small">Date tested: {{ $sample->date_tested->format('F j, Y') }}</span>
            </div>
        </div>
    </div>
</div>

{{-- ── Parameter progress pills ─────────────────────────────────── --}}
@php
$paramOrder = ['ph' => 'Soil pH', 'nitrogen' => 'Nitrogen', 'phosphorus' => 'Phosphorus', 'potassium' => 'Potassium'];
$phDone     = $sample->ph_color_hex ? true : false;
$paramDone  = [
    'ph'         => $phDone,
    'nitrogen'   => (bool)$sample->nitrogen_color_hex,
    'phosphorus' => (bool)$sample->phosphorus_color_hex,
    'potassium'  => (bool)$sample->potassium_color_hex,
];
@endphp
<div class="d-flex gap-2 mb-4 flex-wrap">
    @foreach($paramOrder as $pk => $pl)
    @php
        $isActive  = ($pk === $parameter);
        $isDone    = $paramDone[$pk];
        $isph      = ($pk === 'ph');
        $linkRoute = $isph
            ? route('ph-test.show', $sample)
            : route('parameter-test.show', [$sample, $pk]);
    @endphp
    <a href="{{ $linkRoute }}"
       class="btn btn-sm {{ $isActive ? 'btn-' . $meta['color'] : ($isDone ? 'btn-success' : 'btn-outline-secondary') }}">
        <i class="fas {{ $isDone ? 'fa-check-circle' : ($isActive ? 'fa-circle-dot' : 'fa-circle') }} me-1"></i>
        {{ $pl }}
    </a>
    @endforeach
</div>

{{-- ── Main capture card ─────────────────────────────────────────── --}}
<div class="card border-{{ $meta['color'] }} mb-4">
    <div class="card-header bg-{{ $meta['color'] }} text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas {{ $meta['icon'] }} me-2"></i>
            {{ $meta['label'] }} Test
        </h5>
        <span class="badge bg-white text-{{ $meta['color'] }}">{{ $captureCount }}/3 captures</span>
    </div>
    <div class="card-body">

        {{-- Protocol instructions --}}
        @if($captureCount < 3)
        <div class="alert alert-info mb-4">
            <h6 class="alert-heading mb-2">
                <i class="fas fa-info-circle me-1"></i>{{ $meta['label'] }} Protocol (BSWM)
            </h6>
            <ol class="mb-0 small ps-3">
                <li>Transfer soil sample to the 1st scratch mark (~0.5 g) in a clean dry test tube.</li>
                <li>Fill with <strong>{{ $meta['reagent'] }}</strong> to the 2nd scratch mark (~1 mL).</li>
                <li>Mix well by tapping into palm for <strong>1 minute</strong>.</li>
                <li>Let stand for <strong>2 minutes</strong>, then mix again for 1 minute.</li>
                <li>Let stand for <strong>5 minutes</strong>.</li>
                <li>Insert the test tube into the image capturing box for color capture.</li>
                <li>Take <strong>3 captures</strong> for accuracy.</li>
            </ol>
        </div>
        @endif

        <div class="row">

            {{-- ── Left: Timer + webcam ──────────────────────────── --}}
            <div class="col-md-5 text-center mb-4">

                {{-- Reaction timer --}}
                <div class="mb-3">
                    <div id="timerDisplay" class="display-5 fw-bold text-{{ $meta['color'] }}">8:00</div>
                    <div class="text-muted small mb-2">Mix 1 min → wait 2 min → mix 1 min → wait 5 min</div>
                    <button class="btn btn-outline-{{ $meta['color'] }} btn-sm" onclick="startTimer(480)">
                        <i class="fas fa-play me-1"></i> Start Timer
                    </button>
                    <div id="timerStatus" class="small mt-1 text-muted"></div>
                </div>

                {{-- Webcam --}}
                <div style="position:relative;display:inline-block;">
                    <video id="webcam" width="320" height="240" autoplay playsinline
                           style="border:2px solid var(--bs-{{ $meta['color'] }});border-radius:8px;display:block;"></video>
                    {{-- Capture-zone crosshair: 70×70 px square matching the JS getImageData crop --}}
                    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);
                                width:70px;height:70px;border:2px solid #fff;
                                box-shadow:0 0 0 1px var(--bs-{{ $meta['color'] }}),inset 0 0 0 1px var(--bs-{{ $meta['color'] }});
                                pointer-events:none;"></div>
                    <div style="position:absolute;bottom:6px;left:50%;transform:translateX(-50%);
                                background:rgba(0,0,0,.55);color:#fff;font-size:10px;padding:1px 6px;
                                border-radius:3px;pointer-events:none;white-space:nowrap;">
                        Place liquid here
                    </div>
                </div>
                <canvas id="snapshot" width="320" height="240" style="display:none;"></canvas>
                <br>
                <button id="startCameraBtn" class="btn btn-outline-secondary btn-sm mt-2" onclick="startCamera()">
                    <i class="fas fa-video me-1"></i> Start Camera
                </button>
            </div>

            {{-- ── Right: Capture table ──────────────────────────── --}}
            <div class="col-md-7">
                <table class="table table-bordered align-middle table-sm">
                    <thead class="table-{{ $meta['color'] === 'info' ? 'info' : $meta['color'] }}
                                  {{ in_array($meta['color'], ['success','primary']) ? '' : '' }}">
                        <tr>
                            <th>Capture</th>
                            <th class="text-center">Color</th>
                            <th class="text-center">{{ $meta['label'] }} Reading</th>
                            <th class="text-center">Category</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for($i = 1; $i <= 3; $i++)
                        @php
                            $rd = $readings[$i] ?? null;
                            if ($rd) {
                                $v = $rd->computed_value;
                                if ($v < $meta['low_max'])        { $catLabel = 'LOW';    $catBg = '#c0642a'; }
                                elseif ($v >= $meta['high_min'])  { $catLabel = 'HIGH';   $catBg = '#1565c0'; }
                                else                               { $catLabel = 'MEDIUM'; $catBg = '#2e7d32'; }
                            }
                        @endphp
                        <tr>
                            <td class="fw-bold">Capture {{ $i }}</td>
                            <td class="text-center">
                                @if($rd)
                                    <div style="width:38px;height:20px;background:{{ $rd->color_hex }};
                                                border:1px solid #ccc;border-radius:3px;margin:0 auto 2px;"></div>
                                    <small class="text-muted" style="font-size:10px;">{{ $rd->color_hex }}</small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($rd)
                                    <strong class="text-{{ $meta['color'] }}">
                                        {{ number_format($rd->computed_value, 2) }} {{ $meta['unit'] }}
                                    </strong>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($rd)
                                    <span class="badge text-white"
                                          style="background:{{ $catBg }};font-size:.75rem;letter-spacing:.04em;">
                                        {{ $catLabel }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($rd)
                                    <span class="text-success small"><i class="fas fa-check me-1"></i>Done</span>
                                    <button class="btn btn-outline-secondary btn-sm py-0 px-2 ms-1"
                                            id="capture-btn-{{ $i }}"
                                            onclick="doCapture({{ $i }})">
                                        <i class="fas fa-redo" style="font-size:.75rem;"></i>
                                    </button>
                                @elseif($captureCount === $i - 1)
                                    <button class="btn btn-{{ $meta['color'] }} btn-sm"
                                            id="capture-btn-{{ $i }}"
                                            onclick="doCapture({{ $i }})">
                                        <i class="fas fa-camera me-1"></i>Capture {{ $i }}
                                    </button>
                                @else
                                    <span class="text-muted small" id="capture-btn-{{ $i }}">Waiting…</span>
                                @endif
                            </td>
                        </tr>
                        @endfor
                    </tbody>
                </table>

                {{-- Progress summary --}}
                <div class="p-2 bg-light rounded small">
                    @if($captureCount === 0)
                        <i class="fas fa-info-circle text-{{ $meta['color'] }} me-1"></i>
                        Start the camera and take the first capture.
                    @elseif($captureCount < 3)
                        <i class="fas fa-spinner fa-spin text-{{ $meta['color'] }} me-1"></i>
                        {{ $captureCount }}/3 captures done. Continue capturing…
                    @else
                        <i class="fas fa-check-circle text-success me-1"></i>
                        All 3 captures complete.
                        @if($avgHex)
                            Average color:
                            <span style="display:inline-block;width:16px;height:10px;background:{{ $avgHex }};
                                         border:1px solid #999;border-radius:2px;vertical-align:middle;"></span>
                            <code style="font-size:10px;">{{ $avgHex }}</code>
                        @endif
                    @endif
                </div>

                {{-- All 3 done: show average and navigate --}}
                @if($captureCount >= 3)
                <div class="mt-3">
                    @if($avgHex)
                    <div class="d-flex align-items-center gap-3 p-3 border rounded mb-3">
                        <div style="width:56px;height:32px;background:{{ $avgHex }};border:2px solid #999;border-radius:4px;flex-shrink:0;"></div>
                        <div>
                            <div class="fw-bold text-{{ $meta['color'] }}">Averaged Color: {{ $avgHex }}</div>
                            <small class="text-muted">Used for final {{ $meta['label'] }} calculation</small>
                        </div>
                    </div>
                    @endif

                    @if($meta['next'])
                    <a href="{{ route('parameter-test.show', [$sample, $meta['next']]) }}"
                       class="btn btn-{{ $meta['color'] }} w-100">
                        <i class="fas fa-arrow-right me-1"></i>
                        Continue to {{ ucfirst($meta['next']) }} (P) Test
                    </a>
                    @else
                    <a href="{{ route('samples.show', $sample) }}"
                       class="btn btn-success w-100">
                        <i class="fas fa-check-circle me-1"></i>
                        All Parameters Done — View Sample
                    </a>
                    @endif
                </div>
                @endif

            </div>
        </div>{{-- /row --}}
    </div>
</div>

@endsection

@section('scripts')
<script>
const SAMPLE_ID  = {{ $sample->id }};
const PARAMETER  = '{{ $parameter }}';
const COLOR_KEY  = '{{ $meta['color'] }}';
const csrf       = document.querySelector('meta[name="csrf-token"]').content;
let   videoStream = null;
let   timerInterval = null;
let   captureCount = {{ $captureCount }};

// ── Camera ────────────────────────────────────────────────────────
function startCamera() {
    navigator.mediaDevices.getUserMedia({ video: { width: 320, height: 240 }, audio: false })
        .then(stream => {
            videoStream = stream;
            document.getElementById('webcam').srcObject = stream;
            const btn = document.getElementById('startCameraBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-check-circle"></i> Camera Active';
            btn.classList.replace('btn-outline-secondary', 'btn-success');
        })
        .catch(err => alert('Camera error: ' + err.message));
}

// ── Countdown timer ───────────────────────────────────────────────
function startTimer(seconds) {
    if (timerInterval) clearInterval(timerInterval);
    const display = document.getElementById('timerDisplay');
    const status  = document.getElementById('timerStatus');
    let remaining = seconds;

    function tick() {
        const m = Math.floor(remaining / 60);
        const s = String(remaining % 60).padStart(2, '0');
        if (display) display.textContent = m + ':' + s;
        if (remaining <= 0) {
            clearInterval(timerInterval);
            if (display) display.classList.add('text-success');
            if (status)  { status.textContent = '✓ Ready to capture!'; status.className = 'small mt-1 text-success fw-bold'; }
        }
        remaining--;
    }
    tick();
    timerInterval = setInterval(tick, 1000);
    if (status) { status.textContent = 'Timer running…'; status.className = 'small mt-1 text-primary'; }
}

// ── Capture ───────────────────────────────────────────────────────
function doCapture(captureNumber) {
    if (!videoStream) { alert('Start the camera first.'); return; }

    const video  = document.getElementById('webcam');
    const canvas = document.getElementById('snapshot');
    const ctx    = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    // Save the full frame as a JPEG snapshot for the report
    const snapshot = canvas.toDataURL('image/jpeg', 0.80);

    const cx   = Math.floor(canvas.width  / 2) - 35;
    const cy   = Math.floor(canvas.height / 2) - 35;
    const data = ctx.getImageData(cx, cy, 70, 70).data;
    let r = 0, g = 0, b = 0, n = 0;
    for (let i = 0; i < data.length; i += 4) { r += data[i]; g += data[i+1]; b += data[i+2]; n++; }
    r = Math.round(r/n); g = Math.round(g/n); b = Math.round(b/n);
    const hex = '#' + [r, g, b].map(v => v.toString(16).padStart(2, '0')).join('').toUpperCase();

    const btn = document.getElementById('capture-btn-' + captureNumber);
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…'; }

    fetch('{{ route("color-readings.store") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify({
            sample_id: SAMPLE_ID,
            parameter: PARAMETER,
            color_hex: hex,
            r, g, b,
            test_number: captureNumber,
            snapshot
        })
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) { alert('Error: ' + data.message); return; }
        captureCount = data.tests_done;
        setTimeout(() => location.reload(), 400);
    })
    .catch(() => {
        alert('Network error — please try again.');
        if (btn) { btn.disabled = false; btn.innerHTML = `<i class="fas fa-camera me-1"></i>Capture ${captureNumber}`; }
    });
}
</script>
@endsection
