@extends('layouts.app')
@section('title', 'Import Farmers')
@section('content')

<div class="row mb-3">
    <div class="col">
        <a href="{{ route('farmers.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Farmers
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-file-import me-2"></i>Import Farmers from CSV</h5>
            </div>
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('farmers.import.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-4">
                        <label class="form-label fw-semibold">CSV File *</label>
                        <input type="file" name="csv_file" class="form-control @error('csv_file') is-invalid @enderror"
                               accept=".csv,.txt" required>
                        <div class="form-text">Maximum file size: 2 MB. Accepted: .csv or .txt</div>
                        @error('csv_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="alert alert-info mb-4">
                        <h6 class="alert-heading mb-2"><i class="fas fa-info-circle me-1"></i>Required CSV Format</h6>
                        <p class="mb-2 small">The first row must be the header row with exactly these column names:</p>
                        <code class="d-block p-2 bg-white rounded border small">name,address,farm_location,farm_id</code>
                        <ul class="mt-2 mb-0 small">
                            <li><strong>name</strong> — Full name of the farmer (required)</li>
                            <li><strong>address</strong> — Complete address</li>
                            <li><strong>farm_location</strong> — Barangay or field location (optional, leave blank)</li>
                            <li><strong>farm_id</strong> — Arduino/Phase 2 device ID (optional, leave blank)</li>
                        </ul>
                    </div>

                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-upload me-1"></i> Upload & Import
                    </button>
                    <a href="{{ route('farmers.index') }}" class="btn btn-outline-secondary ms-2">Cancel</a>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-download me-1"></i>Download Template</h6></div>
            <div class="card-body">
                <p class="small text-muted">Download a sample CSV template to fill in your farmer data.</p>
                <a href="{{ route('farmers.import') }}?download=template" class="btn btn-outline-success btn-sm">
                    <i class="fas fa-file-csv me-1"></i> Download Template CSV
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h6 class="mb-0">Sample CSV Preview</h6></div>
            <div class="card-body p-0">
                <table class="table table-bordered table-sm mb-0" style="font-size:12px;">
                    <thead class="table-success">
                        <tr>
                            <th>name</th>
                            <th>address</th>
                            <th>farm_location</th>
                            <th>farm_id</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Juan Dela Cruz</td>
                            <td>Brgy. Labasan, San Teodoro</td>
                            <td>Field Block A</td>
                            <td>ARD-001</td>
                        </tr>
                        <tr>
                            <td>Maria Santos</td>
                            <td>Poblacion I, San Teodoro</td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
// Handle template download via GET param
@if(request('download') === 'template')
(function() {
    const rows = [
        ['name','address','farm_location','farm_id'],
        ['Juan Dela Cruz','Brgy. Labasan, San Teodoro','Field Block A','ARD-001'],
        ['Maria Santos','Poblacion I, San Teodoro','',''],
    ];
    const csv = rows.map(r => r.map(v => '"'+v+'"').join(',')).join('\n');
    const blob = new Blob(['\xEF\xBB\xBF' + csv], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href = url; a.download = 'farmers_template.csv'; a.click();
    URL.revokeObjectURL(url);
})();
@endif
</script>
@endsection
