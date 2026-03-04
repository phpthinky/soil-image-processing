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
        <div class="card">
            <div class="card-header"><h5>Soil Sample Information</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('samples.store') }}" id="sampleForm">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Name of Farmer *</label>
                                <input type="text" class="form-control" name="farmer_name" value="{{ old('farmer_name') }}" placeholder="Enter Farmer's Name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Address *</label>
                                <input type="text" class="form-control" name="address" value="{{ old('address') }}" placeholder="e.g., Poblacion I, San Teodoro" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Farm Location</label>
                        <input type="text" class="form-control" name="location" value="{{ old('location') }}" placeholder="e.g., Field A, North Section">
                        <div class="form-text">Optional: Specify where the sample was taken from</div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Sample Name *</label>
                                <input type="text" class="form-control" name="sample_name" value="{{ old('sample_name') }}" required>
                                <div class="form-text">Give a descriptive name for your soil sample</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Date Received *</label>
                                <input type="date" class="form-control @error('sample_date') is-invalid @enderror"
                                       name="sample_date" id="sample_date"
                                       value="{{ old('sample_date', date('Y-m-d')) }}"
                                       max="{{ date('Y-m-d') }}" required>
                                <div class="form-text">Cannot be a future date.</div>
                                @error('sample_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Date Tested *</label>
                                <input type="date" class="form-control @error('date_tested') is-invalid @enderror"
                                       name="date_tested" id="date_tested"
                                       value="{{ old('date_tested', date('Y-m-d')) }}"
                                       min="{{ old('sample_date', date('Y-m-d')) }}"
                                       max="{{ date('Y-m-d') }}" required>
                                <div class="form-text">Must be on or after the received date, not a future date.</div>
                                @error('date_tested')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Note:</strong> After adding the soil sample, you can analyze it using the system's webcam-based image analysis to determine soil pH, N, P, and K levels.
                    </div>
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-plus-circle"></i> Add Soil Sample
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h5>Sample Collection Guide</h5></div>
            <div class="card-body">
                <p><strong>Proper soil sampling technique:</strong></p>
                <ol class="small">
                    <li>Collect samples from multiple locations in the field</li>
                    <li>Take samples at 6-8 inches depth</li>
                    <li>Mix samples thoroughly in a clean container</li>
                    <li>Allow soil to air dry before analysis</li>
                    <li>Label samples clearly with location and date</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
    const today      = '{{ date('Y-m-d') }}';
    const received   = document.getElementById('sample_date');
    const tested     = document.getElementById('date_tested');

    // When received date changes: update tested min to match
    received.addEventListener('change', function () {
        tested.min = this.value;
        // If tested is now before received, push it forward
        if (tested.value && tested.value < this.value) {
            tested.value = this.value;
        }
    });

    // Guard: tested cannot exceed today
    tested.addEventListener('change', function () {
        if (this.value > today) this.value = today;
        if (this.value < received.value) this.value = received.value;
    });
})();
</script>
@endsection
