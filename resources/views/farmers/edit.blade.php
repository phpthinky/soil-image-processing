@extends('layouts.app')
@section('title', 'Edit Farmer')
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
                <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Edit Farmer — {{ $farmer->name }}</h5>
            </div>
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif
                <form method="POST" action="{{ route('farmers.update', $farmer) }}">
                    @csrf @method('PUT')
                    @include('farmers._form')
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-1"></i> Update Farmer
                        </button>
                        <a href="{{ route('farmers.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Linked Samples</h6></div>
            <div class="card-body">
                @php $linked = $farmer->soilSamples()->latest()->get(); @endphp
                @if($linked->isEmpty())
                    <p class="text-muted small mb-0">No soil samples linked to this farmer yet.</p>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach($linked as $s)
                        <li class="list-group-item py-1 px-0 small d-flex justify-content-between">
                            <a href="{{ route('samples.show', $s) }}">{{ $s->sample_name }}</a>
                            <span class="text-muted">{{ $s->sample_date->format('M j, Y') }}</span>
                        </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
