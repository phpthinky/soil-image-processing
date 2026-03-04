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

@php
$params = [
    'ph'         => ['label' => 'Soil pH',       'unit' => '',    'icon' => 'fa-flask',    'color' => 'primary'],
    'nitrogen'   => ['label' => 'Nitrogen (N)',   'unit' => 'ppm', 'icon' => 'fa-leaf',     'color' => 'success'],
    'phosphorus' => ['label' => 'Phosphorus (P)', 'unit' => 'ppm', 'icon' => 'fa-atom',     'color' => 'info'],
    'potassium'  => ['label' => 'Potassium (K)',  'unit' => 'ppm', 'icon' => 'fa-seedling', 'color' => 'warning'],
];
@endphp

{{-- Per-parameter breakdown --}}
@foreach($params as $key => $meta)
@php
    $paramReadings = $readings[$key] ?? [];
    $capturedCount = count($paramReadings);
    // Averaged hex stored on sample
    $avgHex = $sample->{$key . '_color_hex'};
    $finalValue = $sample->{$key . '_level'};
@endphp
<div class="card mb-4">
    <div class="card-header bg-{{ $meta['color'] }} {{ in_array($meta['color'], ['warning']) ? 'text-dark' : 'text-white' }}">
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
                            <th style="width:100px;">Test #</th>
                            <th class="text-center" style="width:100px;">Color</th>
                            <th class="text-center" style="width:110px;">Hex Value</th>
                            <th class="text-center">Computed Value</th>
                            <th class="text-center">Captured At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for($t = 1; $t <= 3; $t++)
                        @php $rd = $paramReadings[$t] ?? null; @endphp
                        <tr class="{{ $rd ? '' : 'table-light text-muted' }}">
                            <td class="fw-bold">Test {{ $t }}</td>
                            <td class="text-center">
                                @if($rd)
                                    <div style="width:44px;height:24px;background:{{ $rd->color_hex }};
                                                border:1px solid #ccc;border-radius:4px;margin:0 auto;"></div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($rd)
                                    <code style="font-size:12px;">{{ $rd->color_hex }}</code>
                                @else
                                    <span class="text-muted">—</span>
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
                                @elseif($rd)
                                    <small class="text-muted">—</small>
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
                <p class="text-muted small mb-1">Averaged Color</p>
                @if($avgHex)
                    <div style="width:64px;height:36px;background:{{ $avgHex }};border:2px solid #999;
                                border-radius:6px;margin:0 auto 6px;"></div>
                    <code style="font-size:12px;">{{ $avgHex }}</code>
                @else
                    <div style="width:64px;height:36px;background:#eee;border:2px dashed #ccc;
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

{{-- Raw RGB detail table (all readings) --}}
@php
$allReadings = collect();
foreach ($params as $key => $meta) {
    foreach (($readings[$key] ?? []) as $t => $rd) {
        $allReadings->push(['param' => $meta['label'], 'test' => $t, 'rd' => $rd, 'color' => $meta['color']]);
    }
}
@endphp

@if($allReadings->isNotEmpty())
<div class="card mb-4">
    <div class="card-header bg-secondary text-white">
        <h6 class="mb-0"><i class="fas fa-table me-2"></i>All Readings — Raw RGB Data</h6>
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
