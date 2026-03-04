@extends('layouts.app')
@section('title', 'Soil Sample Results')
@section('content')

<div class="row mb-3">
    <div class="col-md-8">
        <h2><i class="fas fa-list-alt me-2"></i>Soil Sample Results</h2>
        <p class="lead text-muted">{{ auth()->user()->isAdmin() ? 'All system samples' : 'Your submitted soil samples' }}</p>
    </div>
    <div class="col-md-4 text-end">
        <a href="{{ route('samples.create') }}" class="btn btn-success me-2">
            <i class="fas fa-plus-circle me-1"></i> New Sample
        </a>
        @if(auth()->user()->isAdmin())
        <a href="{{ route('export') }}" class="btn btn-outline-success">
            <i class="fas fa-file-excel me-1"></i> Export All
        </a>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-body">
        @if($samples->isEmpty())
        <div class="text-center py-5">
            <i class="fas fa-vial fa-3x text-muted mb-3"></i>
            <p class="text-muted">No soil samples yet.</p>
            <a href="{{ route('samples.create') }}" class="btn btn-success">
                <i class="fas fa-plus-circle"></i> Add Your First Sample
            </a>
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-success">
                    <tr>
                        <th>#</th>
                        @if(auth()->user()->isAdmin())<th>User</th>@endif
                        <th>Sample Name</th>
                        <th>Farmer</th>
                        <th>Date Received</th>
                        <th>pH</th><th>N</th><th>P</th><th>K</th>
                        <th>Score</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($samples as $i => $s)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        @if(auth()->user()->isAdmin())<td>{{ $s->user->username }}</td>@endif
                        <td>{{ $s->sample_name }}</td>
                        <td>{{ $s->farmer_name }}</td>
                        <td>{{ $s->sample_date->format('M j, Y') }}</td>
                        <td>{{ !is_null($s->ph_level) ? number_format($s->ph_level,1) : '—' }}</td>
                        <td>{{ !is_null($s->nitrogen_level) ? number_format($s->nitrogen_level,1) : '—' }}</td>
                        <td>{{ !is_null($s->phosphorus_level) ? number_format($s->phosphorus_level,1) : '—' }}</td>
                        <td>{{ !is_null($s->potassium_level) ? number_format($s->potassium_level,1) : '—' }}</td>
                        <td>
                            @if(!is_null($s->fertility_score))
                            <span class="badge bg-{{ $s->fertilityColorClass() }}">{{ $s->fertility_score }}%</span>
                            @else —
                            @endif
                        </td>
                        <td>
                            @if($s->analyzed_at)
                                <span class="badge bg-success">Analyzed</span>
                            @elseif($s->ph_color_hex)
                                <span class="badge bg-warning text-dark">In Progress</span>
                            @else
                                <span class="badge bg-secondary">Pending</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('samples.show', $s) }}" class="btn btn-sm btn-outline-primary me-1">
                                <i class="fas fa-eye"></i>
                            </a>
                            @if($s->analyzed_at)
                            <a href="{{ route('export', ['sample_id' => $s->id]) }}" class="btn btn-sm btn-outline-success">
                                <i class="fas fa-file-excel"></i>
                            </a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection
