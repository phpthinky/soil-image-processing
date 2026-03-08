<?php

namespace Database\Seeders;

use App\Models\PhColorChart;
use Illuminate\Database\Seeder;

class PhColorChartSeeder extends Seeder
{
    /**
     * Seeds the ph_color_charts table with the original BSWM reference colors.
     * Values are taken from the hardcoded constants that were previously in
     * ColorScienceService — manually measured from physical BSWM kit cards
     * under calibrated box-lighting conditions.
     */
    public function run(): void
    {
        // CPR (Cresol Red + Phenolphthalein) — Step 1, pH 4.8–6.0
        $cpr = [
            ['#FF8800', 4.8],
            ['#D2A65A', 5.0],
            ['#FFC800', 5.2],
            ['#B0622D', 5.4],
            ['#B0612C', 5.4],
            ['#EDE800', 5.6],
            ['#9D2529', 5.8],
            ['#A12D31', 5.8],
            ['#7E2938', 6.0],
            ['#7E2939', 6.0],
        ];

        // BCG (Bromocresol Green) — Step 2 acidic, pH 4.0–5.4
        $bcg = [
            ['#CABB05', 4.0],
            ['#C1BE07', 4.2],
            ['#B6C209', 4.4],
            ['#80B21B', 4.6],
            ['#3C9B32', 4.8],
            ['#1A8D54', 5.0],
            ['#008071', 5.2],
            ['#007382', 5.4],
        ];

        // BTB (Bromothymol Blue) — Step 2 near-neutral, pH 6.0–7.6
        // NOTE: Approximate reference values — no physical BTB card calibration
        // performed yet. Recalibrate by measuring BTB card strips under the same
        // box+lighting conditions used for CPR/BCG captures.
        $btb = [
            ['#DDDD00', 6.0],
            ['#DCDC00', 6.0],
            ['#88BB00', 6.4],
            ['#33AA44', 6.8],
            ['#009977', 7.2],
            ['#0066CC', 7.6],
            ['#0055BB', 7.6],
        ];

        $rows = [];

        foreach ($cpr as [$hex, $ph]) {
            $rows[] = ['indicator' => 'CPR', 'ph_value' => $ph, 'hex_value' => $hex, 'active' => true];
        }
        foreach ($bcg as [$hex, $ph]) {
            $rows[] = ['indicator' => 'BCG', 'ph_value' => $ph, 'hex_value' => $hex, 'active' => true];
        }
        foreach ($btb as [$hex, $ph]) {
            $rows[] = ['indicator' => 'BTB', 'ph_value' => $ph, 'hex_value' => $hex, 'active' => true];
        }

        // Add timestamps to each row
        $now = now();
        foreach ($rows as &$row) {
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
        }

        PhColorChart::insert($rows);
    }
}
