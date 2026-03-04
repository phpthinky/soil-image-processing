@extends('layouts.app')
@section('title', 'Add Farmer')
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
                <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Add Farmer</h5>
            </div>
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif
                <form method="POST" action="{{ route('farmers.store') }}">
                    @csrf
                    @include('farmers._form')
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i> Save Farmer
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">About Farm ID</h6></div>
            <div class="card-body small text-muted">
                <p><strong>Farm ID</strong> is an optional identifier used for <strong>Phase 2 (Arduino)</strong> integration.</p>
                <p>If you have an Arduino sensor deployed at a farm, enter its device/record ID here. This will be included in the Phase 2 CSV export so the Arduino device can be matched to the farmer's soil records.</p>
                <p class="mb-0">Leave blank if not applicable — it can be filled in later.</p>
            </div>
        </div>
    </div>
</div>

@endsection
