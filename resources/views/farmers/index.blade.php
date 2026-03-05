@extends('layouts.app')
@section('title', 'Farmers')
@section('content')

<div class="row mb-3 align-items-center">
    <div class="col">
        <h2><i class="fas fa-user-tie me-2"></i>Farmers</h2>
        <p class="lead text-muted mb-0">{{ auth()->user()->isAdmin() ? 'All registered farmers' : 'Your registered farmers' }}</p>
    </div>
    <div class="col-auto d-flex gap-2">
        <a href="{{ route('farmers.import') }}" class="btn btn-outline-success">
            <i class="fas fa-file-import me-1"></i> Import CSV
        </a>
        <a href="{{ route('farmers.create') }}" class="btn btn-success">
            <i class="fas fa-plus-circle me-1"></i> Add Farmer
        </a>
    </div>
</div>

@if(session('import_errors') && count(session('import_errors')))
<div class="alert alert-warning">
    <strong>Some rows could not be imported:</strong>
    <ul class="mb-0 mt-1 small">
        @foreach(session('import_errors') as $err)
        <li>{{ $err }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="card">
    <div class="card-body p-0">
        @if($farmers->isEmpty())
        <div class="text-center py-5">
            <i class="fas fa-user-tie fa-3x text-muted mb-3"></i>
            <p class="text-muted">No farmers registered yet.</p>
            <div class="d-flex gap-2 justify-content-center">
                <a href="{{ route('farmers.import') }}" class="btn btn-outline-success">
                    <i class="fas fa-file-import me-1"></i> Import from CSV
                </a>
                <a href="{{ route('farmers.create') }}" class="btn btn-success">
                    <i class="fas fa-plus-circle me-1"></i> Add Farmer
                </a>
            </div>
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-success">
                    <tr>
                        <th>#</th>
                        @if(auth()->user()->isAdmin())<th>Added By</th>@endif
                        <th>Name</th>
                        <th>Address</th>
                        <th>Farm Location</th>
                        <th>Farm ID <small class="fw-normal text-muted">(Phase 2)</small></th>
                        <th>Samples</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($farmers as $i => $f)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        @if(auth()->user()->isAdmin())
                        <td><small>{{ $f->user->username ?? '—' }}</small></td>
                        @endif
                        <td><strong>{{ $f->name }}</strong></td>
                        <td>{{ $f->address }}</td>
                        <td>{{ $f->farm_location ?? '—' }}</td>
                        <td>
                            @if($f->farm_id)
                                <code class="small">{{ $f->farm_id }}</code>
                            @else
                                <span class="text-muted small">Not set</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-secondary">{{ $f->soilSamples->count() }}</span>
                        </td>
                        <td>
                            <a href="{{ route('farmers.edit', $f) }}" class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="{{ route('farmers.destroy', $f) }}" class="d-inline"
                                  onsubmit="return confirm('Delete {{ addslashes($f->name) }}? Linked samples will not be deleted.')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-3 text-muted small border-top">
            {{ $farmers->count() }} farmer(s) registered.
        </div>
        @endif
    </div>
</div>

@endsection
