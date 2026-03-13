@extends('layouts.app')
@section('title', 'Crops')
@section('content')

<div class="row mb-3 align-items-center">
    <div class="col">
        <h2><i class="fas fa-seedling me-2"></i>Crops</h2>
        <p class="lead text-muted mb-0">Manage crop Low / Medium / High soil classification thresholds.</p>
    </div>
    <div class="col-auto">
        <a href="{{ route('crops.create') }}" class="btn btn-success">
            <i class="fas fa-plus-circle me-1"></i> Add Crop
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card">
    <div class="card-body p-0">
        @if($crops->isEmpty())
        <div class="text-center py-5">
            <i class="fas fa-seedling fa-3x text-muted mb-3"></i>
            <p class="text-muted">No crops added yet.</p>
            <a href="{{ route('crops.create') }}" class="btn btn-success">
                <i class="fas fa-plus-circle me-1"></i> Add First Crop
            </a>
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle table-sm mb-0" style="font-size:.85rem;">
                <thead class="table-success">
                    <tr>
                        <th rowspan="2" class="align-middle">#</th>
                        <th rowspan="2" class="align-middle">Crop</th>
                        <th colspan="3" class="text-center">pH</th>
                        <th colspan="3" class="text-center">Nitrogen (ppm)</th>
                        <th colspan="3" class="text-center">Phosphorus (ppm)</th>
                        <th colspan="3" class="text-center">Potassium (ppm)</th>
                        <th rowspan="2" class="align-middle text-center">Status</th>
                        <th rowspan="2" class="align-middle text-center">Created</th>
                        <th rowspan="2" class="align-middle text-center">By</th>
                        <th rowspan="2" class="align-middle text-center">Actions</th>
                    </tr>
                    <tr>
                        <th class="text-center bg-warning bg-opacity-25" style="font-size:.72rem;">Low</th>
                        <th class="text-center bg-success bg-opacity-25" style="font-size:.72rem;">Med</th>
                        <th class="text-center bg-info   bg-opacity-25" style="font-size:.72rem;">High</th>
                        <th class="text-center bg-warning bg-opacity-25" style="font-size:.72rem;">Low</th>
                        <th class="text-center bg-success bg-opacity-25" style="font-size:.72rem;">Med</th>
                        <th class="text-center bg-info   bg-opacity-25" style="font-size:.72rem;">High</th>
                        <th class="text-center bg-warning bg-opacity-25" style="font-size:.72rem;">Low</th>
                        <th class="text-center bg-success bg-opacity-25" style="font-size:.72rem;">Med</th>
                        <th class="text-center bg-info   bg-opacity-25" style="font-size:.72rem;">High</th>
                        <th class="text-center bg-warning bg-opacity-25" style="font-size:.72rem;">Low</th>
                        <th class="text-center bg-success bg-opacity-25" style="font-size:.72rem;">Med</th>
                        <th class="text-center bg-info   bg-opacity-25" style="font-size:.72rem;">High</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($crops as $i => $crop)
                    <tr>
                        <td class="text-muted">{{ $i + 1 }}</td>
                        <td>
                            <strong>{{ $crop->name }}</strong>
                            @if($crop->description)
                                <br><small class="text-muted">{{ Str::limit($crop->description, 60) }}</small>
                            @endif
                        </td>
                        {{-- pH --}}
                        <td class="text-center">{{ $crop->ph_low  ?? '—' }}</td>
                        <td class="text-center">{{ $crop->ph_med  ?? '—' }}</td>
                        <td class="text-center">{{ $crop->ph_high ?? '—' }}</td>
                        {{-- N --}}
                        <td class="text-center">{{ $crop->n_low  ?? '—' }}</td>
                        <td class="text-center">{{ $crop->n_med  ?? '—' }}</td>
                        <td class="text-center">{{ $crop->n_high ?? '—' }}</td>
                        {{-- P --}}
                        <td class="text-center">{{ $crop->p_low  ?? '—' }}</td>
                        <td class="text-center">{{ $crop->p_med  ?? '—' }}</td>
                        <td class="text-center">{{ $crop->p_high ?? '—' }}</td>
                        {{-- K --}}
                        <td class="text-center">{{ $crop->k_low  ?? '—' }}</td>
                        <td class="text-center">{{ $crop->k_med  ?? '—' }}</td>
                        <td class="text-center">{{ $crop->k_high ?? '—' }}</td>
                        {{-- Status --}}
                        <td class="text-center">
                            @if($crop->status === 'active')
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        {{-- Created date --}}
                        <td class="text-center text-muted" style="font-size:.75rem;">
                            {{ $crop->created_at->format('M d, Y') }}
                        </td>
                        {{-- Created by --}}
                        <td class="text-center text-muted" style="font-size:.75rem;">
                            {{ $crop->creator->username ?? '—' }}
                        </td>
                        {{-- Actions --}}
                        <td class="text-center" style="white-space:nowrap;">
                            <a href="{{ route('crops.edit', $crop) }}"
                               class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="{{ route('crops.destroy', $crop) }}"
                                  class="d-inline"
                                  onsubmit="return confirm('Delete crop \'{{ addslashes($crop->name) }}\'? This cannot be undone.')">
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
            {{ $crops->count() }} crop(s) total &mdash;
            {{ $crops->where('status', 'active')->count() }} active,
            {{ $crops->where('status', 'inactive')->count() }} inactive.
        </div>
        @endif
    </div>
</div>

@endsection
