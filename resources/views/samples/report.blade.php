@extends('layouts.app')
@section('title', 'Test Report — ' . $sample->sample_name)
@section('content')

<div class="row mb-3">
    <div class="col">
        <a href="{{ route('samples.show', $sample) }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Sample
        </a>
        @if($sample->isAnalyzed())
        <a href="{{ route('export', ['sample_id' => $sample->id]) }}" class="btn btn-sm btn-success ms-2">
            <i class="fas fa-file-excel"></i> Export to Excel
        </a>
        <a href="{{ route('samples.pdf', $sample) }}" target="_blank" class="btn btn-sm btn-primary ms-2">
            <i class="fas fa-print"></i> Print / Save as PDF
        </a>
        @endif
    </div>
</div>

{{-- Header card --}}
<div class="card mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="fas fa-microscope me-2"></i>Test Report — {{ $sample->sample_name }}</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <p class="mb-1"><strong>Farmer:</strong> {{ $sample->farmer_name }}</p>
                <p class="mb-1"><strong>Address:</strong> {{ $sample->address }}</p>
                @if($sample->location)
                <p class="mb-1"><strong>Farm Location:</strong> {{ $sample->location }}</p>
                @endif
            </div>
            <div class="col-md-4">
                <p class="mb-1"><strong>Date Received:</strong> {{ $sample->sample_date->format('F j, Y') }}</p>
                <p class="mb-1"><strong>Date Tested:</strong> {{ $sample->date_tested->format('F j, Y') }}</p>
                @if($sample->analyzed_at)
                <p class="mb-1"><strong>Analyzed:</strong> {{ $sample->analyzed_at->format('F j, Y g:i A') }}</p>
                @endif
            </div>
            <div class="col-md-4">
                <p class="mb-1"><strong>Tests Captured:</strong> {{ $sample->tests_completed ?? 0 }}/12</p>
                @if(!is_null($sample->fertility_score))
                <p class="mb-1">
                    <strong>Fertility Score:</strong>
                    <span class="badge bg-{{ $sample->fertilityColorClass() }} ms-1">{{ $sample->fertility_score }}%</span>
                </p>
                @endif
                <p class="mb-0">
                    <strong>Status:</strong>
                    @if($sample->isAnalyzed())
                        <span class="badge bg-success">Analyzed</span>
                    @else
                        <span class="badge bg-warning text-dark">Pending</span>
                    @endif
                </p>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     SOIL pH — Two-Stage BSWM Protocol Breakdown
