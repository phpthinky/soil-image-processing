@extends('layouts.app')
@section('title', 'pH Test — ' . $sample->sample_name)
@section('content')

{{-- ── Header breadcrumb ──────────────────────────────────────── --}}
<div class="row mb-3 align-items-center">
    <div class="col">
        <a href="{{ route('samples.show', $sample) }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Sample
        </a>
    </div>
    @if($phTest->status !== 'step1')
    <div class="col-auto">
        <form method="POST" action="{{ route('ph-test.reset', $sample) }}"
              onsubmit="return confirm('Reset pH test? All captured readings will be lost.')">
            @csrf
            <button class="btn btn-sm btn-outline-warning">
                <i class="fas fa-redo me-1"></i> Reset pH Test
            </button>
        </form>
    </div>
    @endif
</div>

{{-- ── Sample info strip ──────────────────────────────────────── --}}
<div class="card mb-4">
    <div class="card-body py-2">
        <div class="row align-items-center">
            <div class="col-md-8">
                <strong class="text-success"><i class="fas fa-vial me-1"></i>{{ $sample->sample_name }}</strong>
                <span class="mx-2 text-muted">·</span>{{ $sample->farmer_name }}
                <span class="mx-2 text-muted">·</span>{{ $sample->address }}
            </div>
            <div class="col-md-4 text-md-end">
                <span class="text-muted small">Date tested: {{ $sample->date_tested->format('F j, Y') }}</span>
            </div>
        </div>
    </div>
</div>

{{-- ── Progress wizard indicator ─────────────────────────────── --}}
@php
$steps = [
    ['key' => 'step1',    'label' => 'Step 1', 'sub' => 'CPR Solution',    'icon' => 'fa-flask'],
    ['key' => 'decided',  'label' => 'Decision','sub' => 'Select Solution', 'icon' => 'fa-code-branch'],
    ['key' => 'step2',    'label' => 'Step 2', 'sub' => 'BCG / BTB',       'icon' => 'fa-vial'],
    ['key' => 'complete', 'label' => 'Result',  'sub' => 'Final pH',        'icon' => 'fa-check-circle'],
];
$statusOrder = ['step1' => 0, 'retest' => 1, 'step2' => 2, 'complete' => 3];
$current     = $statusOrder[$phTest->status] ?? 0;
@endphp

<div class="d-flex align-items-start mb-4" id="wizard">
    @foreach($steps as $i => $step)
    @php
        $done    = ($i < $current) || ($step['key'] === 'decided' && $current >= 2);
        $active  = ($step['key'] === $phTest->status) ||
                   ($step['key'] === 'decided' && in_array($phTest->status, ['step2', 'complete']));
        $locked  = !$done && !$active;
    @endphp
    <div class="text-center flex-fill">
        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-1"
             style="width:46px;height:46px;font-size:1.1rem;
                    background:{{ $done ? '#388e3c' : ($active ? '#fff3cd' : '#dee2e6') }};
                    border:2px solid {{ $done ? '#2e7d32' : ($active ? '#ffc107' : '#adb5bd') }};
                    color:{{ $done ? '#fff' : ($active ? '#856404' : '#6c757d') }};">
            <i class="fas {{ $step['icon'] }}"></i>
        </div>
        <div class="small fw-bold {{ $locked ? 'text-muted' : '' }}">{{ $step['label'] }}</div>
        <div style="font-size:10px;" class="text-muted">{{ $step['sub'] }}</div>
    </div>
    @if($i < count($steps) - 1)
    <div class="flex-fill" style="border-top:2px solid {{ $done ? '#2e7d32' : '#dee2e6' }};margin-top:22px;"></div>
    @endif
    @endforeach
</div>

{{-- ═══════════════════════════════════════════════════════════════
     STEP 1 — CPR Solution
═════════════════════════════════════════════════════════════════ --}}
@if(in_array($phTest->status, ['step1', 'retest']))
@php $s1count = $phTest->step1Count(); @endphp

