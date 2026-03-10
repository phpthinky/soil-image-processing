<?php

namespace Database\Seeders;

use App\Models\NpkColorChart;
use Illuminate\Database\Seeder;

class NpkColorChartSeeder extends Seeder
{
    /**
     * Seeds npk_color_charts with placeholder values from the old hardcoded constants.
     *
     * ⚠️  ALL ENTRIES SEEDED HERE ARE UNCALIBRATED PLACEHOLDERS.
     *     The original constants used wrong colors (pink/magenta for N, light-blue for P,
     *     grey scale for K) that do not match the physical BSWM card.
     *     Entries are seeded as active=false so the system falls back to the hardcoded
     *     constants until a proper physical calibration is performed.
     *
     * To calibrate:
     *   1. Measure hex values from physical BSWM card under production lighting box.
     *   2. Add calibrated entries via Admin → NPK Color Charts with active=true.
     *   3. Delete or leave these placeholder entries deactivated.
     *
     * Confirmed correct ppm ranges for Nitrogen (from BSWM card):
     *   LOW    15–45 ppm   (orange → dark brown)
     *   MEDIUM 60–150 ppm  (green → teal green)
     *   HIGH   160–240 ppm (blue-green → teal blue)
     *
     * Phosphorus and Potassium ranges to be confirmed from physical card.
     */
    public function run(): void
    {
        $now = now();

        // ── Nitrogen (N) ─────────────────────────────────────────────────────
        // Placeholder category assignments based on confirmed ppm ranges.
        // Hex colors are WRONG — placeholders only.
        $nitrogen = [
            ['#FFF5F5',  2.0, 'low'],
            ['#FFE0E8',  8.0, 'low'],
            ['#FFB3C6', 15.0, 'low'],
            ['#FF80A0', 22.0, 'low'],
            ['#FF4D80', 30.0, 'low'],
            ['#E6006B', 40.0, 'low'],
            ['#CC0066', 50.0, 'medium'],
            ['#990066', 60.0, 'medium'],
            ['#660066', 70.0, 'medium'],
            ['#440044', 80.0, 'medium'],
        ];

        // ── Phosphorus (P) ───────────────────────────────────────────────────
        // Ranges and categories to be confirmed from physical card.
        $phosphorus = [
            ['#FEFEFE',  1.0, 'low'],
            ['#EEF8FF',  3.0, 'low'],
            ['#D4EEFF',  5.0, 'low'],
            ['#A8D8F0',  8.0, 'low'],
            ['#70BAE8', 12.0, 'medium'],
            ['#42A5F5', 18.0, 'medium'],
            ['#1E88E5', 25.0, 'medium'],
            ['#1565C0', 35.0, 'high'],
            ['#0D47A1', 45.0, 'high'],
            ['#062A70', 55.0, 'high'],
        ];

        // ── Potassium (K) ────────────────────────────────────────────────────
        // Ranges and categories to be confirmed from physical card.
        $potassium = [
            ['#0A0A0A',   5.0, 'low'],
            ['#2A2A2A',  15.0, 'low'],
            ['#555555',  25.0, 'low'],
            ['#808080',  40.0, 'medium'],
            ['#AAAAAA',  60.0, 'medium'],
            ['#C8C8C8',  80.0, 'medium'],
            ['#DEDEDE',  95.0, 'high'],
            ['#F0F0F0', 110.0, 'high'],
            ['#FAFAFA', 120.0, 'high'],
        ];

        $rows = [];

        foreach ($nitrogen as [$hex, $ppm, $cat]) {
            $rows[] = [
                'nutrient'   => 'N',
                'hex_value'  => $hex,
                'ppm_value'  => $ppm,
                'category'   => $cat,
                'active'     => false, // ⚠️ not calibrated
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach ($phosphorus as [$hex, $ppm, $cat]) {
            $rows[] = [
                'nutrient'   => 'P',
                'hex_value'  => $hex,
                'ppm_value'  => $ppm,
                'category'   => $cat,
                'active'     => false, // ⚠️ not calibrated
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach ($potassium as [$hex, $ppm, $cat]) {
            $rows[] = [
                'nutrient'   => 'K',
                'hex_value'  => $hex,
                'ppm_value'  => $ppm,
                'category'   => $cat,
                'active'     => false, // ⚠️ not calibrated
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        NpkColorChart::insert($rows);
    }
}