══════════════════════════════════════════════════════════ --}}
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h6 class="mb-0">
            <i class="fas fa-flask me-2"></i>Soil pH
            @if($phTest)
                <span class="badge bg-white text-primary ms-2" style="font-size:.75rem;">
                    {{ $phTest->status === 'complete' ? 'Complete' : ucfirst($phTest->status) }}
                </span>
            @endif
        </h6>
    </div>
    <div class="card-body">

    @if(!$phTest || !$phTest->step1Count())
        <p class="text-muted mb-0"><em>No pH readings captured yet.</em></p>
    @else

    @php
        // Helper: render one stage's capture rows from the JSON readings array
        // $stageReadings = $phTest->step1_readings or step2_readings (array of dicts)
    @endphp

    {{-- ── Stage 1: CPR Solution ───────────────────────────────────── --}}
    <h6 class="fw-bold text-primary mb-2">
        <i class="fas fa-vial me-1"></i>Stage 1 — CPR Solution (Cresol Red Purple)
    </h6>
    <div class="row align-items-start mb-4">
        <div class="col-md-9">
            <table class="table table-bordered table-sm align-middle mb-0">
                <thead class="table-primary">
                    <tr>
                        <th style="width:90px;">Capture</th>
                        <th class="text-center" style="width:130px;">Captured Photo</th>
                        <th class="text-center" style="width:80px;">System Color</th>
                        <th class="text-center" style="width:100px;">Hex Value</th>
                        <th class="text-center">Computed pH</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(range(1,3) as $i)
                    @php $rd = $phTest->step1_readings[$i-1] ?? null; @endphp
                    <tr class="{{ $rd ? '' : 'table-light text-muted' }}">
                        <td class="fw-bold">Capture {{ $i }}</td>

                        {{-- Photo --}}
                        <td class="text-center p-1">
                            @if($rd && !empty($rd['image']))
                                <img src="{{ asset($rd['image']) }}"
                                     width="120" height="90"
                                     style="border-radius:4px;border:1px solid #ccc;object-fit:cover;cursor:pointer;"
                                     title="Click to enlarge"
                                     onclick="document.getElementById('imgS1-{{ $i }}').style.display='flex'">
                                <div id="imgS1-{{ $i }}"
                                     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);
                                            z-index:9999;align-items:center;justify-content:center;"
                                     onclick="this.style.display='none'">
                                    <img src="{{ asset($rd['image']) }}"
                                         style="max-width:90vw;max-height:90vh;border-radius:8px;border:3px solid #fff;">
                                </div>
                            @else
                                <div style="width:120px;height:90px;background:#f8f9fa;border:1px dashed #ccc;
                                            border-radius:4px;display:flex;align-items:center;justify-content:center;margin:0 auto;">
                                    <small class="text-muted" style="font-size:10px;">No image</small>
                                </div>
                            @endif
                        </td>

                        {{-- System color swatch --}}
                        <td class="text-center">
                            @if($rd)
                                <div style="width:44px;height:44px;background:{{ $rd['hex'] }};
                                            border:2px solid #ccc;border-radius:6px;margin:0 auto 2px;" title="{{ $rd['hex'] }}"></div>
                                <small class="text-muted" style="font-size:10px;">System</small>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>

                        <td class="text-center">
                            @if($rd)
                                <code style="font-size:12px;">{{ $rd['hex'] }}</code>
                            @else <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($rd)
                                <strong class="text-primary">{{ number_format($rd['computed_value'], 2) }}</strong>
                            @else <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Stage 1 summary --}}
        <div class="col-md-3 text-center">
            <p class="text-muted small fw-semibold mb-1">CPR Average pH</p>
            @if($phTest->step1_ph)
                <div class="fw-bold fs-4 text-primary mb-1">{{ number_format($phTest->step1_ph, 2) }}</div>
                <div class="mb-2">
                    <span class="badge bg-{{ $phTest->step1_confidence === 'High' ? 'success' : 'warning text-dark' }}">
                        {{ $phTest->step1_confidence }} Confidence
                    </span>
                </div>
                <hr class="my-2">
                <p class="small text-muted mb-1">Decision</p>
                <span class="badge bg-{{ in_array($phTest->next_solution,['BCG','BTB']) ? 'info' : ($phTest->next_solution === 'CPR' ? 'success' : 'danger') }} text-white">
                    @switch($phTest->next_solution)
                        @case('BCG') Proceed to BCG @break
                        @case('BTB') Proceed to BTB @break
                        @case('CPR') CPR Result is Final @break
                        @case('RETEST') Retest Required @break
                        @default Pending
                    @endswitch
                </span>
            @else
                <p class="text-muted small">Incomplete</p>
            @endif
        </div>
    </div>

    {{-- ── Stage 2: BCG or BTB ────────────────────────────────────── --}}
    @if($phTest->step2_readings && count($phTest->step2_readings))
    @php $sol = $phTest->step2_solution; @endphp
    <hr class="my-3">
    <h6 class="fw-bold text-success mb-2">
        <i class="fas fa-vial me-1"></i>Stage 2 — {{ $sol }}
        @if($sol === 'BCG') <small class="fw-normal text-muted">(Bromocresol Green — acidic range)</small>
        @elseif($sol === 'BTB') <small class="fw-normal text-muted">(Bromothymol Blue — near-neutral range)</small>
        @endif
    </h6>
    <div class="row align-items-start">
        <div class="col-md-9">
            <table class="table table-bordered table-sm align-middle mb-0">
                <thead class="table-success">
                    <tr>
                        <th style="width:90px;">Capture</th>
                        <th class="text-center" style="width:130px;">Captured Photo</th>
                        <th class="text-center" style="width:80px;">System Color</th>
                        <th class="text-center" style="width:100px;">Hex Value</th>
                        <th class="text-center">Computed pH</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(range(1,3) as $i)
                    @php $rd = $phTest->step2_readings[$i-1] ?? null; @endphp
                    <tr class="{{ $rd ? '' : 'table-light text-muted' }}">
                        <td class="fw-bold">Capture {{ $i }}</td>

                        {{-- Photo --}}
                        <td class="text-center p-1">
                            @if($rd && !empty($rd['image']))
                                <img src="{{ asset($rd['image']) }}"
                                     width="120" height="90"
                                     style="border-radius:4px;border:1px solid #ccc;object-fit:cover;cursor:pointer;"
                                     title="Click to enlarge"
                                     onclick="document.getElementById('imgS2-{{ $i }}').style.display='flex'">
                                <div id="imgS2-{{ $i }}"
                                     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);
                                            z-index:9999;align-items:center;justify-content:center;"
                                     onclick="this.style.display='none'">
                                    <img src="{{ asset($rd['image']) }}"
                                         style="max-width:90vw;max-height:90vh;border-radius:8px;border:3px solid #fff;">
                                </div>
                            @else
                                <div style="width:120px;height:90px;background:#f8f9fa;border:1px dashed #ccc;
                                            border-radius:4px;display:flex;align-items:center;justify-content:center;margin:0 auto;">
                                    <small class="text-muted" style="font-size:10px;">No image</small>
                                </div>
                            @endif
                        </td>

                        {{-- System color swatch --}}
                        <td class="text-center">
                            @if($rd)
                                <div style="width:44px;height:44px;background:{{ $rd['hex'] }};
                                            border:2px solid #ccc;border-radius:6px;margin:0 auto 2px;" title="{{ $rd['hex'] }}"></div>
                                <small class="text-muted" style="font-size:10px;">System</small>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>

                        <td class="text-center">
                            @if($rd)
                                <code style="font-size:12px;">{{ $rd['hex'] }}</code>
                            @else <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($rd)
                                <strong class="text-success">{{ number_format($rd['computed_value'], 2) }}</strong>
                            @else <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Stage 2 summary + final pH --}}
        <div class="col-md-3 text-center">
            <p class="text-muted small fw-semibold mb-1">{{ $sol }} Average pH</p>
            @if($phTest->step2_ph)
                <div class="fw-bold fs-4 text-success mb-1">{{ number_format($phTest->step2_ph, 2) }}</div>
                <div class="mb-2">
                    <span class="badge bg-{{ $phTest->step2_confidence === 'High' ? 'success' : 'warning text-dark' }}">
                        {{ $phTest->step2_confidence }} Confidence
                    </span>
                </div>
            @endif
            @if($phTest->final_ph)
                <hr class="my-2">
                <p class="text-muted small mb-0">Final pH Result</p>
                <div class="fw-bold fs-3 text-primary">{{ number_format($phTest->final_ph, 2) }}</div>
            @endif
        </div>
    </div>
    @elseif($phTest->next_solution === 'CPR' && $phTest->final_ph)
    {{-- CPR was final (pH 5.4–5.8 range) — no stage 2 needed --}}
    <div class="alert alert-success mt-2 mb-0 py-2">
        <i class="fas fa-check-circle me-1"></i>
        CPR result is final for this pH range (5.4–5.8).
        <strong>Final pH = {{ number_format($phTest->final_ph, 2) }}</strong>
    </div>
    @endif

    {{-- ── Final pH result banner ──────────────────────────────────── --}}
    @if($phTest && $phTest->final_ph)
    <hr class="my-3">
    <div class="d-flex align-items-center justify-content-between bg-primary bg-opacity-10
                border border-primary rounded px-4 py-3">
        <div>
            <span class="fw-semibold text-primary fs-6">Final pH Result</span><br>
            <small class="text-muted">
                Based on
                @if($phTest->next_solution === 'CPR') CPR (transitional range 5.4–5.8)
                @elseif($phTest->step2_solution === 'BCG') BCG — Stage 2 (acidic range ≤ 5.4)
                @elseif($phTest->step2_solution === 'BTB') BTB — Stage 2 (near-neutral range > 5.8)
                @else BSWM protocol
                @endif
            </small>
        </div>
        <div class="text-center">
            @if($sample->ph_color_hex)
            <div style="width:56px;height:36px;background:{{ $sample->ph_color_hex }};border:2px solid #999;
                        border-radius:6px;margin:0 auto 4px;" title="{{ $sample->ph_color_hex }}"></div>
            @endif
            <span class="fw-bold fs-2 text-primary">{{ number_format($phTest->final_ph, 2) }}</span>
            <span class="text-muted"> pH</span>
        </div>
    </div>
    @endif

    @endif {{-- /phTest exists --}}
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     N / P / K Parameters
══════════════════════════════════════════════════════════ --}}
@php
$npkParams = [
    'nitrogen'   => ['label' => 'Nitrogen (N)',   'unit' => 'ppm', 'icon' => 'fa-leaf',     'color' => 'success'],
    'phosphorus' => ['label' => 'Phosphorus (P)', 'unit' => 'ppm', 'icon' => 'fa-atom',     'color' => 'info'],
    'potassium'  => ['label' => 'Potassium (K)',  'unit' => 'ppm', 'icon' => 'fa-seedling', 'color' => 'warning'],
];
@endphp

