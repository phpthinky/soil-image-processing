<?php

namespace Database\Seeders;

use App\Models\Crop;
use Illuminate\Database\Seeder;

class CropSeeder extends Seeder
{
    public function run(): void
    {
        $crops = [
            ['Rice',               'Major staple crop; prefers slightly acidic to neutral, waterlogged soil.',         5.0, 6.5, 20.0, 60.0, 10.0, 30.0, 15.0, 40.0],
            ['Corn / Maize',       'Second major cereal; needs well-drained, moderately fertile soil.',                5.8, 7.0, 25.0, 70.0, 15.0, 40.0, 20.0, 50.0],
            ['Tomato',             'Warm-season vegetable requiring rich, well-drained loamy soil.',                   6.0, 7.0, 30.0, 80.0, 20.0, 50.0, 25.0, 60.0],
            ['Eggplant (Talong)',  'Thrives in warm climate with fertile, well-drained soil.',                         5.5, 6.8, 25.0, 65.0, 15.0, 40.0, 20.0, 50.0],
            ['Ampalaya (Bitter Gourd)', 'Tropical vine; tolerates a wide pH range, needs good drainage.',              5.5, 7.0, 20.0, 55.0, 10.0, 35.0, 15.0, 45.0],
            ['Kangkong (Water Spinach)', 'Fast-growing leafy vegetable; tolerates slightly acidic, moist conditions.', 5.5, 7.0, 25.0, 70.0, 10.0, 30.0, 15.0, 40.0],
            ['Pechay (Bok Choy)',  'Cool-season leafy vegetable; prefers fertile, moist, well-drained soil.',          6.0, 7.0, 30.0, 75.0, 20.0, 45.0, 20.0, 50.0],
            ['Sitaw (String Beans)', 'Warm-season legume; fixes nitrogen; prefers slightly acidic to neutral soil.',   6.0, 7.5, 10.0, 40.0, 15.0, 40.0, 20.0, 50.0],
            ['Sweet Potato (Camote)', 'Tolerant of poor soils; prefers slightly acidic, well-drained sandy loam.',    5.0, 6.5, 10.0, 35.0, 10.0, 30.0, 20.0, 55.0],
            ['Cassava',            'Drought-tolerant root crop; grows in low-fertility, well-drained soils.',          5.0, 6.5, 10.0, 30.0,  5.0, 25.0, 15.0, 45.0],
            ['Gabi (Taro)',        'Tropical root crop; prefers moist, fertile, slightly acidic soil.',                5.5, 7.0, 20.0, 55.0, 10.0, 30.0, 15.0, 40.0],
            ['Banana (Saging)',    'Tropical fruit; needs deep, fertile, well-drained loam with high organic matter.', 5.5, 7.0, 30.0, 80.0, 20.0, 50.0, 30.0, 80.0],
            ['Papaya',             'Fast-growing tropical fruit; prefers rich, well-drained, slightly acidic soil.',   6.0, 7.0, 25.0, 65.0, 15.0, 40.0, 20.0, 55.0],
            ['Mango',              'Long-season tropical tree fruit; tolerates a wide pH and drought conditions.',      5.5, 7.5, 15.0, 50.0, 10.0, 35.0, 15.0, 50.0],
            ['Sugarcane',          'High-input cash crop; needs fertile, well-drained loamy soil.',                    6.0, 7.5, 30.0, 80.0, 20.0, 55.0, 25.0, 70.0],
            ['Peanut (Mani)',      'Legume; fixes nitrogen; needs light, well-drained, slightly acidic sandy loam.',   5.8, 7.0, 10.0, 35.0, 15.0, 40.0, 15.0, 45.0],
            ['Coffee (Arabica/Robusta)', 'Shade-grown tropical crop; needs well-drained, fertile, acidic volcanic soil.', 5.0, 6.5, 20.0, 55.0, 10.0, 30.0, 15.0, 45.0],
            ['Coconut',            'Multipurpose palm; tolerates a wide range of soils; prefers sandy loam.',          5.5, 8.0, 15.0, 45.0, 10.0, 30.0, 20.0, 60.0],
        ];

        foreach ($crops as [$name, $desc, $minPh, $maxPh, $minN, $maxN, $minP, $maxP, $minK, $maxK]) {
            Crop::firstOrCreate(['name' => $name], [
                'description'    => $desc,
                'min_ph'         => $minPh, 'max_ph'         => $maxPh,
                'min_nitrogen'   => $minN,  'max_nitrogen'   => $maxN,
                'min_phosphorus' => $minP,  'max_phosphorus' => $maxP,
                'min_potassium'  => $minK,  'max_potassium'  => $maxK,
            ]);
        }
    }
}
