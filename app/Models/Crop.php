<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Crop extends Model
{
    protected $fillable = [
        'name', 'description',
        'ph_low', 'ph_med', 'ph_high',
        'n_low',  'n_med',  'n_high',
        'p_low',  'p_med',  'p_high',
        'k_low',  'k_med',  'k_high',
        'status',
        'created_by',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Return the top-matching active crop name for a soil reading.
     * Uses ph_med / n_med / p_med / k_med as target values for scoring.
     */
    public static function topMatchName(float $ph, float $n, float $p, float $k): ?string
    {
        $crop = static::active()
            ->selectRaw("name,
                ABS(COALESCE(ph_med, 7) - ?) +
                ABS(COALESCE(n_med,  0) - ?) +
                ABS(COALESCE(p_med,  0) - ?) +
                ABS(COALESCE(k_med,  0) - ?) AS deviation",
                [$ph, $n, $p, $k])
            ->orderBy('deviation')
            ->orderBy('name')
            ->first();

        return $crop?->name;
    }
}
