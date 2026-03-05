<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhTest extends Model
{
    protected $fillable = [
        'sample_id',
        'step1_readings', 'step1_ph', 'step1_variance', 'step1_confidence',
        'step1_outcome', 'step1_remarks',
        'next_solution',
        'step2_solution', 'step2_readings', 'step2_ph', 'step2_variance', 'step2_confidence',
        'step2_outcome', 'step2_remarks',
        'technician_notes',
        'final_ph',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'step1_readings' => 'array',
            'step2_readings' => 'array',
            'step1_ph'       => 'float',
            'step2_ph'       => 'float',
            'final_ph'       => 'float',
            'step1_variance' => 'float',
            'step2_variance' => 'float',
        ];
    }

    public function sample(): BelongsTo
    {
        return $this->belongsTo(SoilSample::class);
    }

    public function step1Count(): int
    {
        return count($this->step1_readings ?? []);
    }

    public function step2Count(): int
    {
        return count($this->step2_readings ?? []);
    }

    public function step1Complete(): bool
    {
        return $this->step1Count() >= 3;
    }

    public function step2Complete(): bool
    {
        return $this->step2Count() >= 3;
    }

    public function solutionLabel(): string
    {
        return match($this->next_solution) {
            'BCG'    => 'BCG (Bromocresol Green) — acidic range ≤ 5.4',
            'BTB'    => 'BTB (Bromothymol Blue) — near-neutral range > 5.8',
            'CPR'    => 'CPR Result is Final — transitional range 5.4–5.8',
            'RETEST' => 'Retest Required — pH outside measurable range',
            default  => 'Pending',
        };
    }
}
