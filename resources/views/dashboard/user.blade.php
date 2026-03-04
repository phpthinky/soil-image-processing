@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')

<div class="row">
    <div class="col-md-12">
        <h2>Dashboard</h2>
        <p class="lead">Welcome back, {{ auth()->user()->username }}!</p>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div><h4>{{ $sampleCount }}</h4><p>Soil Samples</p></div>
                    <div class="align-self-center"><i class="fas fa-vial fa-2x"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div><h4>{{ now()->format('M j, Y') }}</h4><p>Today's Date</p></div>
                    <div class="align-self-center"><i class="fas fa-calendar fa-2x"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div><h4>{{ ucfirst(auth()->user()->user_type) }}</h4><p>User Type</p></div>
                    <div class="align-self-center"><i class="fas fa-user fa-2x"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div><h4>OMA</h4><p>Municipality Agriculturist</p></div>
                    <div class="align-self-center"><i class="fas fa-seedling fa-2x"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h5>Recent Soil Samples</h5></div>
            <div class="card-body">
                @if($recentSamples->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr><th>Sample Name</th><th>Date</th><th>pH Level</th><th>Fertility Score</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            @foreach($recentSamples as $sample)
                            <tr>
                                <td>{{ $sample->sample_name }}</td>
                                <td>{{ $sample->sample_date->format('M j, Y') }}</td>
                                <td>{{ $sample->ph_level ?? '—' }}</td>
                                <td>
                                    @if(!is_null($sample->fertility_score))
                                    <span class="badge bg-{{ $sample->fertility_score >= 80 ? 'success' : ($sample->fertility_score >= 60 ? 'warning' : 'danger') }}">
                                        {{ $sample->fertility_score }}%
                                    </span>
                                    @else —
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('samples.show', $sample) }}" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p>No soil samples found. <a href="{{ route('samples.create') }}">Analyze your first sample</a>.</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h5>Quick Actions</h5></div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('samples.create') }}" class="btn btn-success btn-lg">
                        <i class="fas fa-plus-circle"></i> New Soil Analysis
                    </a>
                    <a href="{{ route('samples.index') }}" class="btn btn-outline-primary">
                        <i class="fas fa-chart-bar"></i> View All Results
                    </a>
                </div>
                <hr>
                <h6>Soil Fertility Guide</h6>
                <ul class="list-unstyled">
                    <li><span class="badge bg-success">75-100%</span> High Fertility</li>
                    <li><span class="badge bg-warning">50-74%</span> Medium Fertility</li>
                    <li><span class="badge bg-danger">0-49%</span> Low Fertility</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
