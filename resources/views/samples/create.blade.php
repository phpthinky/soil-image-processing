@extends('layouts.app')
@section('title', 'Add Soil Sample')
@section('content')

<div class="row">
    <div class="col-md-12">
        <h2>Add Soil Sample</h2>
        <p class="lead">Add a new soil sample for webcam-based soil nutrient analysis.</p>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row mt-4">
    <div class="col-md-8">
        <form method="POST" action="{{ route('samples.store') }}" id="sampleForm">
            @csrf

            {{-- Farmer selector --}}
            <div class="card mb-3">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="fas fa-user-tie me-2"></i>Select Farmer</h6>
                </div>
                <div class="card-body">
                    @if($farmers->isEmpty())
                        <div class="alert alert-warning mb-3">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            No farmers registered yet.
                            <a href="{{ route('farmers.create') }}" class="alert-link">Add a farmer</a> or
                            <a href="{{ route('farmers.import') }}" class="alert-link">import from CSV</a> first,
                            or fill in the details manually below.
                        </div>
                        <input type="hidden" name="farmer_id" value="">
                    @else
                        <div class="mb-0">
                            <label class="form-label fw-semibold">Registered Farmer</label>
                            <select name="farmer_id" id="farmerSelect" class="form-select">
                                <option value="">— Enter details manually —</option>
                                @foreach($farmers as $f)
                                <option value="{{ $f->id }}"
                                        data-name="{{ $f->name }}"
                                        data-address="{{ $f->address }}"
                                        data-location="{{ $f->farm_location ?? '' }}"
                                        {{ old('farmer_id') == $f->id ? 'selected' : '' }}>
                                    {{ $f->name }} — {{ $f->address }}
                                    @if($f->farm_id) [{{ $f->farm_id }}]@endif
                                </option>
                                @endforeach
                            </select>
                            <div class="form-text">Selecting a farmer auto-fills the fields below. You can still edit them.</div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Sample information --}}
            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-vial me-2"></i>Soil Sample Information</h6></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Name of Farmer *</label>
                                <input type="text" class="form-control @error('farmer_name') is-invalid @enderror"
                                       name="farmer_name" id="farmerName"
                                       value="{{ old('farmer_name') }}"
                                       placeholder="Enter Farmer's Name" required>
                                @error('farmer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Address *</label>
                                <input type="text" class="form-control @error('address') is-invalid @enderror"
                                       name="address" id="farmerAddress"
                                       value="{{ old('address') }}"
                                       placeholder="e.g., Poblacion I, San Teodoro" required>
                                @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Farm Location / Barangay</label>
                        <input type="text" class="form-control" name="location" id="farmerLocation"
                               value="{{ old('location') }}"
                               placeholder="e.g., Field A, Brgy. Labasan">
                        <div class="form-text">Optional — barangay or specific field location</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Sample Name *</label>
                        <input type="text" class="form-control @error('sample_name') is-invalid @enderror"
                               name="sample_name" value="{{ old('sample_name') }}" required
                               placeholder="e.g., Field A — March 2026 Sample">
                        <div class="form-text">Give this sample a descriptive name</div>
                        @error('sample_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Date Received *</label>
                                <input type="date" class="form-control @error('sample_date') is-invalid @enderror"
                                       name="sample_date" id="sample_date"
                                       value="{{ old('sample_date', date('Y-m-d')) }}"
                                       max="{{ date('Y-m-d') }}" required>
                                <div class="form-text">Cannot be a future date.</div>
                                @error('sample_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Date Tested *</label>
                                <input type="date" class="form-control @error('date_tested') is-invalid @enderror"
                                       name="date_tested" id="date_tested"
                                       value="{{ old('date_tested', date('Y-m-d')) }}"
                                       min="{{ old('sample_date', date('Y-m-d')) }}"
                                       max="{{ date('Y-m-d') }}" required>
                                <div class="form-text">Must be on or after received date.</div>
                                @error('date_tested')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info py-2">
                        <i class="fas fa-info-circle me-1"></i>
                        After saving, you will be taken to the webcam capture screen to analyze this sample's pH, N, P, and K levels.
                    </div>

                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-plus-circle me-1"></i> Add Soil Sample
                    </button>
                </div>
            </div>

        </form>
    </div>

    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Sample Collection Guide</h6></div>
            <div class="card-body small">
                <p><strong>Proper soil sampling technique:</strong></p>
                <ol>
                    <li>Collect samples from multiple locations in the field</li>
                    <li>Take samples at 6–8 inches depth</li>
                    <li>Mix samples thoroughly in a clean container</li>
                    <li>Allow soil to air dry before analysis</li>
                    <li>Label samples clearly with location and date</li>
                </ol>
            </div>
        </div>

        @if($farmers->isNotEmpty())
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-user-tie me-1"></i>Registered Farmers</h6></div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush" style="max-height:220px;overflow-y:auto;">
                    @foreach($farmers as $f)
                    <li class="list-group-item py-1 px-3 small">
                        <strong>{{ $f->name }}</strong>
                        <div class="text-muted" style="font-size:11px;">{{ $f->address }}</div>
                    </li>
                    @endforeach
                </ul>
                <div class="p-2 text-end">
                    <a href="{{ route('farmers.create') }}" class="btn btn-outline-success btn-sm">
                        <i class="fas fa-plus me-1"></i> Add Farmer
                    </a>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@endsection

@section('scripts')
<script>
(function () {
    const today    = '{{ date('Y-m-d') }}';
    const received = document.getElementById('sample_date');
    const tested   = document.getElementById('date_tested');
    const select   = document.getElementById('farmerSelect');

    // Farmer dropdown auto-fill
    if (select) {
        select.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            if (opt.value) {
                document.getElementById('farmerName').value    = opt.dataset.name    || '';
                document.getElementById('farmerAddress').value = opt.dataset.address || '';
                document.getElementById('farmerLocation').value= opt.dataset.location|| '';
            }
        });

        // Auto-fill if a value was pre-selected (e.g. old() on validation failure)
        if (select.value) select.dispatchEvent(new Event('change'));
    }

    // Date cross-validation
    received.addEventListener('change', function () {
        tested.min = this.value;
        if (tested.value && tested.value < this.value) tested.value = this.value;
    });
    tested.addEventListener('change', function () {
        if (this.value > today) this.value = today;
        if (this.value < received.value) this.value = received.value;
    });
})();
</script>
@endsection
