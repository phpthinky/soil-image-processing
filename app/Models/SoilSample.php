<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SoilSample extends Model
{
    protected $fillable = [
        'user_id', 'sample_name', 'location', 'sample_date',
        'farmer_name', 'address', 'date_tested', 'color_hex',
        'ph_color_hex', 'nitrogen_color_hex', 'phosphorus_color_hex', 'potassium_color_hex',
        'ph_level', 'nitrogen_level', 'phosphorus_level', 'potassium_level',
        'fertility_score', 'ai_recommendation', 'recommended_crop',
        'tests_completed', 'analyzed_at',
    ];

    protected function casts(): array
    {
        return [
            'sample_date'  => 'date',
            'date_tested'  => 'date',
            'analyzed_at'  => 'datetime',
            'ph_level'     => 'float',
            'nitrogen_level'   => 'float',
            'phosphorus_level' => 'float',
            'potassium_level'  => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function colorReadings(): HasMany
    {
        return $this->hasMany(SoilColorReading::class, 'sample_id');
    }

    public function isAnalyzed(): bool
    {
        return !is_null($this->ph_level);
    }

    public function allAveraged(): bool
    {
        return $this->ph_color_hex && $this->nitrogen_color_hex
            && $this->phosphorus_color_hex && $this->potassium_color_hex;
    }

    public function getReadingsByParameter(): array
    {
        $readings = ['ph' => [], 'nitrogen' => [], 'phosphorus' => [], 'potassium' => []];
        foreach ($this->colorReadings()->orderBy('parameter')->orderBy('test_number')->get() as $r) {
            $readings[$r->parameter][$r->test_number] = $r;
        }
        return $readings;
    }

    public function fertilityColorClass(): string
    {
        if (is_null($this->fertility_score)) return 'secondary';
        if ($this->fertility_score >= 75) return 'success';
        if ($this->fertility_score >= 50) return 'warning';
        return 'danger';
    }
}
