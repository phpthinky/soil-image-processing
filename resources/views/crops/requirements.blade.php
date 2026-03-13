@extends('layouts.app')

@section('title', 'Crop pH & NPK Requirements')

@push('styles')
<style>
    .req-table th { white-space: nowrap; }
    .req-table td { vertical-align: middle; }
    .range-badge {
        font-size: .78rem;
        font-family: monospace;
        background: #f0f4f0;
        border: 1px solid #c8dfc8;
        border-radius: 4px;
        padding: 1px 6px;
        white-space: nowrap;
    }
    .ph-badge   { background: #fff3cd; border-color: #ffc107; }
    .n-badge    { background: #d1ecf1; border-color: #17a2b8; }
    .p-badge    { background: #d4edda; border-color: #28a745; }
    .k-badge    { background: #fde8d8; border-color: #fd7e14; }

    @media print {
        .no-print { display: none !important; }
        .sidebar, nav.navbar { display: none !important; }
        .app-body { display: block !important; }
        .main-content { margin: 0 !important; padding: 0 !important; }
        body { background: #fff !important; }
        .card { border: none !important; box-shadow: none !important; }
        .card-header { background: #fff !important; color: #000 !important; border-bottom: 2px solid #000 !important; }
        table { font-size: 9pt !important; }
        .range-badge { border: 1px solid #999 !important; background: #fff !important; }
        h1, h2, .print-title { color: #000 !important; }
        .print-header { display: block !important; }
    }
    .print-header { display: none; }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">

    {{-- Print-only header --}}
    <div class="print-header mb-3">
        <h4 class="mb-0">Office of the Municipal Agriculturist</h4>
        <h5>Crop pH &amp; NPK Requirements Reference</h5>
        <small>Printed: {{ now()->format('F j, Y g:i A') }}</small>
        <hr>
    </div>

    <div class="card shadow-sm">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2"
             style="background: linear-gradient(135deg,#2e7d32,#388e3c); color:#fff;">
            <div>
                <i class="fas fa-seedling me-2"></i>
                <strong>Crop pH &amp; NPK Requirements</strong>
                <span class="badge bg-light text-dark ms-2">{{ $crops->count() }} crops</span>
                <div class="mt-1" style="font-size:.78rem; font-weight:normal; opacity:.92;">
                    <i class="fas fa-book-open me-1"></i>
                    Based on guidelines from:
                    <a href="https://www.bswm.da.gov.ph/download/bswm-fertilizer-recommendation/" target="_blank" rel="noopener"
                       class="text-warning text-decoration-underline ms-1">
                        BSWM Fertilizer Recommendation Guide
                    </a>
                    &nbsp;&amp;&nbsp;
                    <a href="https://www.philrice.gov.ph/wp-content/uploads/2023/10/RS4DM_Balanced-fertilization.pdf" target="_blank" rel="noopener"
                       class="text-warning text-decoration-underline">
                        PhilRice Balanced Fertilization (2023)
                    </a>
                    &nbsp;&amp;&nbsp;
                    <a href="https://www.philrice.gov.ph/balanced-fertilization-cuts-fertilizer-costs-by-p2-4k-experts/" target="_blank" rel="noopener"
                       class="text-warning text-decoration-underline">
                        PhilRice Nutrient Management
                    </a>
                </div>
            </div>
            <div class="d-flex gap-2 no-print">
                <a href="{{ route('crops.requirements.export') }}"
                   class="btn btn-sm btn-warning fw-semibold">
                    <i class="fas fa-file-csv me-1"></i> Export to Excel / CSV
                </a>
                <button onclick="window.print()" class="btn btn-sm btn-light fw-semibold">
                    <i class="fas fa-print me-1"></i> Print
                </button>
            </div>
        </div>

        <div class="card-body p-0">
            @if($crops->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-seedling fa-3x mb-3 d-block" style="opacity:.3"></i>
                    No crops found in the system.
                </div>
            @else
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm mb-0 req-table">
                    <thead class="table-dark">
                        <tr class="align-items-top text-align-top">
                            <th class="text-center" style="width:3rem">#</th>
                            <th rowspan="2">Crop Name</th>
                            <th colspan="3" class="text-center">pH</th> 

                            <th colspan="3" class="text-center">N<br><small class="fw-normal opacity-75">(ppm)</small></th>
                            <th colspan="3" class="text-center">P<br><small class="fw-normal opacity-75">(ppm)</small></th>
                            <th colspan="3" class="text-center">K<br><small class="fw-normal opacity-75">(ppm)</small></th>
                        </tr>
                        <tr>
                            <th></th>
                            <th class="bg-warning">Low</th>
                            <th class=" bg-success">Neutral</th>
                            <th class=" bg-info">High</th>

                            <th class="bg-warning">Low</th>
                            <th class=" bg-success">Neutral</th>
                            <th class=" bg-info">High</th>

                            <th class="bg-warning">Low</th>
                            <th class=" bg-success">Neutral</th>
                            <th class=" bg-info">High</th>

                            <th class="bg-warning">Low</th>
                            <th class=" bg-success">Optimal</th>
                            <th class=" bg-info">High</th>

                        </tr>
                    </thead>
                    <tbody>
                        @foreach($crops as $i => $crop)
                        <tr style="font-size: 0.8em">
                            <td class="text-center text-muted">{{ $i + 1 }}</td>
                            <td class="">{{ $crop->name }}</td>
                            <td class="text-center bg-warning">
                                <span class="range-badge ph-badge">
                                     {{ number_format($crop->min_ph, 1) }}
                                </span>
                            </td>
                             <td class="text-center bg-success">
                                <span class="range-badge ph-badge">
                                    {{ number_format($crop->min_ph, 1) }} - {{  number_format($crop->max_ph, 1) }}
                                </span>
                            </td> <td class="text-center bg-info">
                                <span class="range-badge ph-badge">
                                    {{  number_format($crop->max_ph, 1) }}
                                </span>
                            </td>



                            <td class="text-center bg-warning">
                                <span class="range-badge n-badge">
                                    {{ number_format($crop->min_nitrogen, 0) }} 
                                </span>
                            </td>
                            <td class="text-center bg-success">
                                <span class="range-badge n-badge">
                                    {{ number_format($crop->min_nitrogen , 0) }} -  {{ number_format($crop->min_nitrogen , 0) }}
                                </span>
                            </td>
                            <td class="text-center bg-info">
                                <span class="range-badge n-badge">
                                    {{ number_format($crop->max_nitrogen, 0) }}
                                </span>
                            </td>



                            <td class="text-center bg-warning   ">
                                <span class="range-badge p-badge">
                                    {{ number_format($crop->min_phosphorus, 0) }} 
                                </span>
                            </td>
                            

                            <td class="text-center bg-success">
                                <span class="range-badge p-badge">
                                    {{ number_format($crop->min_phosphorus, 0) }} -  {{ number_format($crop->max_phosphorus, 0) }}
                                </span>
                            </td>
                            

                            <td class="text-center bg-info">
                                <span class="range-badge p-badge">
                                   {{ number_format($crop->max_phosphorus, 0) }}
                                </span>
                            </td>
                            
                            <td class="text-center bg-warning   ">
                                <span class="range-badge k-badge">
                                  {{ number_format($crop->min_potassium, 0) }} 
                                </span>
                            </td>
                            <td class="text-center bg-success   ">
                                <span class="range-badge k-badge">
                                    {{ number_format($crop->min_potassium, 0) }} - {{ number_format($crop->max_potassium, 0) }}
                                </span>
                            </td>
                            <td class="text-center bg-info  ">
                                <span class="range-badge k-badge">
                                  {{ number_format($crop->max_potassium, 0) }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Legend --}}
            <div class="px-3 py-2 border-top bg-light no-print">
                <small class="text-muted">
                    <strong>How to use:</strong>
                    Compare your soil test results to the Min–Max ranges above.
                    A crop is suitable when <strong>all four values</strong> (pH, N, P, K) fall within its ranges.
                    &nbsp;|&nbsp;
                    <span class="range-badge ph-badge">pH</span>
                    <span class="range-badge n-badge">Nitrogen</span>
                    <span class="range-badge p-badge">Phosphorus</span>
                    <span class="range-badge k-badge">Potassium</span>
                    &nbsp;values are in ppm (mg/kg) except pH.
                </small>
                <div class="mt-2 p-2 rounded border" style="background:#fff8e1;">
                    <small>
                        <i class="fas fa-external-link-alt me-1 text-warning"></i>
                        <strong>References used for this data:</strong>
                        <ul class="mb-0 mt-1 ps-3" style="line-height:1.8;">
                            <li>
                                <a href="https://www.bswm.da.gov.ph/download/bswm-fertilizer-recommendation/" target="_blank" rel="noopener">
                                    BSWM Fertilizer Recommendation Guide
                                </a>
                                — DA Bureau of Soils and Water Management
                            </li>
                            <li>
                                <a href="https://www.philrice.gov.ph/wp-content/uploads/2023/10/RS4DM_Balanced-fertilization.pdf" target="_blank" rel="noopener">
                                    PhilRice: Balanced Fertilization (RS4DM, 2023 PDF)
                                </a>
                                — Philippine Rice Research Institute
                            </li>
                            <li>
                                <a href="https://www.philrice.gov.ph/balanced-fertilization-cuts-fertilizer-costs-by-p2-4k-experts/" target="_blank" rel="noopener">
                                    PhilRice: Balanced Fertilization Cuts Fertilizer Costs
                                </a>
                                — Nutrient management article, PhilRice
                            </li>
                            <li>
                                <a href="https://www.bswm.da.gov.ph/program/sha-nsst/" target="_blank" rel="noopener">
                                    BSWM: National Soil Sampling &amp; Testing for Rice and Corn
                                </a>
                                — Soil Health Assessment Program
                            </li>
                        </ul>
                        <span class="text-muted d-block mt-1">
                            <i class="fas fa-info-circle me-1"></i>
                            pH and NPK ranges are consistent with DA-BSWM and PhilRice soil fertility guidelines.
                            For crop-specific soil analysis, have your soil tested at the nearest
                            <a href="https://www.bswm.da.gov.ph" target="_blank" rel="noopener">BSWM</a> or DA regional laboratory.
                        </span>
                    </small>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection