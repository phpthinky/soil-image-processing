<?php

namespace App\Http\Controllers;

use App\Models\Crop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CropController extends Controller
{
    public function index()
    {
        $crops = Crop::with('creator')->orderBy('name')->get();
        return view('crops.index', compact('crops'));
    }

    public function create()
    {
        return view('crops.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['created_by'] = Auth::id();

        Crop::create($data);

        return redirect()->route('crops.index')
            ->with('success', 'Crop "' . $data['name'] . '" added successfully.');
    }

    public function edit(Crop $crop)
    {
        return view('crops.edit', compact('crop'));
    }

    public function update(Request $request, Crop $crop)
    {
        $data = $this->validated($request, $crop->id);
        $crop->update($data);

        return redirect()->route('crops.index')
            ->with('success', 'Crop "' . $crop->name . '" updated.');
    }

    public function destroy(Crop $crop)
    {
        $name = $crop->name;
        $crop->delete();

        return redirect()->route('crops.index')
            ->with('success', 'Crop "' . $name . '" deleted.');
    }

    // ── Shared validation ─────────────────────────────────────────────────────

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $uniqueRule = 'unique:crops,name' . ($ignoreId ? ',' . $ignoreId : '');

        return $request->validate([
            'name'        => ['required', 'string', 'max:150', $uniqueRule],
            'description' => ['nullable', 'string', 'max:500'],
            'status'      => ['required', 'in:active,inactive'],

            'ph_low'  => ['nullable', 'numeric', 'min:0', 'max:14'],
            'ph_med'  => ['nullable', 'numeric', 'min:0', 'max:14'],
            'ph_high' => ['nullable', 'numeric', 'min:0', 'max:14'],

            'n_low'   => ['nullable', 'numeric', 'min:0'],
            'n_med'   => ['nullable', 'numeric', 'min:0'],
            'n_high'  => ['nullable', 'numeric', 'min:0'],

            'p_low'   => ['nullable', 'numeric', 'min:0'],
            'p_med'   => ['nullable', 'numeric', 'min:0'],
            'p_high'  => ['nullable', 'numeric', 'min:0'],

            'k_low'   => ['nullable', 'numeric', 'min:0'],
            'k_med'   => ['nullable', 'numeric', 'min:0'],
            'k_high'  => ['nullable', 'numeric', 'min:0'],
        ]);
    }
}
