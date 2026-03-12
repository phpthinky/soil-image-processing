<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Replace the original BTB (Bromothymol Blue) reference colors with the
 * newly calibrated values measured from the physical BSWM BTB color card.
 *
 * Changes vs. the previous approximations:
 *   - Range extended from 6.0–7.6 to 6.0–7.8 (6 points instead of 5)
 *   - New intermediate point added at pH 6.2
 *   - All hex values updated to actual card measurements
 *   - Correct monotonic progression: yellow-green → green → dark green → teal → blue
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('ph_color_charts')->where('indicator', 'BTB')->delete();

        $now = now();
        DB::table('ph_color_charts')->insert([
            ['indicator' => 'BTB', 'ph_value' => 6.0, 'hex_value' => '#C9D900', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['indicator' => 'BTB', 'ph_value' => 6.2, 'hex_value' => '#0FCA02', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['indicator' => 'BTB', 'ph_value' => 6.4, 'hex_value' => '#027419', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['indicator' => 'BTB', 'ph_value' => 6.8, 'hex_value' => '#022706', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['indicator' => 'BTB', 'ph_value' => 7.2, 'hex_value' => '#013251', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['indicator' => 'BTB', 'ph_value' => 7.8, 'hex_value' => '#1F0F99', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        DB::table('ph_color_charts')->where('indicator', 'BTB')->delete();

        $now = now();
        DB::table('ph_color_charts')->insert([
            ['indicator' => 'BTB', 'ph_value' => 6.0, 'hex_value' => '#DDDD00', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['indicator' => 'BTB', 'ph_value' => 6.0, 'hex_value' => '#DCDC00', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['indicator' => 'BTB', 'ph_value' => 6.4, 'hex_value' => '#88BB00', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['indicator' => 'BTB', 'ph_value' => 6.8, 'hex_value' => '#33AA44', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['indicator' => 'BTB', 'ph_value' => 7.2, 'hex_value' => '#009977', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['indicator' => 'BTB', 'ph_value' => 7.6, 'hex_value' => '#0066CC', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['indicator' => 'BTB', 'ph_value' => 7.6, 'hex_value' => '#0055BB', 'active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
};
