<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop the old crops table (min/max range columns + the threshold additions)
     * and recreate it with the clean Low / Medium / High schema.
     *
     * Column semantics per nutrient (ph, n, p, k):
     *   *_low  — upper boundary of the Low band   (soil < *_low  → "Low")
     *   *_med  — optimal / target value            (used in fertilizer formula)
     *   *_high — lower boundary of the High band  (soil > *_high → "High")
     *   Low ≤ soil ≤ High → "Medium"
     */
    public function up(): void
    {
        Schema::dropIfExists('crops');

        Schema::create('crops', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique();
            $table->text('description')->nullable();

            // pH thresholds (0–14 scale)
            $table->decimal('ph_low',  4, 2)->nullable()->comment('Upper bound of Low pH band');
            $table->decimal('ph_med',  4, 2)->nullable()->comment('Optimal pH target');
            $table->decimal('ph_high', 4, 2)->nullable()->comment('Lower bound of High pH band');

            // Nitrogen thresholds (ppm)
            $table->decimal('n_low',  8, 2)->nullable()->comment('Upper bound of Low N band');
            $table->decimal('n_med',  8, 2)->nullable()->comment('Optimal N target');
            $table->decimal('n_high', 8, 2)->nullable()->comment('Lower bound of High N band');

            // Phosphorus thresholds (ppm)
            $table->decimal('p_low',  8, 2)->nullable()->comment('Upper bound of Low P band');
            $table->decimal('p_med',  8, 2)->nullable()->comment('Optimal P target');
            $table->decimal('p_high', 8, 2)->nullable()->comment('Lower bound of High P band');

            // Potassium thresholds (ppm)
            $table->decimal('k_low',  8, 2)->nullable()->comment('Upper bound of Low K band');
            $table->decimal('k_med',  8, 2)->nullable()->comment('Optimal K target');
            $table->decimal('k_high', 8, 2)->nullable()->comment('Lower bound of High K band');

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crops');

        // Restore original schema so rollback does not leave system broken
        Schema::create('crops', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->text('description')->nullable();
            $table->decimal('min_ph', 4, 2)->default(0.00);
            $table->decimal('max_ph', 4, 2)->default(14.00);
            $table->decimal('min_nitrogen', 6, 2)->default(0.00);
            $table->decimal('max_nitrogen', 6, 2)->default(100.00);
            $table->decimal('min_phosphorus', 6, 2)->default(0.00);
            $table->decimal('max_phosphorus', 6, 2)->default(100.00);
            $table->decimal('min_potassium', 6, 2)->default(0.00);
            $table->decimal('max_potassium', 6, 2)->default(100.00);
            $table->timestamps();
        });
    }
};
