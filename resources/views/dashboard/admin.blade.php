@extends('layouts.app')
@section('title', 'Admin Dashboard')
@section('content')

<div class="row">
    <div class="col-md-12">
        <h2>Admin Dashboard</h2>
        <p class="lead">System overview and management.</p>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div><h4>{{ $usersCount }}</h4><p>Total Users</p></div>
                    <div class="align-self-center"><i class="fas fa-users fa-2x"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div><h4>{{ $samplesCount }}</h4><p>Soil Samples</p></div>
                    <div class="align-self-center"><i class="fas fa-vial fa-2x"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div><h4>{{ $cropsCount }}</h4><p>Crop Types</p></div>
                    <div class="align-self-center"><i class="fas fa-seedling fa-2x"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div><h4>OMA</h4><p>Administration</p></div>
                    <div class="align-self-center"><i class="fas fa-cogs fa-2x"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h5>Recent Soil Samples</h5></div>
            <div class="card-body">
                @if($recentSamples->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>Sample</th><th>User</th><th>Date</th><th>Fertility</th></tr></thead>
                        <tbody>
                            @foreach($recentSamples as $sample)
                            <tr>
                                <td>{{ $sample->sample_name }}</td>
                                <td>{{ $sample->user->username }}</td>
                                <td>{{ $sample->created_at->format('M j') }}</td>
                                <td>
                                    @if(!is_null($sample->fertility_score))
                                    <span class="badge bg-{{ $sample->fertility_score >= 80 ? 'success' : ($sample->fertility_score >= 60 ? 'warning' : 'danger') }}">
                                        {{ $sample->fertility_score }}%
                                    </span>
                                    @else —
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p>No soil samples found.</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h5>Quick Actions</h5></div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.users') }}" class="btn btn-outline-primary">
                        <i class="fas fa-users"></i> Manage Users
                    </a>
                    <a href="{{ route('samples.create') }}" class="btn btn-outline-success">
                        <i class="fas fa-flask"></i> New Soil Analysis
                    </a>
                    <a href="{{ route('samples.index') }}" class="btn btn-outline-info">
                        <i class="fas fa-chart-bar"></i> All Results
                    </a>
                    <a href="{{ route('export') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-file-excel"></i> Export All Data
                    </a>
                </div>
            </div>
        </div>
        <div class="card mt-4">
            <div class="card-header"><h5>System Information</h5></div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr><td><strong>System Version:</strong></td><td>Soil Fertility Analyzer 2.0</td></tr>
                    <tr><td><strong>Framework:</strong></td><td>Laravel 11</td></tr>
                    <tr><td><strong>Last Update:</strong></td><td>{{ now()->format('F j, Y') }}</td></tr>
                    <tr><td><strong>Sensor Type:</strong></td><td>HD WEB CAM</td></tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
