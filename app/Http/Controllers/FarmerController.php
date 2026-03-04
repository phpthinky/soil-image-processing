<?php

namespace App\Http\Controllers;

use App\Models\Farmer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FarmerController extends Controller
{
    private function farmerQuery()
    {
        $user = Auth::user();
        return $user->isAdmin()
            ? Farmer::with('user')
            : Farmer::where('user_id', $user->id);
    }

    public function index()
    {
        $farmers = $this->farmerQuery()->latest()->get();
        return view('farmers.index', compact('farmers'));
    }

    public function create()
    {
        return view('farmers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:150',
            'address'       => 'required|string|max:255',
            'farm_location' => 'nullable|string|max:200',
            'farm_id'       => 'nullable|string|max:100',
        ]);

        Auth::user()->farmers()->create($data);

        return redirect()->route('farmers.index')
            ->with('success', 'Farmer added successfully.');
    }

    public function edit(Farmer $farmer)
    {
        $this->authorise($farmer);
        return view('farmers.edit', compact('farmer'));
    }

    public function update(Request $request, Farmer $farmer)
    {
        $this->authorise($farmer);

        $data = $request->validate([
            'name'          => 'required|string|max:150',
            'address'       => 'required|string|max:255',
            'farm_location' => 'nullable|string|max:200',
            'farm_id'       => 'nullable|string|max:100',
        ]);

        $farmer->update($data);

        return redirect()->route('farmers.index')
            ->with('success', 'Farmer updated successfully.');
    }

    public function destroy(Farmer $farmer)
    {
        $this->authorise($farmer);
        $farmer->delete();

        return redirect()->route('farmers.index')
            ->with('success', 'Farmer deleted.');
    }

    // ── CSV Import ────────────────────────────────────────────────

    public function importForm()
    {
        return view('farmers.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file     = $request->file('csv_file');
        $handle   = fopen($file->getRealPath(), 'r');
        $header   = null;
        $imported = 0;
        $skipped  = 0;
        $errors   = [];
        $userId   = Auth::id();

        while (($row = fgetcsv($handle)) !== false) {
            // Skip blank lines
            if (empty(array_filter($row))) continue;

            // First non-empty row is the header
            if ($header === null) {
                $header = array_map(fn($h) => strtolower(trim($h)), $row);
                continue;
            }

            $data = array_combine($header, $row);

            $name = trim($data['name'] ?? '');
            if (empty($name)) {
                $skipped++;
                continue;
            }

            try {
                Farmer::create([
                    'user_id'       => $userId,
                    'name'          => $name,
                    'address'       => trim($data['address'] ?? ''),
                    'farm_location' => trim($data['farm_location'] ?? '') ?: null,
                    'farm_id'       => trim($data['farm_id'] ?? '') ?: null,
                ]);
                $imported++;
            } catch (\Throwable $e) {
                $errors[] = "Row for "{$name}": " . $e->getMessage();
            }
        }

        fclose($handle);

        $message = "Imported {$imported} farmer(s).";
        if ($skipped)        $message .= " Skipped {$skipped} empty row(s).";
        if (!empty($errors)) $message .= ' Some rows had errors — see details below.';

        return redirect()->route('farmers.index')
            ->with('success', $message)
            ->with('import_errors', $errors);
    }

    // ── JSON endpoint used by sample create form ──────────────────

    public function json()
    {
        $farmers = $this->farmerQuery()
            ->select('id', 'name', 'address', 'farm_location', 'farm_id')
            ->orderBy('name')
            ->get();

        return response()->json($farmers);
    }

    // ─────────────────────────────────────────────────────────────

    private function authorise(Farmer $farmer): void
    {
        $user = Auth::user();
        if (!$user->isAdmin() && $farmer->user_id !== $user->id) {
            abort(403);
        }
    }
}
