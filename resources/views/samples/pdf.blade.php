<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soil Test Report — {{ $sample->sample_name }}</title>
    <style>
        /* ── Base ────────────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
            color: #111;
            background: #fff;
            padding: 20px;
        }
        h1 { font-size: 16pt; margin-bottom: 4px; }
        h2 { font-size: 13pt; margin: 16px 0 6px; border-bottom: 2px solid #2e7d32; padding-bottom: 4px; color: #2e7d32; }
        h3 { font-size: 11pt; margin: 12px 0 4px; color: #1565c0; }
        p  { margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 10pt; }
        th, td { border: 1px solid #bbb; padding: 4px 6px; text-align: left; }
        th { background: #e8f5e9; font-weight: bold; }
        .badge {
            display: inline-block; padding: 1px 7px; border-radius: 10px;
            font-size: 9pt; font-weight: bold; color: #fff;
        }
        .badge-success  { background: #388e3c; }
        .badge-warning  { background: #f9a825; color: #111; }
        .badge-info     { background: #0288d1; }
        .badge-danger   { background: #c62828; }
        .badge-secondary{ background: #757575; }
        .badge-primary  { background: #1565c0; }
        .swatch {
            display: inline-block; width: 18px; height: 14px;
            border: 1px solid #999; vertical-align: middle; margin-right: 4px;
            border-radius: 2px;
        }
        .info-grid { display: flex; flex-wrap: wrap; gap: 6px 24px; margin-bottom: 10px; }
        .info-grid span { font-size: 10.5pt; }
        .info-grid strong { color: #333; }
        .section { margin-bottom: 18px; }
        .note-box { background: #fff9c4; border: 1px solid #f9a825; padding: 6px 10px; border-radius: 4px; font-size: 10pt; margin-bottom: 8px; }
        .result-row { display: flex; gap: 20px; margin-bottom: 10px; flex-wrap: wrap; }
        .result-card {
            border: 1px solid #bbb; border-radius: 6px;
            padding: 8px 14px; min-width: 120px; text-align: center;
        }
        .result-card .value { font-size: 18pt; font-weight: bold; }
        .result-card .label { font-size: 9pt; color: #555; }
        .stage-header { background: #e3f2fd; border-left: 4px solid #1565c0; padding: 4px 8px; margin-bottom: 6px; font-weight: bold; }
        .stage2-header { background: #e8f5e9; border-left: 4px solid #2e7d32; padding: 4px 8px; margin-bottom: 6px; font-weight: bold; }
        .final-box {
            border: 2px solid #1565c0; border-radius: 6px;
            padding: 10px 16px; display: flex; justify-content: space-between;
            align-items: center; background: #e3f2fd; margin: 10px 0;
        }
        .final-box .ph-value { font-size: 22pt; font-weight: bold; color: #1565c0; }
        .print-btn {
            display: inline-block; padding: 8px 20px; background: #1565c0; color: #fff;
            border: none; border-radius: 4px; cursor: pointer; font-size: 11pt;
            text-decoration: none; margin-bottom: 16px;
        }
        .print-btn:hover { background: #0d47a1; }
        .no-print { }
        /* ── Print overrides ──────────────────────────────────────── */
        @media print {
            body { padding: 0; font-size: 10pt; }
            .no-print { display: none !important; }
            h2 { font-size: 12pt; }
            table { font-size: 9pt; }
            @page { size: A4; margin: 15mm 12mm; }
        }
    </style>
</head>
<body>

{{-- Print button (hidden when printing) --}}
<div class="no-print" style="margin-bottom:12px;">
    <button class="print-btn" onclick="window.print()">
        &#128438; Print / Save as PDF
    </button>
    <a href="{{ route('samples.show', $sample) }}"
       style="margin-left:12px;font-size:11pt;color:#555;">&larr; Back to Sample</a>
</div>

{{-- ── Header ─────────────────────────────────────────────────────── --}}
<div style="border-bottom:3px solid #2e7d32;padding-bottom:10px;margin-bottom:14px;">
    <h1>&#127807; Soil Test Report</h1>
    <div style="font-size:13pt;color:#2e7d32;font-weight:bold;">{{ $sample->sample_name }}</div>
</div>

