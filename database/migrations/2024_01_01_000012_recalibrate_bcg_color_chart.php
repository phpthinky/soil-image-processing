<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Replace the original BCG (Bromocresol Green) reference colors that were
 * seeded with incorrect olive/purple hues with the properly calibrated
 * yellow-green → green → teal-green → blue gradient measured from the
 * physical BSWM BCG color card under calibrated box-lighting conditions.
 *
 * The old entries had:
 *   - Two olive-green entries at pH 4.0 (#798136, #7C843A) — wrong hue family
 *   - Bright yellow (#CCCC00) at pH 4.2 — incorrect
 *   - Teal greens at 4.4/4.8 with wrong progression
 *   - Blue-purple (#596394) at pH 5.2 — wrong hue entirely
 *
 * The corrected entries follow the actual BCG color progression:
 *   pH 4.0 → yellow-green (#CABB05)
 *   pH 4.2 → yellow-green (#C1BE07)
 *   pH 4.4 → yellow-green (#B6C209)
 *   pH 4.6 → olive-green  (#80B21B)
 *   pH 4.8 → green        (#3C9B32)
 *   pH 5.0 → teal-green   (#1A8D54)
 *   pH 5.2 → teal         (#008071)
 *   pH 5.4 → teal-blue    (#007382)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Remove all existing BCG entries (old calibration)
        DB::table('ph_color_charts')->where('indicator', 'BCG')->delete();

        // Insert the correct BCG gradient — one entry per discrete card point,
        // clean monotonic progression from yellow-green to teal-blue.
        $now = now();
        DB::table('ph_color_charts')->insert([
            ['indicator' => 'BCG', 'ph_value' => 4.0, 'hex_value' => '#CABB05', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['indicator' => 'BCG', 'ph_value' => 4.2, 'hex_value' => '#C1BE07', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['indicator' => 'BCG', 'ph_value' => 4.4, 'hex_value' => '#B6C209', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['indicator' => 'BCG', 'ph_value' => 4.6, 'hex_value' => '#80B21B', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['indicator' => 'BCG', 'ph_value' => 4.8, 'hex_value' => '#3C9B32', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['indicator' => 'BCG', 'ph_value' => 5.0, 'hex_value' => '#1A8D54', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['indicator' => 'BCG', 'ph_value' => 5.2, 'hex_value' => '#008071', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['indicator' => 'BCG', 'ph_value' => 5.4, 'hex_value' => '#007382', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        // Restore the old (incorrect) BCG entries on rollback
        DB::table('ph_color_charts')->where('indicator', 'BCG')->delete();

        $now = now();
        DB::table('ph_color_charts')->insert([
            ['indicator' => 'BCG', 'ph_value' => 4.0, 'hex_value' => '#798136', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['indicator' => 'BCG', 'ph_value' => 4.0, 'hex_value' => '#7C843A', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['indicator' => 'BCG', 'ph_value' => 4.2, 'hex_value' => '#CCCC00', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['indicator' => 'BCG', 'ph_value' => 4.4, 'hex_value' => '#47806C', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['indicator' => 'BCG', 'ph_value' => 4.4, 'hex_value' => '#417C67', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['indicator' => 'BCG', 'ph_value' => 4.6, 'hex_value' => '#22AA33', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['indicator' => 'BCG', 'ph_value' => 4.8, 'hex_value' => '#548976', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['indicator' => 'BCG', 'ph_value' => 4.8, 'hex_value' => '#457F6C', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['indicator' => 'BCG', 'ph_value' => 4.8, 'hex_value' => '#46806C', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['indicator' => 'BCG', 'ph_value' => 5.0, 'hex_value' => '#009966', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['indicator' => 'BCG', 'ph_value' => 5.2, 'hex_value' => '#596394', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['indicator' => 'BCG', 'ph_value' => 5.2, 'hex_value' => '#576292', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['indicator' => 'BCG', 'ph_value' => 5.4, 'hex_value' => '#0066BB', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
};