<div class="card border-primary mb-4">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-flask me-2"></i>Step 1 — CPR Solution (Cresol Red Purple)</h5>
        <span class="badge bg-white text-primary">{{ $s1count }}/3 captures</span>
    </div>
    <div class="card-body">

        @if($phTest->status === 'retest')
        <div class="alert alert-warning mb-3">
            <h6 class="alert-heading"><i class="fas fa-exclamation-triangle me-1"></i>Retest Required</h6>
            pH1 reading of <strong>{{ $phTest->step1_ph }}</strong> is outside the BCG (5.0–5.4) and BTB (5.8–6.0) ranges.
            Please repeat the CPR test with a fresh sample or verify the soil strip placement.
            Reset the test using the button above to start over.
        </div>
        @else
        <div class="alert alert-info mb-3">
            <h6 class="alert-heading mb-2"><i class="fas fa-info-circle me-1"></i>CPR Protocol (BSWM)</h6>
            <ol class="mb-0 small ps-3">
                <li>Transfer soil sample to 1st scratch mark (~0.5 g) in a clean test tube.</li>
                <li>Fill with <strong>CPR reagent</strong> to the 2nd scratch mark (~1 mL).</li>
                <li>Mix well by tapping into palm for <strong>1 minute</strong>.</li>
                <li>Let stand for <strong>2 minutes</strong>, then mix again for 1 minute.</li>
                <li>Let stand for <strong>5 minutes</strong>.</li>
                <li>Insert the test tube into the image capturing box for color capture.</li>
                <li>Take <strong>3 captures</strong> for accuracy.</li>
            </ol>
        </div>
        @endif

        {{-- Timer + camera --}}
        <div class="row">
            <div class="col-md-5 text-center mb-3">
                {{-- Reaction timer --}}
                <div class="mb-3">
                    <div id="timerDisplay1" class="display-5 fw-bold text-primary">8:00</div>
                    <div class="text-muted small mb-2">Mix 1 min → wait 2 min → mix 1 min → wait 5 min</div>
                    <button class="btn btn-outline-primary btn-sm" onclick="startTimer(1, 480)">
                        <i class="fas fa-play me-1"></i> Start Timer
                    </button>
                    <div id="timerStatus1" class="small mt-1 text-muted"></div>
                </div>

                {{-- Webcam --}}
                <div style="position:relative;display:inline-block;">
                    <video id="webcam" width="320" height="240" autoplay playsinline
                           style="border:2px solid #0d6efd;border-radius:8px;display:block;"></video>
                    {{-- Capture-zone crosshair: 70×70 px box matching the JS getImageData crop --}}
                    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);
                                width:70px;height:70px;border:2px solid #fff;box-shadow:0 0 0 1px #0d6efd,inset 0 0 0 1px #0d6efd;
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
                    <i class="fas fa-video"></i> Start Camera
                </button>
            </div>

            <div class="col-md-7">
                <table class="table table-bordered align-middle table-sm">
                    <thead class="table-primary">
                        <tr>
                            <th>Capture</th>
                            <th class="text-center">Photo</th>
                            <th class="text-center">System Color</th>
                            <th class="text-center">Hex Value</th>
                        <th class="text-center">Scientific Raw pH</th>
                        <th class="text-center">Nearest Chart pH</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for($i = 1; $i <= 3; $i++)
                        @php $rd = $phTest->step1_readings[$i-1] ?? null; @endphp
                        <tr>
                            <td class="fw-bold">{{ $i }}</td>
                            <td class="text-center">
                                @if($rd && !empty($rd['image']))
                                    <img src="{{ asset($rd['image']) }}" width="48" height="36"
                                         style="border-radius:3px;border:1px solid #ccc;object-fit:cover;"
                                         title="Capture {{ $i }}">
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($rd)
                                    <div style="width:38px;height:22px;background:{{ $rd['hex'] }};
                                                border:1px solid #ccc;border-radius:3px;margin:0 auto;"></div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($rd)
                                    <code style="font-size:10px;">{{ $rd['hex'] }}</code>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($rd)
                                    <span class="text-muted" style="font-size:11px;">{{ number_format($rd['computed_value'], 2) }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($rd)
                                    <strong class="text-primary fs-6">{{ number_format($rd['chart_ph'] ?? \App\Services\PhTestService::snapToChartPh($rd['computed_value'], 'CPR'), 1) }}</strong>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($rd)
                                    <span class="text-success small"><i class="fas fa-check"></i></span>
                                @elseif($s1count === $i - 1)
                                    <button class="btn btn-primary btn-sm"
                                            onclick="captureStep(1, {{ $i }})"
                                            id="capture1-{{ $i }}">
                                        <i class="fas fa-camera me-1"></i>Capture {{ $i }}
                                    </button>
                                @else
                                    <span class="text-muted small">Waiting…</span>
                                @endif
                            </td>
                        </tr>
                        @endfor
                    </tbody>
                </table>

                @if($s1count > 0)
                <div class="mt-2 p-2 bg-light rounded small">
                    @if($s1count < 3)
                        <i class="fas fa-spinner fa-spin text-primary me-1"></i>
                        {{ $s1count }}/3 captures done. Continue capturing…
                    @else
                        <i class="fas fa-check-circle text-success me-1"></i>
                        All 3 captures complete.
                        <strong>Chart pH = {{ number_format($phTest->step1_chart_ph ?? \App\Services\PhTestService::snapToChartPh($phTest->step1_ph, 'CPR'), 1) }}</strong>
                        &nbsp;<span class="text-muted">(Scientific: {{ number_format($phTest->step1_ph, 2) }},
                        {{ $phTest->step1_confidence }} confidence, variance {{ number_format($phTest->step1_variance, 4) }})</span>
                    @endif
                </div>
                @endif

                @if($phTest->step1_outcome)
                @php
                $s1badge = match($phTest->step1_outcome) {
                    'win-bcg', 'win-btb' => ['bg-success', 'fa-trophy', 'Win — Proceed to Step 2', 'success'],
                    'win-cpr'            => ['bg-success', 'fa-check-circle', 'Win — CPR Result is Final', 'success'],
                    'retest'             => ['bg-warning text-dark', 'fa-exclamation-triangle', 'Retest Required', 'warning'],
                    'high-acid'          => ['bg-danger', 'fa-exclamation-circle', 'Highly Acidic — Retest', 'danger'],
                    'alkaline'           => ['bg-info', 'fa-exclamation-circle', 'Alkaline — Retest', 'info'],
                    default              => ['bg-secondary', 'fa-info-circle', 'Pending', 'secondary'],
                };
                @endphp
                <div class="mt-2">
                    <span class="badge {{ $s1badge[0] }} fs-6 px-3 py-2">
                        <i class="fas {{ $s1badge[1] }} me-1"></i>{{ $s1badge[2] }}
                    </span>
                    <div class="alert alert-{{ $s1badge[3] }} mt-2 py-2 small">
                        <i class="fas fa-comment-alt me-1"></i><strong>Technician Remarks:</strong>
                        {{ $phTest->step1_remarks }}
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

{{-- ═══════════════════════════════════════════════════════════════
     DECISION — after Step 1 completes
═════════════════════════════════════════════════════════════════ --}}
@if(in_array($phTest->status, ['step2', 'complete']))
<div class="card border-warning mb-4">
    <div class="card-header bg-warning text-dark">
        <h5 class="mb-0"><i class="fas fa-code-branch me-2"></i>Decision — Next Solution</h5>
    </div>
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-5">
                <div class="text-center p-3 border rounded">
                    <div class="text-muted small mb-1">Step 1 (CPR) pH Reading</div>
                    <div class="display-5 fw-bold text-primary">{{ number_format($phTest->step1_ph, 2) }}</div>
                    <div class="mt-2">
                        <span class="badge bg-{{ $phTest->step1_confidence === 'High' ? 'success' : 'warning' }}">
                            {{ $phTest->step1_confidence }} Confidence
                        </span>
                        <span class="badge bg-secondary ms-1">Variance: {{ number_format($phTest->step1_variance, 4) }}</span>
                    </div>
                    @php
                    $s1readings = $phTest->step1_readings ?? [];
                    @endphp
                    <div class="d-flex justify-content-center gap-2 mt-2">
                        @foreach($s1readings as $rd)
                        <div style="width:28px;height:16px;background:{{ $rd['hex'] }};border:1px solid #ccc;border-radius:2px;" title="{{ $rd['hex'] }}"></div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="col-md-7">
                <div class="alert alert-{{ $phTest->next_solution === 'BCG' ? 'success' : ($phTest->next_solution === 'BTB' ? 'info' : 'danger') }} mb-0">
                    <h6 class="alert-heading mb-2">
                        <i class="fas fa-flask me-1"></i>
                        Use: <strong>{{ $phTest->next_solution }}</strong>
                    </h6>
                    <p class="mb-1 small">{{ app(\App\Services\PhTestService::class)->solutionDescription($phTest->next_solution) }}</p>
                    @if($phTest->next_solution === 'BCG')
                        <p class="mb-0 small text-muted">BCG covers pH 4.5–5.4. Acidic soils typically need lime amendment.</p>
                    @elseif($phTest->next_solution === 'BTB')
                        <p class="mb-0 small text-muted">BTB covers pH 5.8–7.0. Near-neutral soils are generally suitable for most crops.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- ═══════════════════════════════════════════════════════════════
     STEP 2 — BCG or BTB
═════════════════════════════════════════════════════════════════ --}}
@if($phTest->status === 'step2')
@php
$s2count  = $phTest->step2Count();
$solution = $phTest->step2_solution;
$timer    = app(\App\Services\PhTestService::class)->reactionTimer($solution);
$timerMin = (int)($timer / 60);
$timerSec = str_pad($timer % 60, 2, '0', STR_PAD_LEFT);
@endphp

