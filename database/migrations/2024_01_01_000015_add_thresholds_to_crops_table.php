<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add Low / Medium / High threshold columns to the crops table.
     *
     * Each nutrient (ph, nitrogen, phosphorus, potassium) now has three
     * threshold values that define the boundaries between classification bands:
     *
     *   value < low_threshold             → "Low"
     *   low_threshold ≤ value ≤ high_threshold → "Medium" (optimal range)
     *   value > high_threshold            → "High"
     *
     * The medium_min / medium_max pair is kept explicit so technicians can set
     * asymmetric optimal windows (e.g. pH optimal 5.5–6.5 while low < 5.5 and
     * high > 6.5).
     */
    public function up(): void
    {
        Schema::table('crops', function (Blueprint $table) {
            // pH thresholds (0–14 scale)
            $table->decimal('ph_low_max',  4, 2)->nullable()->after('max_ph')
                  ->comment('Upper bound of Low pH band (soil pH below this = Low)');
            $table->decimal('ph_medium_min', 4, 2)->nullable()->after('ph_low_max')
                  ->comment('Lower bound of Medium (optimal) pH band');
            $table->decimal('ph_medium_max', 4, 2)->nullable()->after('ph_medium_min')
                  ->comment('Upper bound of Medium (optimal) pH band');
            $table->decimal('ph_high_min',  4, 2)->nullable()->after('ph_medium_max')
                  ->comment('Lower bound of High pH band (soil pH above this = High)');

            // Nitrogen thresholds (kg/ha)
            $table->decimal('n_low_max',    8, 2)->nullable()->after('ph_high_min')
                  ->comment('Upper bound of Low N band');
            $table->decimal('n_medium_min', 8, 2)->nullable()->after('n_low_max')
                  ->comment('Lower bound of Medium (optimal) N band');
            $table->decimal('n_medium_max', 8, 2)->nullable()->after('n_medium_min')
                  ->comment('Upper bound of Medium (optimal) N band');
            $table->decimal('n_high_min',   8, 2)->nullable()->after('n_medium_max')
                  ->comment('Lower bound of High N band');

            // Phosphorus thresholds (kg/ha)
            $table->decimal('p_low_max',    8, 2)->nullable()->after('n_high_min')
                  ->comment('Upper bound of Low P band');
            $table->decimal('p_medium_min', 8, 2)->nullable()->after('p_low_max')
                  ->comment('Lower bound of Medium (optimal) P band');
            $table->decimal('p_medium_max', 8, 2)->nullable()->after('p_medium_min')
                  ->comment('Upper bound of Medium (optimal) P band');
            $table->decimal('p_high_min',   8, 2)->nullable()->after('p_medium_max')
                  ->comment('Lower bound of High P band');

            // Potassium thresholds (kg/ha)
            $table->decimal('k_low_max',    8, 2)->nullable()->after('p_high_min')
                  ->comment('Upper bound of Low K band');
            $table->decimal('k_medium_min', 8, 2)->nullable()->after('k_low_max')
                  ->comment('Lower bound of Medium (optimal) K band');
            $table->decimal('k_medium_max', 8, 2)->nullable()->after('k_medium_min')
                  ->comment('Upper bound of Medium (optimal) K band');
            $table->decimal('k_high_min',   8, 2)->nullable()->after('k_medium_max')
                  ->comment('Lower bound of High K band');

            // Fertilizer fraction used in the client formula:
            //   current_soil / nutrient_fraction = fertilizer_amount (kg/ha)
            $table->decimal('n_fertilizer_fraction', 6, 4)->nullable()->after('k_high_min')
                  ->comment('N content fraction of applied fertilizer (e.g. 0.46 for Urea 46-0-0)');
            $table->decimal('p_fertilizer_fraction', 6, 4)->nullable()->after('n_fertilizer_fraction')
                  ->comment('P2O5 fraction of applied fertilizer (e.g. 0.20 for 0-20-0 SSP)');
            $table->decimal('k_fertilizer_fraction', 6, 4)->nullable()->after('p_fertilizer_fraction')
                  ->comment('K2O fraction of applied fertilizer (e.g. 0.60 for MOP 0-0-60)');
        });
    }

    public function down(): void
    {
        Schema::table('crops', function (Blueprint $table) {
            $table->dropColumn([
                'ph_low_max', 'ph_medium_min', 'ph_medium_max', 'ph_high_min',
                'n_low_max',  'n_medium_min',  'n_medium_max',  'n_high_min',
                'p_low_max',  'p_medium_min',  'p_medium_max',  'p_high_min',
                'k_low_max',  'k_medium_min',  'k_medium_max',  'k_high_min',
                'n_fertilizer_fraction', 'p_fertilizer_fraction', 'k_fertilizer_fraction',
            ]);
        });
    }
};