<div class="info-grid">
    <span><strong>Farmer:</strong> {{ $sample->farmer_name }}</span>
    <span><strong>Address:</strong> {{ $sample->address }}</span>
    @if($sample->location)<span><strong>Farm Location:</strong> {{ $sample->location }}</span>@endif
    <span><strong>Date Received:</strong> {{ $sample->sample_date->format('F j, Y') }}</span>
    <span><strong>Date Tested:</strong> {{ $sample->date_tested->format('F j, Y') }}</span>
    @if($sample->analyzed_at)<span><strong>Analyzed:</strong> {{ $sample->analyzed_at->format('F j, Y g:i A') }}</span>@endif
    <span><strong>Tests Captured:</strong> {{ $sample->tests_completed ?? 0 }}/12</span>
    @if(!is_null($sample->fertility_score))
    <span><strong>Fertility Score:</strong>
        <span class="badge badge-{{ $sample->fertilityColorClass() }}">{{ $sample->fertility_score }}%</span>
    </span>
    @endif
</div>

{{-- ── Soil Parameters Summary ─────────────────────────────────────── --}}
@if($sample->isAnalyzed())
<h2>&#128200; Soil Analysis Results</h2>
<div class="result-row">
    <div class="result-card">
        <div class="label">Soil pH</div>
        <div class="value" style="color:#1565c0;">{{ number_format($sample->ph_level,2) }}</div>
        @if($sample->ph_color_hex)
        <div><span class="swatch" style="background:{{ $sample->ph_color_hex }};"></span>
             <small>{{ $sample->ph_color_hex }}</small></div>
        @endif
    </div>
    <div class="result-card">
        <div class="label">Nitrogen (N)</div>
        <div class="value" style="color:#2e7d32;">{{ number_format($sample->nitrogen_level,1) }}</div>
        <div class="label">ppm</div>
    </div>
    <div class="result-card">
        <div class="label">Phosphorus (P)</div>
        <div class="value" style="color:#0288d1;">{{ number_format($sample->phosphorus_level,1) }}</div>
        <div class="label">ppm</div>
    </div>
    <div class="result-card">
        <div class="label">Potassium (K)</div>
        <div class="value" style="color:#f57f17;">{{ number_format($sample->potassium_level,1) }}</div>
        <div class="label">ppm</div>
    </div>
    @if(!is_null($sample->fertility_score))
    <div class="result-card">
        <div class="label">Fertility Score</div>
        <div class="value" style="color:#{{ $sample->fertility_score>=75?'388e3c':($sample->fertility_score>=50?'f9a825':'c62828') }};">
            {{ $sample->fertility_score }}%
        </div>
    </div>
    @endif
</div>
@endif

{{-- ── pH Test Details ─────────────────────────────────────────────── --}}
@if($phTest)
<h2>&#128139; pH Test — BSWM Two-Step Protocol</h2>