<div class="card border-success mb-4">
    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-vial me-2"></i>Step 2 — {{ $solution }} Solution
        </h5>
        <span class="badge bg-white text-success">{{ $s2count }}/3 captures</span>
    </div>
    <div class="card-body">
        <div class="alert alert-info mb-3">
            <h6 class="alert-heading mb-2">
                <i class="fas fa-info-circle me-1"></i>
                {{ $solution }} ({{ $solution === 'BCG' ? 'Bromocresol Green' : 'Bromothymol Blue' }}) Protocol (BSWM)
            </h6>
            <ol class="mb-0 small ps-3">
                <li>Transfer <strong>fresh soil sample</strong> to 1st scratch mark (~0.5 g) in a clean dry test tube.</li>
                <li>Fill with <strong>{{ $solution }} reagent</strong> to the 2nd scratch mark (~1 mL).</li>
                <li>Mix well by tapping into palm for <strong>1 minute</strong>.</li>
                <li>Let stand for <strong>2 minutes</strong>, then mix again for 1 minute.</li>
                <li>Let stand for <strong>5 minutes</strong>.</li>
                <li>Insert the test tube into the image capturing box for color capture.</li>
                <li>Take <strong>3 captures</strong> for accuracy. (If color is between two values, the average is recorded.)</li>
            </ol>
        </div>

        <div class="row">
            <div class="col-md-5 text-center mb-3">
                <div class="mb-3">
                    <div id="timerDisplay2" class="display-5 fw-bold text-success">{{ $timerMin }}:{{ $timerSec }}</div>
                    <div class="text-muted small mb-2">Reaction timer ({{ $solution }} color development)</div>
                    <button class="btn btn-outline-success btn-sm" onclick="startTimer(2, {{ $timer }})">
                        <i class="fas fa-play me-1"></i> Start Timer
                    </button>
                    <div id="timerStatus2" class="small mt-1 text-muted"></div>
                </div>

                <div style="position:relative;display:inline-block;">
                    <video id="webcam" width="320" height="240" autoplay playsinline
                           style="border:2px solid #388e3c;border-radius:8px;display:block;"></video>
                    {{-- Capture-zone crosshair: 70×70 px box matching the JS getImageData crop --}}
                    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);
                                width:70px;height:70px;border:2px solid #fff;box-shadow:0 0 0 1px #388e3c,inset 0 0 0 1px #388e3c;
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
                    <i class="fas fa-video"></i> Start Camera
                </button>
            </div>

            <div class="col-md-7">
                <table class="table table-bordered align-middle table-sm">
                    <thead class="table-success">
                        <tr>
                            <th>Capture</th>
                            <th class="text-center">Photo</th>
                            <th class="text-center">System Color</th>
                            <th class="text-center">Hex Value</th>

                        <th class="text-center">Scientific Raw pH</th>
                        <th class="text-center">Nearest Chart pH</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for($i = 1; $i <= 3; $i++)
                        @php $rd = $phTest->step2_readings[$i-1] ?? null; @endphp
                        <tr>
                            <td class="fw-bold">{{ $i }}</td>
                            <td class="text-center">
                                @if($rd && !empty($rd['image']))
                                    <img src="{{ asset($rd['image']) }}" width="48" height="36"
                                         style="border-radius:3px;border:1px solid #ccc;object-fit:cover;"
                                         title="Capture {{ $i }}">
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($rd)
                                    <div style="width:38px;height:22px;background:{{ $rd['hex'] }};
                                                border:1px solid #ccc;border-radius:3px;margin:0 auto;"></div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($rd)
                                    <code style="font-size:10px;">{{ $rd['hex'] }}</code>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($rd)
                                    <span class="text-muted" style="font-size:11px;">{{ number_format($rd['computed_value'], 2) }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($rd)
                                    <strong class="text-success fs-6">{{ number_format($rd['chart_ph'] ?? \App\Services\PhTestService::snapToChartPh($rd['computed_value'], $phTest->step2_solution ?? 'CPR'), 1) }}</strong>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($rd)
                                    <span class="text-success small"><i class="fas fa-check"></i></span>
                                @elseif($s2count === $i - 1)
                                    <button class="btn btn-success btn-sm"
                                            onclick="captureStep(2, {{ $i }})"
                                            id="capture2-{{ $i }}">
                                        <i class="fas fa-camera me-1"></i>Capture {{ $i }}
                                    </button>
                                @else
                                    <span class="text-muted small">Waiting…</span>
                                @endif
                            </td>
                        </tr>
                        @endfor
                    </tbody>
                </table>

                @if($s2count > 0)
                <div class="mt-2 p-2 bg-light rounded small">
                    @if($s2count < 3)
                        <i class="fas fa-spinner fa-spin text-success me-1"></i>
                        {{ $s2count }}/3 captures done. Continue capturing…
                    @else
                        <i class="fas fa-check-circle text-success me-1"></i>
                        All 3 captures complete.
                        <strong>Chart pH = {{ number_format($phTest->step2_chart_ph ?? \App\Services\PhTestService::snapToChartPh($phTest->step2_ph, $phTest->step2_solution ?? 'CPR'), 1) }}</strong>
                        &nbsp;<span class="text-muted">(Scientific: {{ number_format($phTest->step2_ph, 2) }},
                        {{ $phTest->step2_confidence }} confidence, variance {{ number_format($phTest->step2_variance, 4) }})</span>
                    @endif
                </div>
                @endif

                @if($phTest->step2_outcome)
                @php
                $s2badge = match($phTest->step2_outcome) {
                    'confirmed'    => ['bg-success', 'fa-check-double'],
                    'borderline'   => ['bg-warning text-dark', 'fa-adjust'],
                    'inconsistent' => ['bg-danger', 'fa-times-circle'],
                    default        => ['bg-secondary', 'fa-info-circle'],
                };
                @endphp
                <div class="mt-2">
                    <span class="badge {{ $s2badge[0] }} fs-6 px-3 py-2">
                        <i class="fas {{ $s2badge[1] }} me-1"></i>
                        @if($phTest->step2_outcome === 'confirmed') Win — pH Confirmed
                        @elseif($phTest->step2_outcome === 'borderline') Borderline Result
                        @elseif($phTest->step2_outcome === 'inconsistent') Inconsistent — Review
                        @endif
                    </span>
                    <div class="alert alert-{{ $phTest->step2_outcome === 'confirmed' ? 'success' : ($phTest->step2_outcome === 'borderline' ? 'warning' : 'danger') }} mt-2 py-2 small">
                        <i class="fas fa-comment-alt me-1"></i><strong>Technician Remarks:</strong>
                        {{ $phTest->step2_remarks }}
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

{{-- ═══════════════════════════════════════════════════════════════
     RESULT — Complete
═════════════════════════════════════════════════════════════════ --}}
@if($phTest->status === 'complete')
@php
$s1readings  = $phTest->step1_readings ?? [];
$s2readings  = $phTest->step2_readings ?? [];
$avgHex      = $sample->ph_color_hex;
$finalSol    = $phTest->step2_solution ?? 'CPR';
// Chart pH: use step2 if available, otherwise step1 (CPR-final path)
$finalChart  = $phTest->step2_chart_ph
    ?? $phTest->step1_chart_ph
    ?? \App\Services\PhTestService::snapToChartPh($phTest->final_ph, $finalSol);
$finalSci    = $phTest->final_ph;
$interpretation = \App\Services\PhTestService::phInterpretation($finalChart);
// Average confidence_pct across the final step readings
$finalReadings = !empty($s2readings) ? $s2readings : $s1readings;
$confPcts = array_filter(array_column($finalReadings, 'confidence_pct'));
$avgConfPct = !empty($confPcts) ? (int) round(array_sum($confPcts) / count($confPcts)) : null;
@endphp

<div class="card border-success mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>pH Test Complete</h5>
    </div>
    <div class="card-body">

        {{-- Summary row: Step1 | Solution | Step2 | Final (big panel) --}}
        <div class="row text-center g-3 align-items-stretch">
            <div class="col-md-3">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">Step 1 (CPR)</div>
                    <div class="fs-4 fw-bold text-primary">
                        {{ number_format($phTest->step1_chart_ph ?? \App\Services\PhTestService::snapToChartPh($phTest->step1_ph, 'CPR'), 1) }}
                    </div>
                    <div class="text-muted" style="font-size:11px;">Scientific: {{ number_format($phTest->step1_ph, 2) }}</div>
                    <span class="badge bg-{{ $phTest->step1_confidence === 'High' ? 'success' : 'warning' }} mt-1">
                        {{ $phTest->step1_confidence }} confidence
                    </span>
                </div>
            </div>
            <div class="col-md-2">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">Solution Used</div>
                    <div class="fs-4 fw-bold text-warning">{{ $finalSol }}</div>
                    <span class="badge bg-secondary mt-1" style="font-size:9px;">
                        {{ $finalSol === 'BCG' ? 'Bromocresol Green' : ($finalSol === 'BTB' ? 'Bromothymol Blue' : 'Cresol Red Purple') }}
                    </span>
                </div>
            </div>
            @if($phTest->step2_ph)
            <div class="col-md-2">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">Step 2 ({{ $finalSol }})</div>
                    <div class="fs-4 fw-bold text-success">
                        {{ number_format($phTest->step2_chart_ph ?? \App\Services\PhTestService::snapToChartPh($phTest->step2_ph, $finalSol), 1) }}
                    </div>
                    <div class="text-muted" style="font-size:11px;">Scientific: {{ number_format($phTest->step2_ph, 2) }}</div>
                    <span class="badge bg-{{ $phTest->step2_confidence === 'High' ? 'success' : 'warning' }} mt-1">
                        {{ $phTest->step2_confidence }} confidence
                    </span>
                </div>
            </div>
            @endif

            {{-- ── FINAL pH RESULT PANEL ─────────────────────────── --}}
            <div class="{{ $phTest->step2_ph ? 'col-md-5' : 'col-md-7' }}">
                <div class="border-3 border-success rounded p-3 h-100 bg-light text-center">
                    <div class="text-muted small fw-semibold mb-1 text-uppercase letter-spacing-1">Soil pH Result</div>

                    {{-- Big chart value --}}
                    <div class="display-3 fw-bold text-success lh-1">{{ number_format($finalChart, 1) }}</div>
                    <div class="text-muted small fw-semibold mb-2">Chart Value
                        @if($avgHex)
                        <div style="width:44px;height:22px;background:{{ $avgHex }};border:1px solid #aaa;
                                    border-radius:3px;display:inline-block;vertical-align:middle;margin-left:6px;"></div>
                        @endif
                    </div>

                    {{-- Scientific raw value --}}
                    <div class="text-muted mb-1" style="font-size:12px;">
                        <i class="fas fa-microscope me-1"></i>
                        Scientific Reading: <strong>{{ number_format($finalSci, 2) }}</strong>
                    </div>

                    {{-- Color match confidence --}}
                    @if($avgConfPct !== null)
                    <div class="mb-2" style="font-size:12px;">
                        <i class="fas fa-palette me-1 text-info"></i>
                        Color Match Confidence:
                        <strong class="text-{{ $avgConfPct >= 75 ? 'success' : ($avgConfPct >= 50 ? 'warning' : 'danger') }}">
                            {{ $avgConfPct }}%
                        </strong>
                    </div>
                    @endif

                    {{-- Interpretation --}}
                    <div class="mt-1 px-2 py-1 rounded"
                         style="background:rgba(0,0,0,.05);font-size:12px;">
                        <i class="fas fa-leaf me-1 text-success"></i>
                        <em>Interpretation:</em><br>
                        <strong>{{ $interpretation }}</strong>
                    </div>
                </div>
            </div>
        </div>

        {{-- Individual captures: Step 1 + Step 2 in table format --}}
        @foreach([['label' => 'Step 1 (CPR)', 'readings' => $s1readings, 'sol' => 'CPR', 'color' => 'primary'],
                  ['label' => 'Step 2 (' . $finalSol . ')', 'readings' => $s2readings, 'sol' => $finalSol, 'color' => 'success']]
                 as $step)
        @if(count($step['readings']) > 0)
        <div class="mt-4">
            <h6 class="text-muted fw-semibold">{{ $step['label'] }} — Individual Captures</h6>
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle mb-0">
                    <thead class="table-{{ $step['color'] }}">
                        <tr>
                            <th>Capture</th>
                            <th class="text-center">Captured Photo</th>
                            <th class="text-center">System Color</th>
                            <th class="text-center">Hex Value</th>
                            
                        <th class="text-center">Scientific Raw pH</th>
                        <th class="text-center">Nearest Chart pH</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($step['readings'] as $i => $rd)
                        <tr>
                            <td class="fw-bold">Capture {{ $i + 1 }}</td>
                            <td class="text-center">
                                @if(!empty($rd['image']))
                                    <img src="{{ asset($rd['image']) }}" width="64" height="48"
                                         style="border-radius:4px;border:1px solid #ccc;object-fit:cover;">
                                @else
                                    <span class="text-muted small">No photo</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div style="width:44px;height:26px;background:{{ $rd['hex'] }};border:1px solid #ccc;
                                            border-radius:3px;margin:0 auto;"></div>
                            </td>
                            <td class="text-center"><code style="font-size:10px;">{{ $rd['hex'] }}</code></td>
                            <td class="text-center text-muted" style="font-size:12px;">{{ number_format($rd['computed_value'], 2) }}</td>
                            <td class="text-center">
                                <strong class="text-{{ $step['color'] }} fs-6">
                                    {{ number_format($rd['chart_ph'] ?? \App\Services\PhTestService::snapToChartPh($rd['computed_value'], $step['sol']), 1) }}
                                </strong>
                                @if(!empty($rd['confidence_pct']))
                                <div style="font-size:10px;" class="text-muted">{{ $rd['confidence_pct'] }}% match</div>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
        @endforeach

        {{-- Outcome summary panel --}}
        @if($phTest->step1_remarks || $phTest->step2_remarks)
        <div class="mt-4">
            <h6 class="fw-bold"><i class="fas fa-clipboard-check me-1 text-success"></i>Test Outcome Summary</h6>
            <div class="row g-2">
                @if($phTest->step1_remarks)
                <div class="col-md-6">
                    <div class="card border-0 bg-light h-100">
                        <div class="card-body py-2 px-3">
                            <div class="d-flex align-items-center mb-1">
                                <span class="badge bg-{{ in_array($phTest->step1_outcome, ['win-bcg','win-btb','win-cpr']) ? 'success' : 'warning text-dark' }} me-2">Step 1 (CPR)</span>
                                @if(in_array($phTest->step1_outcome, ['win-bcg','win-btb']))
                                    <span class="text-success fw-bold small"><i class="fas fa-trophy me-1"></i>Win — Proceed to Step 2</span>
                                @elseif($phTest->step1_outcome === 'win-cpr')
                                    <span class="text-success fw-bold small"><i class="fas fa-check-circle me-1"></i>Win — CPR Final</span>
                                @else
                                    <span class="text-warning fw-bold small"><i class="fas fa-exclamation-triangle me-1"></i>Retest</span>
                                @endif
                            </div>
                            <p class="mb-0 small text-muted">{{ $phTest->step1_remarks }}</p>
                        </div>
                    </div>
                </div>
                @endif
                @if($phTest->step2_remarks)
                <div class="col-md-6">
                    <div class="card border-0 bg-light h-100">
                        <div class="card-body py-2 px-3">
                            <div class="d-flex align-items-center mb-1">
                                <span class="badge bg-{{ $phTest->step2_outcome === 'confirmed' ? 'success' : ($phTest->step2_outcome === 'borderline' ? 'warning text-dark' : 'danger') }} me-2">Step 2 ({{ $phTest->step2_solution }})</span>
                                @if($phTest->step2_outcome === 'confirmed')
                                    <span class="text-success fw-bold small"><i class="fas fa-check-double me-1"></i>Win — Confirmed</span>
                                @elseif($phTest->step2_outcome === 'borderline')
                                    <span class="text-warning fw-bold small"><i class="fas fa-adjust me-1"></i>Borderline</span>
                                @else
                                    <span class="text-danger fw-bold small"><i class="fas fa-times-circle me-1"></i>Inconsistent</span>
                                @endif
                            </div>
                            <p class="mb-0 small text-muted">{{ $phTest->step2_remarks }}</p>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif

        <div class="alert alert-success mt-4 mb-0">
            <i class="fas fa-check-circle me-1"></i>
            <strong>Chart pH = {{ number_format($finalChart, 1) }}</strong>
            <span class="text-muted">(Scientific: {{ number_format($finalSci, 2) }})</span>
            has been saved and will be used in soil fertility and crop recommendation calculations.
        </div>
    </div>
</div>

<div class="text-end mb-4">
    <a href="{{ route('samples.show', $sample) }}" class="btn btn-success">
        <i class="fas fa-arrow-right me-1"></i> Continue to N/P/K Capture
    </a>
</div>
@endif

@endsection

@section('scripts')
<script>
const sampleId  = {{ $sample->id }};
const csrf      = document.querySelector('meta[name="csrf-token"]').content;
let   videoStream = null;
let   timerInterval = null;

// ── Camera ────────────────────────────────────────────────────────
function startCamera() {
    navigator.mediaDevices.getUserMedia({ video: { width: 320, height: 240 }, audio: false })
        .then(stream => {
            videoStream = stream;
            document.querySelectorAll('video#webcam').forEach(v => v.srcObject = stream);
            const btn = document.getElementById('startCameraBtn');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-check-circle"></i> Camera Active';
                btn.classList.replace('btn-outline-secondary', 'btn-success');
            }
        })
        .catch(err => alert('Camera error: ' + err.message));
}

