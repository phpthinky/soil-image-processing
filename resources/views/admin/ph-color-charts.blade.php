@extends('layouts.app')
@section('title', 'pH Color Chart Manager')

@section('styles')
    .color-swatch {
        display: inline-block;
        width: 36px;
        height: 24px;
        border-radius: 4px;
        border: 1px solid rgba(0,0,0,.25);
        vertical-align: middle;
        box-shadow: inset 0 1px 2px rgba(0,0,0,.15);
    }
    .indicator-tab.active { font-weight: 700; }
    .entry-row.inactive td { opacity: .45; }
@endsection

@section('content')
<div class="row mb-3">
    <div class="col">
        <h2><i class="fa fa-palette me-2 text-success"></i>pH Color Chart Manager</h2>
        <p class="text-muted mb-0">Manage the reference hex colors used by CIEDE2000 delta-E matching for each indicator solution.</p>
    </div>
</div>

{{-- Alerts --}}
@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fa fa-exclamation-circle me-1"></i> {{ $errors->first() }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-4">

    {{-- ── Add New Entry ────────────────────────────────────────── --}}
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header bg-success text-white">
                <i class="fa fa-plus me-1"></i> Add Color Entry
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.ph-color-charts.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Indicator</label>
                        <select class="form-select" name="indicator" required>
                            <option value="">— select —</option>
                            @foreach(['CPR','BCG','BTB'] as $ind)
                            <option value="{{ $ind }}" {{ old('indicator') === $ind ? 'selected' : '' }}>
                                {{ $ind }}
                                @if($ind === 'CPR') — Cresol Red + Phenolphthalein (Step 1)
                                @elseif($ind === 'BCG') — Bromocresol Green (Step 2, acidic)
                                @else — Bromothymol Blue (Step 2, near-neutral)
                                @endif
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">pH Value</label>
                        <input type="number" class="form-control" name="ph_value"
                               value="{{ old('ph_value') }}" min="0" max="14" step="0.1"
                               placeholder="e.g. 5.4" required>
                        <div class="form-text">One decimal place (e.g. 5.0, 5.2, 6.8)</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Hex Color Value</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" id="colorPicker"
                                   value="{{ old('hex_value', '#FF8800') }}"
                                   oninput="document.getElementById('hexInput').value = this.value.toUpperCase()">
                            <input type="text" class="form-control font-monospace" id="hexInput"
                                   name="hex_value" value="{{ old('hex_value', '#FF8800') }}"
                                   placeholder="#RRGGBB" pattern="^#[0-9A-Fa-f]{6}$"
                                   oninput="syncPicker(this.value)" required>
                        </div>
                        <div class="form-text">Measured from physical BSWM card under calibrated lighting.</div>
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="fa fa-plus me-1"></i> Add Entry
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ── Chart Tables ─────────────────────────────────────────── --}}
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" id="indicatorTabs">
                    @foreach(['CPR','BCG','BTB'] as $ind)
                    <li class="nav-item">
                        <button class="nav-link indicator-tab {{ $loop->first ? 'active' : '' }}"
                                data-bs-toggle="tab" data-bs-target="#tab-{{ $ind }}">
                            {{ $ind }}
                            <span class="badge bg-secondary ms-1">{{ ($charts[$ind] ?? collect())->count() }}</span>
                        </button>
                    </li>
                    @endforeach
                </ul>
            </div>
            <div class="card-body p-0">
                <div class="tab-content">
                    @foreach(['CPR','BCG','BTB'] as $ind)
                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="tab-{{ $ind }}">
                        @php $entries = $charts[$ind] ?? collect(); @endphp
                        @if($entries->isEmpty())
                        <div class="p-4 text-muted text-center">
                            <i class="fa fa-inbox fa-2x mb-2 d-block"></i>No entries for {{ $ind }} yet.
                        </div>
                        @else
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Swatch</th>
                                        <th>Hex</th>
                                        <th>pH Value</th>
                                        <th class="text-center">Active</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($entries as $entry)
                                    <tr class="entry-row {{ $entry->active ? '' : 'inactive' }}">
                                        <td>
                                            <span class="color-swatch" style="background:{{ $entry->hex_value }}"></span>
                                        </td>
                                        <td class="font-monospace">{{ $entry->hex_value }}</td>
                                        <td><strong>{{ number_format($entry->ph_value, 1) }}</strong></td>
                                        <td class="text-center">
                                            @if($entry->active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary">Inactive</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                {{-- Toggle active --}}
                                                <form method="POST" action="{{ route('admin.ph-color-charts.toggle', $entry) }}">
                                                    @csrf @method('PATCH')
                                                    <button type="submit"
                                                            class="btn {{ $entry->active ? 'btn-outline-secondary' : 'btn-outline-success' }}"
                                                            title="{{ $entry->active ? 'Deactivate' : 'Activate' }}">
                                                        <i class="fa {{ $entry->active ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                                                    </button>
                                                </form>
                                                {{-- Delete — opens danger modal --}}
                                                <button type="button"
                                                        class="btn btn-outline-danger"
                                                        title="Delete permanently"
                                                        onclick="openDeleteModal({{ $entry->id }}, '{{ $entry->hex_value }}', '{{ $ind }}', {{ $entry->ph_value }})">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="mt-3 p-3 bg-light rounded border small text-muted">
            <i class="fa fa-info-circle me-1 text-primary"></i>
            <strong>Tip:</strong> Deactivating an entry removes it from delta-E matching without deleting it — useful for temporary calibration tests.
            Multiple hex values per pH point are supported and improve CIEDE2000 matching accuracy.
        </div>
    </div>

</div>
{{-- ── Delete Danger Modal ──────────────────────────────────────── --}}
<div class="modal fade" id="deleteModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-danger">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fa fa-exclamation-triangle me-2"></i>Permanent Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger mb-3">
                    <strong>Warning:</strong> You are about to permanently delete this color reference entry.
                    This action <strong>cannot be undone</strong> and will immediately affect color matching
                    for all future pH test captures.
                </div>
                <p class="mb-1">Entry to delete:</p>
                <div class="d-flex align-items-center gap-3 p-2 rounded bg-light border mb-3">
                    <span id="modal-swatch" class="color-swatch" style="width:40px;height:28px;"></span>
                    <span id="modal-label" class="font-monospace fw-bold"></span>
                </div>
                <form id="deleteForm" method="POST">
                    @csrf @method('DELETE')
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i> Cancel
                </button>
                <button type="button" class="btn btn-danger" onclick="document.getElementById('deleteForm').submit()">
                    <i class="fa fa-trash me-1"></i> Delete Permanently
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
function syncPicker(hexVal) {
    if (/^#[0-9A-Fa-f]{6}$/.test(hexVal)) {
        document.getElementById('colorPicker').value = hexVal;
    }
}

function openDeleteModal(id, hex, indicator, ph) {
    document.getElementById('deleteForm').action = `/admin/ph-color-charts/${id}`;
    document.getElementById('modal-swatch').style.background = hex;
    document.getElementById('modal-label').textContent = `${hex}  —  ${indicator}  pH ${ph.toFixed(1)}`;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
@endsection
