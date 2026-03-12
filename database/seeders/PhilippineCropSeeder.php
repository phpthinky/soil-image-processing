<?php

namespace Database\Seeders;

use App\Models\Crop;
use Illuminate\Database\Seeder;

/**
 * Comprehensive Philippine Crop Seeder
 *
 * Covers staple crops, vegetables, root crops, fruits, legumes, grains,
 * cash crops, herbs, and spices that can be grown in the Philippines,
 * including varieties that thrive in acidic soils (pH < 6.0).
 *
 * Uses firstOrCreate on the unique `name` column — safe to run repeatedly
 * via `php artisan db:seed` without creating duplicates.
 *
 * Columns: name, description, min_ph, max_ph,
 *          min_nitrogen, max_nitrogen,   (ppm)
 *          min_phosphorus, max_phosphorus, (ppm)
 *          min_potassium, max_potassium    (ppm)
 */
class PhilippineCropSeeder extends Seeder
{
    public function run(): void
    {
        // ┌───────────────────────────────────────────────────────────────────────────────┐
        // │  FORMAT: [name, description, minPh, maxPh, minN, maxN, minP, maxP, minK, maxK]│
        // │  * marks crops that tolerate acidic soil (min_ph < 6.0)                      │
        // └───────────────────────────────────────────────────────────────────────────────┘
        $crops = [

            // ── STAPLE CEREALS ─────────────────────────────────────────────────────────
            ['Rice',
                'Major staple crop; prefers slightly acidic to neutral, waterlogged soil. Tolerates wide pH range. *Acid-tolerant.*',
                5.0, 6.5, 20.0, 60.0, 10.0, 30.0, 15.0, 40.0],

            ['Corn / Maize',
                'Second major cereal; needs well-drained, moderately fertile soil.',
                5.8, 7.0, 25.0, 70.0, 15.0, 40.0, 20.0, 50.0],

            ['Sorghum',
                'Drought-tolerant cereal; can grow in slightly acidic, low-fertility soils. *Acid-tolerant.*',
                5.5, 7.5, 15.0, 50.0, 10.0, 30.0, 15.0, 45.0],

            // ── VEGETABLES ─────────────────────────────────────────────────────────────
            ['Tomato',
                'Warm-season vegetable requiring rich, well-drained loamy soil.',
                6.0, 7.0, 30.0, 80.0, 20.0, 50.0, 25.0, 60.0],

            ['Eggplant (Talong)',
                'Thrives in warm climate with fertile, well-drained soil.',
                5.5, 6.8, 25.0, 65.0, 15.0, 40.0, 20.0, 50.0],

            ['Ampalaya (Bitter Gourd)',
                'Tropical vine; tolerates a wide pH range, needs good drainage.',
                5.5, 7.0, 20.0, 55.0, 10.0, 35.0, 15.0, 45.0],

            ['Kangkong (Water Spinach)',
                'Fast-growing leafy vegetable; tolerates slightly acidic, moist conditions.',
                5.5, 7.0, 25.0, 70.0, 10.0, 30.0, 15.0, 40.0],

            ['Pechay (Bok Choy)',
                'Cool-season leafy vegetable; prefers fertile, moist, well-drained soil.',
                6.0, 7.0, 30.0, 75.0, 20.0, 45.0, 20.0, 50.0],

            ['Sitaw (String Beans)',
                'Warm-season legume; fixes nitrogen; prefers slightly acidic to neutral soil.',
                6.0, 7.5, 10.0, 40.0, 15.0, 40.0, 20.0, 50.0],

            ['Squash (Kalabasa)',
                'High-yielding vine crop; tolerates slightly acidic, well-drained soil.',
                5.5, 7.0, 20.0, 60.0, 15.0, 35.0, 20.0, 55.0],

            ['Bottle Gourd (Upo)',
                'Fast-growing tropical gourd; prefers loamy, well-drained, moderately fertile soil.',
                6.0, 7.0, 20.0, 55.0, 15.0, 35.0, 20.0, 50.0],

            ['Patola (Luffa / Sponge Gourd)',
                'Tropical vine grown for young fruit and mature sponge; needs fertile, well-drained soil.',
                6.0, 7.0, 20.0, 55.0, 15.0, 35.0, 15.0, 45.0],

            ['Okra',
                'Warm-season vegetable; tolerates heat and slightly acidic soil; needs well-drained loam.',
                6.0, 7.5, 20.0, 55.0, 15.0, 35.0, 20.0, 50.0],

            ['Chili / Sili (Hot Pepper)',
                'Warm-season spice crop; adapts to slightly acidic, well-drained loamy soil.',
                5.5, 7.0, 25.0, 65.0, 15.0, 40.0, 20.0, 55.0],

            ['Bell Pepper (Capsicum)',
                'High-value vegetable; prefers fertile, well-drained loamy soil with moderate pH.',
                6.0, 7.5, 25.0, 65.0, 20.0, 45.0, 25.0, 55.0],

            ['Carrot',
                'Cool-season root vegetable; prefers deep, loose, slightly acidic sandy loam.',
                5.5, 7.0, 15.0, 45.0, 20.0, 50.0, 20.0, 55.0],

            ['Radish (Labanos)',
                'Fast-maturing root vegetable; prefers loose, well-drained, slightly acidic soil.',
                6.0, 7.5, 15.0, 45.0, 20.0, 45.0, 20.0, 50.0],

            ['Cabbage (Repolyo)',
                'Cool-season leafy crop grown in highland areas; needs fertile, well-drained soil.',
                6.0, 7.5, 25.0, 65.0, 20.0, 45.0, 20.0, 55.0],

            ['Lettuce',
                'Cool-season salad crop; prefers moist, fertile, slightly acidic to neutral soil.',
                6.0, 7.0, 20.0, 55.0, 20.0, 45.0, 20.0, 50.0],

            ['Mustard Greens (Mustasa)',
                'Leafy vegetable and condiment crop; adapts to a wide pH range. *Acid-tolerant.*',
                5.5, 7.5, 20.0, 55.0, 15.0, 40.0, 15.0, 45.0],

            ['Alugbati (Malabar Spinach)',
                'Tropical leafy vegetable; highly tolerant of acidic and moist soils. *Acid-tolerant.*',
                5.5, 8.0, 20.0, 55.0, 10.0, 30.0, 15.0, 40.0],

            ['Malunggay (Moringa)',
                'Nutritious multi-purpose tree; tolerates poor, slightly acidic to alkaline soils.',
                6.0, 8.0, 15.0, 45.0, 10.0, 30.0, 15.0, 40.0],

            ['Sigarilyas (Winged Bean)',
                'High-protein legume; all parts edible; tolerates acidic, low-fertility soils. *Acid-tolerant.*',
                5.0, 7.5, 10.0, 35.0, 10.0, 30.0, 15.0, 40.0],

            ['Bataw (Hyacinth Bean)',
                'Hardy legume used as food and cover crop; tolerates acidic, poor soils. *Acid-tolerant.*',
                5.0, 7.5, 10.0, 35.0, 10.0, 30.0, 15.0, 40.0],

            ['Uray / Kulitis (Amaranth)',
                'Leafy vegetable and grain crop; adaptable to acidic, marginal soils. *Acid-tolerant.*',
                5.5, 7.5, 15.0, 50.0, 10.0, 30.0, 15.0, 40.0],

            ['Celery',
                'High-value cool-season vegetable grown in highland areas; needs fertile, moist soil.',
                6.0, 7.0, 30.0, 75.0, 25.0, 55.0, 25.0, 60.0],

            ['Chinese Cabbage (Wombok)',
                'Cool-season leafy crop; prefers fertile, moist, slightly acidic to neutral soil.',
                6.0, 7.0, 25.0, 65.0, 20.0, 45.0, 20.0, 55.0],

            // ── ROOT CROPS ─────────────────────────────────────────────────────────────
            ['Sweet Potato (Camote)',
                'Tolerant of poor soils; prefers slightly acidic, well-drained sandy loam. *Acid-tolerant.*',
                5.0, 6.5, 10.0, 35.0, 10.0, 30.0, 20.0, 55.0],

            ['Cassava',
                'Drought-tolerant root crop; grows in low-fertility, acidic, well-drained soils. *Acid-tolerant.*',
                5.0, 6.5, 10.0, 30.0,  5.0, 25.0, 15.0, 45.0],

            ['Gabi (Taro)',
                'Tropical root crop; prefers moist, fertile, slightly acidic soil.',
                5.5, 7.0, 20.0, 55.0, 10.0, 30.0, 15.0, 40.0],

            ['Ube (Purple Yam)',
                'Prized root crop for its purple flesh; prefers slightly acidic, well-drained loamy soil.',
                5.5, 7.0, 15.0, 45.0, 10.0, 30.0, 15.0, 45.0],

            ['Ginger (Luya)',
                'Rhizome spice crop; prefers slightly acidic, well-drained, humus-rich loamy soil.',
                5.5, 7.5, 15.0, 45.0, 15.0, 35.0, 15.0, 45.0],

            ['Turmeric (Luyang Dilaw)',
                'Rhizome spice and medicinal crop; tolerates acidic, moist soil. *Acid-tolerant.*',
                5.0, 7.5, 15.0, 45.0, 15.0, 35.0, 15.0, 45.0],

            ['Pineapple (Pinya)',
                'Tropical fruit grown for fresh market and processing; thrives in highly acidic, sandy soil. *Highly acid-tolerant.*',
                4.5, 6.0, 10.0, 30.0,  5.0, 20.0, 15.0, 45.0],

            ['Wild Yam (Nami)',
                'Edible tuber; tolerates acidic, low-fertility soils in upland areas. *Acid-tolerant.*',
                5.0, 7.0, 10.0, 30.0,  5.0, 20.0, 10.0, 35.0],

            ['Arrowroot (Uraro)',
                'Starch root crop; prefers slightly acidic, well-drained loamy soil.',
                6.0, 7.5, 10.0, 35.0, 10.0, 30.0, 15.0, 40.0],

            // ── FRUITS ─────────────────────────────────────────────────────────────────
            ['Banana (Saging)',
                'Tropical fruit; needs deep, fertile, well-drained loam with high organic matter.',
                5.5, 7.0, 30.0, 80.0, 20.0, 50.0, 30.0, 80.0],

            ['Papaya',
                'Fast-growing tropical fruit; prefers rich, well-drained, slightly acidic soil.',
                6.0, 7.0, 25.0, 65.0, 15.0, 40.0, 20.0, 55.0],

            ['Mango',
                'Long-season tropical tree fruit; tolerates a wide pH and drought conditions.',
                5.5, 7.5, 15.0, 50.0, 10.0, 35.0, 15.0, 50.0],

            ['Coconut',
                'Multipurpose palm; tolerates a wide range of soils; prefers sandy loam.',
                5.5, 8.0, 15.0, 45.0, 10.0, 30.0, 20.0, 60.0],

            ['Jackfruit (Langka)',
                'Large tropical tree fruit; tolerates slightly acidic, deep, well-drained loamy soil.',
                5.5, 7.5, 15.0, 45.0, 10.0, 30.0, 15.0, 45.0],

            ['Rambutan',
                'Sweet tropical fruit; prefers deep, fertile, slightly acidic, well-drained soil. *Acid-tolerant.*',
                4.5, 6.5, 15.0, 45.0, 10.0, 30.0, 15.0, 45.0],

            ['Lanzones (Langsat)',
                'Shade-tolerant tropical fruit; prefers acidic, moist, fertile, well-drained soil.',
                5.5, 7.0, 15.0, 45.0, 10.0, 30.0, 15.0, 45.0],

            ['Durian',
                'King of fruits; prefers deep, fertile, slightly acidic, well-drained clay loam. *Acid-tolerant.*',
                5.0, 7.0, 20.0, 55.0, 15.0, 35.0, 20.0, 55.0],

            ['Mangosteen',
                'Slow-growing tropical fruit; thrives in acidic, deep, humus-rich soil. *Acid-tolerant.*',
                5.0, 7.0, 15.0, 45.0, 10.0, 30.0, 15.0, 45.0],

            ['Marang',
                'Borneo/Mindanao tropical fruit; prefers slightly acidic, fertile, moist soil. *Acid-tolerant.*',
                5.0, 7.0, 15.0, 45.0, 10.0, 30.0, 15.0, 45.0],

            ['Calamansi (Philippine Lime)',
                'Important citrus; prefers slightly acidic, fertile, well-drained loamy soil.',
                5.5, 7.0, 15.0, 45.0, 10.0, 30.0, 15.0, 50.0],

            ['Dalandan (Philippine Orange)',
                'Local orange variety; prefers slightly acidic, well-drained, fertile loamy soil.',
                5.5, 7.5, 15.0, 45.0, 10.0, 30.0, 15.0, 50.0],

            ['Pummelo / Suha',
                'Largest citrus; prefers slightly acidic, deep, well-drained soil.',
                5.5, 7.5, 15.0, 45.0, 10.0, 30.0, 15.0, 50.0],

            ['Guava (Bayabas)',
                'Hardy tropical fruit; grows well in acidic, low-fertility soils. *Acid-tolerant.*',
                4.5, 7.0, 10.0, 35.0,  5.0, 25.0, 10.0, 40.0],

            ['Avocado',
                'Subtropical/tropical fruit; prefers well-drained, fertile, slightly acidic loamy soil.',
                6.0, 7.0, 15.0, 45.0, 10.0, 30.0, 15.0, 45.0],

            ['Watermelon (Pakwan)',
                'Warm-season vining fruit; needs well-drained, sandy loam with moderate pH.',
                6.0, 7.0, 15.0, 45.0, 10.0, 30.0, 20.0, 55.0],

            ['Melon (Melon / Honeydew)',
                'Warm-season fruit; prefers fertile, well-drained, slightly alkaline to neutral soil.',
                6.0, 7.5, 15.0, 45.0, 10.0, 30.0, 20.0, 55.0],

            ['Guyabano (Soursop)',
                'Tropical fruit with medicinal value; prefers slightly acidic, well-drained fertile soil.',
                5.5, 7.5, 15.0, 45.0, 10.0, 30.0, 15.0, 45.0],

            ['Dragon Fruit (Pitaya)',
                'Cactus fruit; tolerates a wide pH range; needs well-drained, sandy-loam soil.',
                6.0, 7.5, 10.0, 35.0,  5.0, 25.0, 15.0, 45.0],

            ['Passion Fruit (Maracuya)',
                'Fast-growing vine fruit; adapts to slightly acidic, well-drained, fertile soil.',
                6.0, 8.0, 15.0, 45.0, 10.0, 30.0, 15.0, 50.0],

            ['Santol',
                'Tropical fruit tree; adapts to slightly acidic, well-drained lowland soils.',
                5.5, 7.5, 10.0, 35.0,  5.0, 25.0, 10.0, 40.0],

            ['Starfruit (Balimbing)',
                'Tropical fruit; prefers slightly acidic, well-drained loamy soil.',
                5.5, 7.5, 10.0, 35.0,  5.0, 25.0, 10.0, 40.0],

            ['Atis (Sugar Apple)',
                'Tropical fruit; prefers warm climate with slightly acidic, well-drained soil.',
                6.0, 8.0, 10.0, 35.0,  5.0, 25.0, 10.0, 40.0],

            ['Sineguelas (Spanish Plum)',
                'Small tropical fruit; tolerates slightly acidic, well-drained, low-fertility soil.',
                5.5, 7.5, 10.0, 35.0,  5.0, 25.0, 10.0, 40.0],

            ['Lemon / Dayap (Citrus limon)',
                'Sour citrus used for juice and cooking; prefers slightly acidic, fertile, well-drained soil.',
                5.5, 7.5, 15.0, 45.0, 10.0, 30.0, 15.0, 50.0],

            ['Breadfruit (Rimas)',
                'Starchy tropical fruit tree; tolerates slightly acidic, well-drained lowland soils.',
                6.0, 7.5, 10.0, 35.0,  5.0, 25.0, 10.0, 40.0],

            // ── LEGUMES ────────────────────────────────────────────────────────────────
            ['Peanut (Mani)',
                'Legume; fixes nitrogen; needs light, well-drained, slightly acidic sandy loam.',
                5.8, 7.0, 10.0, 35.0, 15.0, 40.0, 15.0, 45.0],

            ['Mung Bean (Monggo)',
                'Short-duration legume; fixes nitrogen; tolerates slightly acidic, well-drained soil.',
                6.0, 7.5, 10.0, 35.0, 15.0, 35.0, 15.0, 45.0],

            ['Soybean',
                'High-protein legume; fixes nitrogen; prefers slightly acidic to neutral, well-drained soil.',
                6.0, 7.0, 10.0, 35.0, 15.0, 40.0, 15.0, 45.0],

            ['Cowpea (Paayap)',
                'Drought-tolerant legume; tolerates acidic, low-fertility soils. *Acid-tolerant.*',
                5.5, 7.5, 10.0, 35.0, 10.0, 30.0, 15.0, 40.0],

            ['Pigeon Pea (Kadyos)',
                'Deep-rooted perennial legume; well adapted to acidic, low-fertility soils. *Acid-tolerant.*',
                5.0, 7.0, 10.0, 35.0, 10.0, 30.0, 15.0, 40.0],

            ['Snap Pea',
                'Cool-season legume grown in highland areas; prefers slightly acidic, well-drained soil.',
                6.0, 7.5, 10.0, 35.0, 15.0, 40.0, 15.0, 45.0],

            // ── CASH CROPS ─────────────────────────────────────────────────────────────
            ['Sugarcane',
                'High-input cash crop; needs fertile, well-drained loamy soil.',
                6.0, 7.5, 30.0, 80.0, 20.0, 55.0, 25.0, 70.0],

            ['Coffee (Arabica / Robusta)',
                'Shade-grown tropical crop; needs well-drained, fertile, acidic volcanic soil. *Acid-tolerant.*',
                5.0, 6.5, 20.0, 55.0, 10.0, 30.0, 15.0, 45.0],

            ['Cacao (Cocoa)',
                'Shade-grown tropical tree crop; thrives in acidic, deep, well-drained loamy soil. *Acid-tolerant.*',
                5.0, 7.5, 20.0, 55.0, 10.0, 30.0, 15.0, 50.0],

            ['Abaca (Manila Hemp)',
                'Fiber cash crop; tolerates acidic, fertile, well-drained volcanic soil. *Acid-tolerant.*',
                5.0, 7.0, 20.0, 55.0, 10.0, 30.0, 20.0, 55.0],

            ['Rubber (Hevea brasiliensis)',
                'Latex-producing tree; thrives in acidic, deep, well-drained soil. *Highly acid-tolerant.*',
                4.5, 7.0, 15.0, 45.0,  5.0, 25.0, 15.0, 45.0],

            ['Oil Palm',
                'High-yield oil crop; grows in acidic, deep, well-drained mineral soils. *Acid-tolerant.*',
                4.0, 7.0, 20.0, 55.0, 10.0, 30.0, 20.0, 60.0],

            ['Tobacco',
                'Cash crop grown in Ilocos and Cagayan; needs slightly acidic, light, well-drained sandy loam.',
                5.5, 7.5, 20.0, 55.0, 15.0, 40.0, 20.0, 55.0],

            ['Pili Nut',
                'Endemic Philippine tree nut; prefers slightly acidic, well-drained volcanic loam.',
                5.5, 7.0, 10.0, 35.0,  5.0, 25.0, 15.0, 45.0],

            ['Cashew',
                'Tropical nut crop; grows in acidic, sandy, well-drained soils. *Acid-tolerant.*',
                5.0, 7.5, 10.0, 35.0,  5.0, 25.0, 10.0, 40.0],

            ['Bamboo (Kawayan)',
                'Multi-purpose grass; highly tolerant of acidic, poor soils. *Acid-tolerant.*',
                4.5, 7.0, 15.0, 45.0,  5.0, 25.0, 10.0, 40.0],

            // ── HERBS & SPICES ─────────────────────────────────────────────────────────
            ['Garlic (Bawang)',
                'Important bulb crop; prefers slightly acidic to slightly alkaline, well-drained soil.',
                5.5, 8.0, 20.0, 55.0, 20.0, 45.0, 15.0, 50.0],

            ['Onion (Sibuyas)',
                'Important bulb vegetable; prefers slightly acidic to neutral, fertile, well-drained soil.',
                6.0, 7.5, 20.0, 55.0, 20.0, 50.0, 15.0, 50.0],

            ['Lemongrass (Tanglad)',
                'Aromatic grass used for tea and cooking; highly adaptable; tolerates acidic, poor soil. *Acid-tolerant.*',
                5.0, 8.5, 10.0, 35.0,  5.0, 20.0, 10.0, 35.0],

            ['Pandan (Screwpine)',
                'Aromatic leaf crop; tolerates slightly acidic, moist, fertile soil.',
                5.5, 7.0, 10.0, 35.0,  5.0, 20.0, 10.0, 35.0],

            ['Basil (Balanoy)',
                'Aromatic herb; prefers slightly acidic, fertile, well-drained soil.',
                6.0, 7.5, 20.0, 55.0, 15.0, 35.0, 15.0, 40.0],

            ['Oregano (Oregano Blanco)',
                'Medicinal and culinary herb; tolerates a wide pH range, needs well-drained soil.',
                6.0, 8.0, 15.0, 45.0, 10.0, 30.0, 10.0, 35.0],

            ['Mint (Yerba Buena)',
                'Medicinal herb; prefers moist, slightly acidic, fertile soil.',
                6.0, 7.5, 15.0, 45.0, 10.0, 30.0, 10.0, 35.0],

            ['Sabila (Aloe Vera)',
                'Succulent medicinal plant; tolerates slightly acidic, sandy, well-drained soil.',
                6.0, 8.0,  5.0, 25.0,  5.0, 20.0,  5.0, 25.0],

            ['Luya-luyahan (Galangal)',
                'Ginger-family spice; prefers slightly acidic, moist, humus-rich soil. *Acid-tolerant.*',
                5.5, 7.0, 15.0, 40.0, 10.0, 30.0, 15.0, 40.0],

        ];

        foreach ($crops as [$name, $desc, $minPh, $maxPh, $minN, $maxN, $minP, $maxP, $minK, $maxK]) {
            Crop::firstOrCreate(
                ['name' => $name],
                [
                    'description'    => $desc,
                    'min_ph'         => $minPh,  'max_ph'         => $maxPh,
                    'min_nitrogen'   => $minN,   'max_nitrogen'   => $maxN,
                    'min_phosphorus' => $minP,   'max_phosphorus' => $maxP,
                    'min_potassium'  => $minK,   'max_potassium'  => $maxK,
                ]
            );
        }

        $this->command->info('PhilippineCropSeeder: ' . count($crops) . ' crops processed (duplicates skipped).');
    }
}