// ── Countdown timer ───────────────────────────────────────────────
function startTimer(step, seconds) {
    if (timerInterval) clearInterval(timerInterval);
    const display = document.getElementById('timerDisplay' + step);
    const status  = document.getElementById('timerStatus' + step);
    let remaining = seconds;

    function tick() {
        const m = Math.floor(remaining / 60);
        const s = String(remaining % 60).padStart(2, '0');
        if (display) display.textContent = m + ':' + s;
        if (remaining <= 0) {
            clearInterval(timerInterval);
            if (display) { display.textContent = '0:00'; display.classList.replace('text-primary','text-success'); }
            if (status) { status.textContent = '✓ Ready to capture!'; status.className = 'small mt-1 text-success fw-bold'; }
        }
        remaining--;
    }
    tick();
    timerInterval = setInterval(tick, 1000);
    if (status) { status.textContent = 'Timer running…'; status.className = 'small mt-1 text-primary'; }
}

// ── Capture ───────────────────────────────────────────────────────
function captureStep(step, captureNumber) {
    if (!videoStream) { alert('Start the camera first.'); return; }

    const video  = document.querySelector('video#webcam');
    const canvas = document.getElementById('snapshot');
    const ctx    = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    // Save the full frame as a JPEG snapshot for the report
    const snapshot = canvas.toDataURL('image/jpeg', 0.80);

    const cx   = Math.floor(canvas.width / 2) - 35;
    const cy   = Math.floor(canvas.height / 2) - 35;
    const data = ctx.getImageData(cx, cy, 70, 70).data;
    let r = 0, g = 0, b = 0, n = 0;
    for (let i = 0; i < data.length; i += 4) { r += data[i]; g += data[i+1]; b += data[i+2]; n++; }
    r = Math.round(r/n); g = Math.round(g/n); b = Math.round(b/n);
    const hex = '#' + [r,g,b].map(v => v.toString(16).padStart(2,'0')).join('').toUpperCase();

    const btn = document.getElementById('capture' + step + '-' + captureNumber);
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…'; }

    fetch('{{ route("ph-test.capture") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify({ sample_id: sampleId, step, color_hex: hex, r, g, b, snapshot })
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) { alert('Error: ' + data.message); return; }
        if (data.reload) setTimeout(() => location.reload(), 500);
    })
    .catch(() => {
        alert('Network error — please try again.');
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-camera"></i> Capture ' + captureNumber; }
    });
}
</script>
@endsection