{{-- Stage 1 --}}
<div class="stage-header">Stage 1 — CPR Solution (Cresol Red Purple)</div>
<table>
    <thead>
        <tr><th>Capture</th><th>Hex Color</th><th>System Color</th><th>Computed pH</th></tr>
    </thead>
    <tbody>
        @foreach(range(1,3) as $i)
        @php $rd = $phTest->step1_readings[$i-1] ?? null; @endphp
        <tr>
            <td>Capture {{ $i }}</td>
            <td>{{ $rd ? $rd['hex'] : '—' }}</td>
            <td>
                @if($rd)
                    <span class="swatch" style="background:{{ $rd['hex'] }};"></span>{{ $rd['hex'] }}
                @else —
                @endif
            </td>
            <td>{{ $rd ? number_format($rd['computed_value'],2) : '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@if($phTest->step1_ph)
<p style="margin-bottom:8px;">
    CPR Average pH: <strong>{{ number_format($phTest->step1_ph,2) }}</strong> &nbsp;
    Confidence: <strong>{{ $phTest->step1_confidence }}</strong> &nbsp;
    Decision: <strong>
        @switch($phTest->next_solution)
            @case('BCG') Proceed to BCG @break
            @case('BTB') Proceed to BTB @break
            @case('CPR') CPR Result is Final @break
            @case('RETEST') Retest Required @break
            @default Pending
        @endswitch
    </strong>
</p>
@endif

{{-- Stage 2 --}}
@if($phTest->step2_readings && count($phTest->step2_readings))
<div class="stage2-header">
    Stage 2 — {{ $phTest->step2_solution }}
    @if($phTest->step2_solution === 'BCG') (Bromocresol Green — acidic range)
    @elseif($phTest->step2_solution === 'BTB') (Bromothymol Blue — near-neutral range)
    @endif
</div>
<table>
    <thead>
        <tr><th>Capture</th><th>Hex Color</th><th>System Color</th><th>Computed pH</th></tr>
    </thead>
    <tbody>
        @foreach(range(1,3) as $i)
        @php $rd = $phTest->step2_readings[$i-1] ?? null; @endphp
        <tr>
            <td>Capture {{ $i }}</td>
            <td>{{ $rd ? $rd['hex'] : '—' }}</td>
            <td>
                @if($rd)
                    <span class="swatch" style="background:{{ $rd['hex'] }};"></span>{{ $rd['hex'] }}
                @else —
                @endif
            </td>
            <td>{{ $rd ? number_format($rd['computed_value'],2) : '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@if($phTest->step2_ph)
<p style="margin-bottom:8px;">
    {{ $phTest->step2_solution }} Average pH: <strong>{{ number_format($phTest->step2_ph,2) }}</strong> &nbsp;
    Confidence: <strong>{{ $phTest->step2_confidence }}</strong>
</p>
@endif
@endif

{{-- Final pH --}}
@if($phTest->final_ph)
<div class="final-box">
    <div>
        <div style="font-weight:bold;font-size:12pt;">Final pH Result</div>
        <div style="font-size:10pt;color:#555;">
            Based on
            @if($phTest->next_solution==='CPR') CPR (transitional range 5.4–5.8)
            @elseif($phTest->step2_solution==='BCG') BCG — Stage 2 (acidic range ≤ 5.4)
            @elseif($phTest->step2_solution==='BTB') BTB — Stage 2 (near-neutral range > 5.8)
            @else BSWM protocol
            @endif
        </div>
    </div>
    <div style="text-align:center;">
        @if($sample->ph_color_hex)
        <span class="swatch" style="background:{{ $sample->ph_color_hex }};width:30px;height:20px;"></span>
        @endif
        <span class="ph-value">{{ number_format($phTest->final_ph,2) }}</span>
        <span style="font-size:12pt;color:#555;"> pH</span>
    </div>
</div>
@endif
@endif

{{-- ── Fertilizer Recommendations ──────────────────────────────────── --}}
@if(!empty($fertRec))
<h2>&#127807; Fertilizer Recommendations</h2>
<table>
    <thead>
        <tr><th>Input</th><th>Recommended Rate</th></tr>
    </thead>
    <tbody>
        @if($fertRec['lime_tons'] > 0)
        <tr><td>Dolomitic Lime</td><td>{{ $fertRec['lime_tons'] }} t/ha</td></tr>
        @endif
        <tr><td>Urea (46-0-0)</td><td>{{ $fertRec['urea_bags'] }} bags/ha (50 kg each)</td></tr>
        <tr><td>TSP (0-46-0)</td><td>{{ $fertRec['tsp_bags'] }} bags/ha (50 kg each)</td></tr>
        <tr><td>MOP (0-0-60)</td><td>{{ $fertRec['mop_bags'] }} bags/ha (50 kg each)</td></tr>
    </tbody>
</table>
@if(!empty($fertRec['notes']))
<div class="note-box">
    @foreach($fertRec['notes'] as $note)
    <div>&#8226; {{ $note }}</div>
    @endforeach
</div>
@endif
@endif

{{-- ── Crop Recommendations ─────────────────────────────────────────── --}}
<h2>&#127807; Crop Recommendations</h2>

{{-- Group 1: Tolerance Match --}}
<h3>Group 1 — Tolerance Match (pH + NPK compatible)</h3>
<p style="font-size:9.5pt;color:#555;margin-bottom:6px;">
    Crops where soil pH is within range, ranked by overall score. Can be planted with current soil.
</p>
@if(count($cropsByTolerance))
<table>
    <thead><tr><th>#</th><th>Crop</th><th>pH Range</th><th>N (ppm)</th><th>P (ppm)</th><th>K (ppm)</th><th>Score</th></tr></thead>
    <tbody>
        @foreach($cropsByTolerance as $i => $crop)
        @php $s=$crop->match_score; $mc=$s==4?'success':($s>=3?'warning':($s>=2?'info':'secondary')); @endphp
        <tr>
            <td>{{ $i+1 }}</td>
            <td><strong>{{ $crop->name }}</strong>@if($i===0) &#9733;@endif</td>
            <td>{{ $crop->min_ph }}–{{ $crop->max_ph }}</td>
            <td>{{ $crop->min_nitrogen }}–{{ $crop->max_nitrogen }}</td>
            <td>{{ $crop->min_phosphorus }}–{{ $crop->max_phosphorus }}</td>
            <td>{{ $crop->min_potassium }}–{{ $crop->max_potassium }}</td>
            <td><span class="badge badge-{{ $mc }}">{{ $s }}/4</span></td>
        </tr>
        @endforeach
    </tbody>
</table>
@else
<p style="color:#b71c1c;">No crops match the current soil pH.</p>
@endif

{{-- Group 2: Fertility Score --}}
<h3>Group 2 — Fertility Score (NPK-based, no pH filter)</h3>
<p style="font-size:9.5pt;color:#555;margin-bottom:6px;">
    Ranked by NPK compatibility. May need pH amendment. A lime or sulfur application can correct the pH.
</p>
@if(count($cropsByFertility))
<table>
    <thead><tr><th>#</th><th>Crop</th><th>pH Range</th><th>N (ppm)</th><th>P (ppm)</th><th>K (ppm)</th><th>NPK Score</th><th>pH Match?</th></tr></thead>
    <tbody>
        @foreach($cropsByFertility as $i => $crop)
        @php
            $ns=$crop->npk_score; $nc=$ns==3?'success':($ns>=2?'warning':($ns>=1?'info':'secondary'));
            $phOk = $sample->ph_level>=$crop->min_ph && $sample->ph_level<=$crop->max_ph;
        @endphp
        <tr>
            <td>{{ $i+1 }}</td>
            <td><strong>{{ $crop->name }}</strong>@if($i===0) &#9733;@endif</td>
            <td>{{ $crop->min_ph }}–{{ $crop->max_ph }}</td>
            <td>{{ $crop->min_nitrogen }}–{{ $crop->max_nitrogen }}</td>
            <td>{{ $crop->min_phosphorus }}–{{ $crop->max_phosphorus }}</td>
            <td>{{ $crop->min_potassium }}–{{ $crop->max_potassium }}</td>
            <td><span class="badge badge-{{ $nc }}">{{ $ns }}/3</span></td>
            <td>
                @if($phOk)
                    <span class="badge badge-success">Yes</span>
                @else
                    <span class="badge badge-danger">Needs fix</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@else
<p style="color:#b71c1c;">No NPK data available.</p>
@endif

{{-- Group 3: pH Threshold --}}
<h3>Group 3 — pH Threshold (All pH-compatible crops)</h3>
<p style="font-size:9.5pt;color:#555;margin-bottom:6px;">
    Every species whose pH tolerance covers {{ $sample->ph_level ? 'pH '.number_format($sample->ph_level,1) : 'current soil pH' }}.
    Ranked by NPK score. Nutrient amendment may still be needed.
</p>
@if(count($cropsByPh))
<table>
    <thead><tr><th>#</th><th>Crop</th><th>pH Range</th><th>N (ppm)</th><th>P (ppm)</th><th>K (ppm)</th><th>NPK Score</th></tr></thead>
    <tbody>
        @foreach($cropsByPh as $i => $crop)
        @php $ns=$crop->npk_score; $nc=$ns==3?'success':($ns>=2?'warning':($ns>=1?'info':'secondary')); @endphp
        <tr>
            <td>{{ $i+1 }}</td>
            <td><strong>{{ $crop->name }}</strong>@if($i===0) &#9733;@endif</td>
            <td>{{ $crop->min_ph }}–{{ $crop->max_ph }}</td>
            <td>{{ $crop->min_nitrogen }}–{{ $crop->max_nitrogen }}</td>
            <td>{{ $crop->min_phosphorus }}–{{ $crop->max_phosphorus }}</td>
            <td>{{ $crop->min_potassium }}–{{ $crop->max_potassium }}</td>
            <td><span class="badge badge-{{ $nc }}">{{ $ns }}/3</span></td>
        </tr>
        @endforeach
    </tbody>
</table>
@else
<p style="color:#b71c1c;">No crops tolerate this pH level.</p>
@endif

{{-- Footer --}}
<div style="margin-top:24px;border-top:1px solid #ccc;padding-top:8px;font-size:9pt;color:#777;">
    Soil Analysis System &bull; BSWM Protocol &bull;
    Generated: {{ now()->format('F j, Y g:i A') }}
</div>

</body>
</html>
