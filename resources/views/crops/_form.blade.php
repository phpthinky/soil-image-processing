{{-- Shared form fields for create and edit --}}

<div class="mb-3">
    <label class="form-label fw-semibold">Crop Name <span class="text-danger">*</span></label>
    <input type="text" name="name"
           class="form-control @error('name') is-invalid @enderror"
           value="{{ old('name', $crop->name ?? '') }}"
           placeholder="e.g., Rice, Corn, Banana" required>
    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label class="form-label fw-semibold">Description</label>
    <textarea name="description" rows="2"
              class="form-control @error('description') is-invalid @enderror"
              placeholder="Optional short description">{{ old('description', $crop->description ?? '') }}</textarea>
    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-4">
    <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
    <select name="status" class="form-select @error('status') is-invalid @enderror" required>
        <option value="active"   {{ old('status', $crop->status ?? 'active') === 'active'   ? 'selected' : '' }}>Active</option>
        <option value="inactive" {{ old('status', $crop->status ?? 'active') === 'inactive' ? 'selected' : '' }}>Inactive</option>
    </select>
    <div class="form-text">Inactive crops are hidden from soil analysis recommendations.</div>
    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<hr class="my-4">

{{-- Threshold fields helper --}}
@php
    $hint = fn(string $nutrient, string $unit) =>
        "Set the Low / Medium (optimal) / High classification boundaries for {$nutrient} ({$unit}). Leave blank to skip this nutrient in scoring.";
@endphp

{{-- pH --}}
<h6 class="text-success mb-2"><i class="fas fa-flask me-1"></i>pH Thresholds <small class="text-muted fw-normal">(0 – 14 scale)</small></h6>
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <label class="form-label">pH Low <small class="text-muted">(upper bound of Low band)</small></label>
        <input type="number" step="0.01" min="0" max="14" name="ph_low"
               class="form-control @error('ph_low') is-invalid @enderror"
               value="{{ old('ph_low', $crop->ph_low ?? '') }}" placeholder="e.g. 5.5">
        <div class="form-text">Soil pH below this = <span class="badge bg-warning text-dark">Low</span></div>
        @error('ph_low')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">pH Medium <small class="text-muted">(optimal target)</small></label>
        <input type="number" step="0.01" min="0" max="14" name="ph_med"
               class="form-control @error('ph_med') is-invalid @enderror"
               value="{{ old('ph_med', $crop->ph_med ?? '') }}" placeholder="e.g. 6.0">
        <div class="form-text">Ideal pH for this crop <span class="badge bg-success">Medium</span></div>
        @error('ph_med')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">pH High <small class="text-muted">(lower bound of High band)</small></label>
        <input type="number" step="0.01" min="0" max="14" name="ph_high"
               class="form-control @error('ph_high') is-invalid @enderror"
               value="{{ old('ph_high', $crop->ph_high ?? '') }}" placeholder="e.g. 7.0">
        <div class="form-text">Soil pH above this = <span class="badge bg-info">High</span></div>
        @error('ph_high')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
<h4 class="text-primary">Crop target fertilizer - STK</h4>
<div class="alert alert-info d-flex align-items-center" role="alert">
  
  <div>
    <p><i class="fa fa-info-circle me-2 fs-5"></i> <strong>Unit Conversion Guide:</strong></p>
    <p> Note: Crop nutrient requirements in this system are stored in <strong>kg/ha</strong> (e.g., OMA). If your data is in <strong>ppm</strong>,please convert it first using the formula below.</p>
    <span class="fw-medium">Conversion formula:</span> <ul><li>ppm = kg/ha &divide; 2</li><li>kg/ha = ppm x 2</li></ul> 
  </div>
</div>
{{-- Nitrogen --}}
<h6 class="text-success mb-2"><i class="fas fa-atom me-1"></i>Nitrogen (N) Thresholds <small class="text-muted fw-normal">(kg/ha)</small></h6>
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <label class="form-label">N Low</label>
        <input type="number" step="0.01" min="0" name="n_low"
               class="form-control @error('n_low') is-invalid @enderror"
               value="{{ old('n_low', $crop->n_low ?? '') }}" placeholder="e.g. 90">
        @error('n_low')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">N Medium <small class="text-muted">(optimal target)</small></label>
        <input type="number" step="0.01" min="0" name="n_med"
               class="form-control @error('n_med') is-invalid @enderror"
               value="{{ old('n_med', $crop->n_med ?? '') }}" placeholder="e.g. 60">
        @error('n_med')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">N High</label>
        <input type="number" step="0.01" min="0" name="n_high"
               class="form-control @error('n_high') is-invalid @enderror"
               value="{{ old('n_high', $crop->n_high ?? '') }}" placeholder="e.g. 20">
        @error('n_high')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>


{{-- Phosphorus --}}
<h6 class="text-success mb-2"><i class="fas fa-atom me-1"></i>Phosphorus (P) Thresholds <small class="text-muted fw-normal">(kg/ha)</small></h6>
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <label class="form-label">P Low</label>
        <input type="number" step="0.01" min="0" name="p_low"
               class="form-control @error('p_low') is-invalid @enderror"
               value="{{ old('p_low', $crop->p_low ?? '') }}" placeholder="e.g. 20">
        @error('p_low')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">P Medium <small class="text-muted">(optimal target)</small></label>
        <input type="number" step="0.01" min="0" name="p_med"
               class="form-control @error('p_med') is-invalid @enderror"
               value="{{ old('p_med', $crop->p_med ?? '') }}" placeholder="e.g. 15">
        @error('p_med')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">P High</label>
        <input type="number" step="0.01" min="0" name="p_high"
               class="form-control @error('p_high') is-invalid @enderror"
               value="{{ old('p_high', $crop->p_high ?? '') }}" placeholder="e.g. 10">
        @error('p_high')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

{{-- Potassium --}}
<h6 class="text-success mb-2"><i class="fas fa-atom me-1"></i>Potassium (K) Thresholds <small class="text-muted fw-normal">(kg/ha)</small></h6>
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <label class="form-label">K Low</label>
        <input type="number" step="0.01" min="0" name="k_low"
               class="form-control @error('k_low') is-invalid @enderror"
               value="{{ old('k_low', $crop->k_low ?? '') }}" placeholder="e.g. 200">
        @error('k_low')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">K Medium <small class="text-muted">(optimal target)</small></label>
        <input type="number" step="0.01" min="0" name="k_med"
               class="form-control @error('k_med') is-invalid @enderror"
               value="{{ old('k_med', $crop->k_med ?? '') }}" placeholder="e.g. 100">
        @error('k_med')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">K High</label>
        <input type="number" step="0.01" min="0" name="k_high"
               class="form-control @error('k_high') is-invalid @enderror"
               value="{{ old('k_high', $crop->k_high ?? '') }}" placeholder="e.g. 50">
        @error('k_high')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