@foreach($npkParams as $key => $meta)
@php
    $paramReadings = $readings[$key] ?? [];
    $capturedCount = count($paramReadings);
    $avgHex        = $sample->{$key . '_color_hex'};
    $finalValue    = $sample->{$key . '_level'};
@endphp
<div class="card mb-4">
    <div class="card-header bg-{{ $meta['color'] }} {{ $meta['color'] === 'warning' ? 'text-dark' : 'text-white' }}">
        <h6 class="mb-0">
            <i class="fas {{ $meta['icon'] }} me-2"></i>{{ $meta['label'] }}
            <span class="badge bg-white text-dark ms-2" style="font-size:.75rem;">{{ $capturedCount }}/3 tests</span>
        </h6>
    </div>
    <div class="card-body">
        @if($capturedCount === 0)
            <p class="text-muted mb-0"><em>No readings captured yet for this parameter.</em></p>
        @else
        <div class="row align-items-center">
            <div class="col-md-9">
                <table class="table table-bordered table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:80px;">Test #</th>
                            <th class="text-center" style="width:130px;">Captured Photo</th>
                            <th class="text-center" style="width:80px;">System Color</th>
                            <th class="text-center" style="width:100px;">Hex Value</th>
                            <th class="text-center">Computed Value</th>
                            <th class="text-center">Captured At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for($t = 1; $t <= 3; $t++)
                        @php $rd = $paramReadings[$t] ?? null; @endphp
                        <tr class="{{ $rd ? '' : 'table-light text-muted' }}">
                            <td class="fw-bold">Test {{ $t }}</td>

                            {{-- Actual photo from webcam --}}
                            <td class="text-center p-1">
                                @if($rd && $rd->captured_image)
                                    <img src="{{ asset($rd->captured_image) }}"
                                         width="120" height="90"
                                         style="border-radius:4px;border:1px solid #ccc;object-fit:cover;cursor:pointer;"
                                         title="Click to enlarge"
                                         onclick="document.getElementById('imgModal{{ $key }}{{ $t }}').style.display='flex'">
                                    <div id="imgModal{{ $key }}{{ $t }}"
                                         style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);
                                                z-index:9999;align-items:center;justify-content:center;"
                                         onclick="this.style.display='none'">
                                        <img src="{{ asset($rd->captured_image) }}"
                                             style="max-width:90vw;max-height:90vh;border-radius:8px;border:3px solid #fff;">
                                    </div>
                                @else
                                    <div style="width:120px;height:90px;background:#f8f9fa;border:1px dashed #ccc;
                                                border-radius:4px;display:flex;align-items:center;justify-content:center;margin:0 auto;">
                                        <small class="text-muted" style="font-size:10px;">No image</small>
                                    </div>
                                @endif
                            </td>

                            {{-- System-extracted color swatch --}}
                            <td class="text-center">
                                @if($rd)
                                    <div style="width:44px;height:44px;background:{{ $rd->color_hex }};
                                                border:2px solid #ccc;border-radius:6px;margin:0 auto 2px;" title="{{ $rd->color_hex }}"></div>
                                    <small class="text-muted" style="font-size:10px;">System</small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            <td class="text-center">
                                @if($rd)
                                    <code style="font-size:12px;">{{ $rd->color_hex }}</code>
                                @else <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($rd)
                                    <strong>{{ number_format($rd->computed_value, 3) }}</strong>
                                    <small class="text-muted">{{ $meta['unit'] }}</small>
                                @else
                                    <span class="text-muted">Not captured</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($rd && $rd->captured_at)
                                    <small>{{ \Carbon\Carbon::parse($rd->captured_at)->format('M j, Y g:i A') }}</small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                        @endfor
                    </tbody>
                </table>
            </div>

            {{-- Averaged result column --}}
            <div class="col-md-3 text-center">
                <p class="text-muted small fw-semibold mb-1">System Avg. Color</p>
                <p class="text-muted" style="font-size:10px;margin-top:-4px;">(mean of 3 extracted colors)</p>
                @if($avgHex)
                    <div style="width:72px;height:48px;background:{{ $avgHex }};border:2px solid #999;
                                border-radius:6px;margin:0 auto 6px;" title="{{ $avgHex }}"></div>
                    <code style="font-size:12px;">{{ $avgHex }}</code>
                @else
                    <div style="width:72px;height:48px;background:#eee;border:2px dashed #ccc;
                                border-radius:6px;margin:0 auto 6px;"></div>
                    <small class="text-muted">Not averaged yet</small>
                @endif

                @if(!is_null($finalValue))
                <hr class="my-2">
                <p class="text-muted small mb-0">Final Result</p>
                <div class="fw-bold fs-5 text-{{ $meta['color'] }}">
                    {{ number_format($finalValue, 2) }}
                    <small style="font-size:.75rem;">{{ $meta['unit'] }}</small>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
