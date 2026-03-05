{{-- Shared form fields for create and edit --}}
<div class="mb-3">
    <label class="form-label fw-semibold">Full Name *</label>
    <input type="text" class="form-control @error('name') is-invalid @enderror"
           name="name" value="{{ old('name', $farmer->name ?? '') }}"
           placeholder="e.g., Juan Dela Cruz" required>
    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label class="form-label fw-semibold">Address *</label>
    <input type="text" class="form-control @error('address') is-invalid @enderror"
           name="address" value="{{ old('address', $farmer->address ?? '') }}"
           placeholder="e.g., Poblacion I, San Teodoro, Oriental Mindoro" required>
    @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label class="form-label fw-semibold">Farm Location / Barangay</label>
    <input type="text" class="form-control @error('farm_location') is-invalid @enderror"
           name="farm_location" value="{{ old('farm_location', $farmer->farm_location ?? '') }}"
           placeholder="e.g., Brgy. Labasan, Field Block A">
    <div class="form-text">Required — specific field location</div>
    @error('farm_location')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-4">
    <label class="form-label fw-semibold">Farm ID <span class="badge bg-info text-dark ms-1" style="font-size:.65rem;">Phase 2</span></label>
    <input type="text" class="form-control @error('farm_id') is-invalid @enderror"
           name="farm_id" value="{{ old('farm_id', $farmer->farm_id ?? '') }}"
           placeholder="e.g., Farm-001 or Farm ID">
    <div class="form-text">Optional — OMA official farm record ID for sensor matching</div>
    @error('farm_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
