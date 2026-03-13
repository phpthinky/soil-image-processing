@extends('layouts.app')
@section('title', 'Add Crop')
@section('content')

<div class="row mb-3 align-items-center">
    <div class="col">
        <h2><i class="fas fa-seedling me-2"></i>Add Crop</h2>
        <p class="lead text-muted mb-0">Define Low / Medium / High soil thresholds for this crop.</p>
    </div>
    <div class="col-auto">
        <a href="{{ route('crops.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Crops
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('crops.store') }}">
            @csrf
            @include('crops._form')
            <div class="d-flex gap-2 mt-2">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save me-1"></i> Save Crop
                </button>
                <a href="{{ route('crops.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

@endsection