@endforeach

{{-- Raw RGB detail table (N/P/K — all readings) --}}
@php
$allReadings = collect();
foreach ($npkParams as $key => $meta) {
    foreach (($readings[$key] ?? []) as $t => $rd) {
        $allReadings->push(['param' => $meta['label'], 'test' => $t, 'rd' => $rd, 'color' => $meta['color']]);
    }
}
@endphp

@if($allReadings->isNotEmpty())
<div class="card mb-4">
    <div class="card-header bg-secondary text-white">
        <h6 class="mb-0"><i class="fas fa-table me-2"></i>N / P / K Readings — Raw RGB Data</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle table-sm mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Parameter</th>
                        <th class="text-center">Test #</th>
                        <th class="text-center">Swatch</th>
                        <th class="text-center">Hex</th>
                        <th class="text-center">R</th>
                        <th class="text-center">G</th>
                        <th class="text-center">B</th>
                        <th class="text-center">Computed Value</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allReadings as $row)
                    <tr>
                        <td><span class="badge bg-{{ $row['color'] }}">{{ $row['param'] }}</span></td>
                        <td class="text-center">{{ $row['test'] }}</td>
                        <td class="text-center">
                            <div style="width:36px;height:18px;background:{{ $row['rd']->color_hex }};
                                        border:1px solid #ccc;border-radius:3px;margin:0 auto;"></div>
                        </td>
                        <td class="text-center"><code style="font-size:11px;">{{ $row['rd']->color_hex }}</code></td>
                        <td class="text-center">{{ $row['rd']->r }}</td>
                        <td class="text-center">{{ $row['rd']->g }}</td>
                        <td class="text-center">{{ $row['rd']->b }}</td>
                        <td class="text-center fw-bold">{{ number_format($row['rd']->computed_value, 3) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<div class="row mb-4">
    <div class="col text-end">
        <a href="{{ route('samples.show', $sample) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Sample
        </a>
        @if($sample->isAnalyzed())
        <a href="{{ route('export', ['sample_id' => $sample->id]) }}" class="btn btn-success ms-2">
            <i class="fas fa-file-excel me-1"></i> Export to Excel
        </a>
        @endif
    </div>
</div>

@endsection
