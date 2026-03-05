# Soil Fertility Analyzer v2 — OMA (Office of the Municipal Agriculturist)

**Laravel 11 rewrite** of the original PHP/MySQL application.
Webcam-based colorimetric soil testing using BSWM (Bureau of Soils and Water Management) protocol.

---

## Table of Contents

1. [What's New in v2](#whats-new-in-v2)
2. [System Overview](#system-overview)
3. [Technology Stack](#technology-stack)
4. [Setup & Installation](#setup--installation)
5. [User Roles & Access](#user-roles--access)
6. [Testing Workflow](#testing-workflow)
   - [pH Test (2-Step)](#ph-test-2-step)
   - [Nitrogen / Phosphorus / Potassium Tests](#nitrogen--phosphorus--potassium-tests)
7. [Database Schema](#database-schema)
8. [Application Architecture](#application-architecture)
9. [Color Science Pipeline](#color-science-pipeline)
10. [Fertilizer Recommendation Engine](#fertilizer-recommendation-engine)
11. [Crop-Specific Calculator](#crop-specific-calculator)
12. [AI Agronomic Advisor](#ai-agronomic-advisor)
13. [Farmer Management & Phase 2](#farmer-management--phase-2)
14. [Export System](#export-system)
15. [Calibration Guide](#calibration-guide)
16. [Key Reference Numbers](#key-reference-numbers)

---

## What's New in v2

| Feature | v1 (raw PHP) | v2 (Laravel 11) |
|---------|-------------|-----------------|
| Framework | Plain PHP files | Laravel 11 MVC |
| Authentication | Plain-text passwords | Bcrypt hashing, Gate policies |
| pH Testing | Single-step webcam capture | **2-Step CPR → BCG/BTB protocol** (BSWM) |
| N/P/K Testing | Tabbed Test 1/2/3 interface | **Individual dedicated pages** per parameter |
| Technician Remarks | None | Auto-generated win/retest outcome per step |
| Farmer Management | Name/address only in sample form | Dedicated Farmers table with CRUD + CSV import |
| Phase 2 (Arduino) | Not supported | `farm_id` field + Phase 2 export endpoint |
| Fertilizer Calculator | Fixed BSWM per-hectare table | Crop-specific calculator from DB, area-adjusted |
| Crop Database | Static seed | Editable via seeder, powers calculator dropdown |
| AI Recommendations | Available | Available — gracefully disabled if API key absent |
| Layout | Bootstrap 4, no sidebar | Bootstrap 5 CDN, green OMA sidebar |
| CSRF | None | Laravel CSRF on all forms |

---

## System Overview

Agricultural extension workers at the Municipal Agriculture Office collect soil samples from farmers and run colorimetric tests using the **BSWM Soil Test Kit**. The kit uses chemical reagents that produce distinct color reactions in test tubes for four soil parameters:

| Parameter | Reagent | Color Reaction |
|-----------|---------|----------------|
| Soil pH | CPR → BCG or BTB | Red-orange → green → blue |
| Nitrogen (N) | N-Reagent | Colorless → pink → purple |
| Phosphorus (P) | P-Reagent | Colorless → deep blue |
| Potassium (K) | K-Reagent | Clear → turbid white/gray |

The system replaces visual chart-matching with a **webcam color capture pipeline**: the worker inserts the test tube into a capture box, takes 3 captures, and the system computes the nutrient value using CIE L\*a\*b\* color science and CIEDE2000 color distance.

---

## Technology Stack

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 11 |
| PHP | 8.2+ |
| Database | MySQL / MariaDB |
| Frontend | Bootstrap 5 (CDN), Font Awesome 6.5 (CDN) |
| Color Science | CIE L\*a\*b\*, CIEDE2000 (ISO 11664-6) |
| AI | Anthropic Claude API (`claude-haiku-4-5-20251001`) |
| Auth | Laravel Auth + Gate policies |
| Sessions | Database driver |

> **Note:** No Node.js / npm required. All assets are loaded from CDN. No `vendor/` directory is checked into git — run `composer install` after cloning.

---

## Setup & Installation

### Requirements
- PHP 8.2+
- Composer
- MySQL / MariaDB 10.4+
- Web server (Apache/Nginx) or `php artisan serve`
- Webcam accessible via browser (HTTPS or `localhost`)

### Steps

```bash
# 1. Clone the repository
git clone <repo-url>
cd soil-image-processing

# 2. Install PHP dependencies
composer install

# 3. Copy environment file and configure
cp .env.example .env
php artisan key:generate

# 4. Set database credentials in .env
DB_DATABASE=soil_analyzer
DB_USERNAME=root
DB_PASSWORD=

# 5. (Optional) Set Anthropic API key for AI features
ANTHROPIC_API_KEY=sk-ant-your-key-here

# 6. Run migrations
php artisan migrate

# 7. Seed the database (crops, default admin user)
php artisan db:seed

# 8. Create session table (database driver)
php artisan session:table
php artisan migrate

# 9. Start the server
php artisan serve
```

Open `http://localhost:8000` in Chrome or Firefox.

### Default Login

| Username | Password | Role |
|----------|----------|------|
| `admin` | `admin123` | Administrator |

> Change this immediately after first login via **Admin → Users**.

---

## User Roles & Access

Three user types: `farmer`, `professional`, `admin`.

| Feature | farmer / professional | admin |
|---------|-----------------------|-------|
| Create soil samples | Own only | All users |
| View samples | Own only | All users |
| Manage farmers | Own only | All users |
| Export CSV | Own samples | All samples |
| Phase 2 export | ✗ | ✓ |
| User management | ✗ | ✓ |
| Admin dashboard | ✗ | ✓ |

Access is enforced by `Gate::define('admin', fn($user) => $user->isAdmin())` in `AppServiceProvider` and checked with `can:admin` middleware on admin routes.

---

## Testing Workflow

### Overall Flow

```
Register Sample → pH Test (2-step) → N Test → P Test → K Test → Compute Results
```

All 4 parameters must have 3 captures each (12 total) before the system computes the final analysis.

---

### pH Test (2-Step)

Route: `GET /samples/{id}/ph-test`

The pH test follows the official BSWM 2-step confirmation protocol:

#### Step 1 — CPR (Cresol Red Purple)

1. Transfer ~0.5 g soil to test tube (1st scratch mark)
2. Fill with CPR reagent to 2nd scratch mark (~1 mL)
3. Mix by tapping into palm for **1 minute**
4. Let stand **2 minutes**, then mix again for 1 minute
5. Let stand **5 minutes**
6. Insert test tube into image capturing box
7. Take **3 captures** (system averages them)

#### Decision Logic (BSWM Protocol)

| CPR pH Reading | Next Action | Outcome |
|---------------|-------------|---------|
| pH ≤ 5.4 | Proceed with **BCG** (Bromocresol Green) | `win-bcg` |
| pH > 5.8 | Proceed with **BTB** (Bromothymol Blue) | `win-btb` |
| 5.4 < pH ≤ 5.8 | **CPR result is final** — no Step 2 needed | `win-cpr` |
| pH < 4.0 or > 7.6 | Outside measurable range — **Retest** | `retest` / `high-acid` / `alkaline` |

#### Step 2 — BCG or BTB Confirmation

Same 7-step protocol as CPR but using BCG or BTB reagent. After 3 captures:

| Step 2 Result | Outcome Key | Meaning |
|--------------|------------|---------|
| pH in expected range, close to pH1 | `confirmed` | Win — reliable reading |
| pH in range but differs from pH1 by > 0.5 | `borderline` | Acceptable, note variability |
| pH outside expected range for the solution | `inconsistent` | Review results |

#### Technician Remarks

Auto-generated remarks are saved per step. They include:
- The outcome label (Win / Retest / Borderline / Inconsistent)
- A plain-language explanation
- A confidence note (High / Low based on variance)

Remarks are visible on the pH test page and in the Test Outcome Summary panel.

---

### Nitrogen / Phosphorus / Potassium Tests

Routes:
- `GET /samples/{id}/test/nitrogen`
- `GET /samples/{id}/test/phosphorus`
- `GET /samples/{id}/test/potassium`

Each parameter has its own dedicated page with the same 7-step BSWM protocol. The page includes:

- **8-minute reaction timer** (mix 1 min → wait 2 min → mix 1 min → wait 5 min)
- **Webcam feed** with center crosshair guide
- **3-capture table** — sequential locking (Capture 2 unlocks after Capture 1)
- **Average color swatch** displayed once all 3 are done
- **Prev/Next navigation** between parameters (N → P → K)
- **Parameter progress pills** at the top showing completion status for all 4

#### Important: Potassium Turbidity

Potassium uses a **turbidity reaction** (not a color change). The test tube should be photographed against a **black background** in the capture box so that cloudiness registers correctly.

---

## Database Schema

### `users`
| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT PK | Auto-increment |
| username | VARCHAR(50) UNIQUE | Login name |
| email | VARCHAR(100) UNIQUE | |
| password | VARCHAR(255) | Bcrypt hashed |
| user_type | ENUM | `farmer`, `professional`, `admin` |

### `farmers`
| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT PK | |
| user_id | FK → users | Owner |
| name | VARCHAR(150) | Farmer's full name |
| address | VARCHAR(255) | |
| farm_location | VARCHAR(200) | nullable |
| farm_id | VARCHAR(100) | nullable — Phase 2 Arduino record ID |

### `soil_samples`
| Column | Notes |
|--------|-------|
| user_id | FK → users |
| farmer_id | FK → farmers (nullable) |
| sample_name, farmer_name, address, location | Sample metadata |
| sample_date, date_tested | Date received / date tested (date_tested ≥ sample_date enforced) |
| ph_color_hex, nitrogen_color_hex, phosphorus_color_hex, potassium_color_hex | Averaged hex written after 3rd capture per parameter |
| ph_level, nitrogen_level, phosphorus_level, potassium_level | Final computed nutrient values |
| fertility_score | 0–100 weighted score |
| ai_recommendation | Stored Claude API response |
| recommended_crop | Top crop from matching query |
| tests_completed | 0–12 progress counter |
| analyzed_at | Timestamp set on first full analysis |

### `soil_color_readings`
| Column | Notes |
|--------|-------|
| sample_id | FK → soil_samples |
| parameter | ENUM: `ph`, `nitrogen`, `phosphorus`, `potassium` |
| test_number | 1, 2, or 3 |
| color_hex, r, g, b | Captured color |
| computed_value | Individual reading converted to ppm/pH |
| captured_at | Timestamp |

Unique constraint on `(sample_id, parameter, test_number)` — re-capturing simply updates the existing row.

### `ph_tests`
| Column | Notes |
|--------|-------|
| sample_id | FK → soil_samples (unique — one pH test per sample) |
| step1_readings | JSON array of 3 capture objects |
| step1_ph, step1_variance, step1_confidence | Computed stats after Step 1 |
| step1_outcome | `win-bcg`, `win-btb`, `win-cpr`, `retest`, `high-acid`, `alkaline` |
| step1_remarks | Auto-generated technician remarks |
| next_solution | `BCG`, `BTB`, `CPR` (final), or `RETEST` |
| step2_solution | `BCG` or `BTB` |
| step2_readings | JSON array of 3 capture objects |
| step2_ph, step2_variance, step2_confidence | Computed stats after Step 2 |
| step2_outcome | `confirmed`, `borderline`, `inconsistent` |
| step2_remarks | Auto-generated technician remarks |
| technician_notes | Free text field (future use) |
| final_ph | The pH value written to soil_samples |
| status | ENUM: `step1`, `step2`, `complete`, `retest` |

### `crops`
18 Philippine crops seeded via `CropSeeder`. Stores `min/max` tolerance ranges for pH, N, P, K. Used for crop matching and the fertilizer calculator dropdown.

---

## Application Architecture

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   │   ├── LoginController.php
│   │   │   └── RegisterController.php
│   │   ├── Admin/
│   │   │   └── UserController.php
│   │   ├── SampleController.php        ← CRUD + auto-compute trigger
│   │   ├── ColorReadingController.php  ← API: POST /api/color-readings
│   │   ├── PhTestController.php        ← pH 2-step workflow
│   │   ├── ParameterTestController.php ← N/P/K capture pages
│   │   ├── FarmerController.php        ← CRUD + CSV import + JSON autocomplete
│   │   ├── ExportController.php        ← CSV + Phase 2 export
│   │   ├── AiRecommendationController.php
│   │   ├── DashboardController.php
│   │   └── HelpController.php
│   └── Middleware/
├── Models/
│   ├── User.php
│   ├── SoilSample.php
│   ├── SoilColorReading.php
│   ├── Crop.php
│   ├── Farmer.php
│   └── PhTest.php
├── Providers/
│   └── AppServiceProvider.php          ← Gate::define('admin', ...)
└── Services/
    ├── ColorScienceService.php         ← sRGB → Lab → CIEDE2000 → ppm
    ├── FertilizerService.php           ← BSWM recommendation engine
    └── PhTestService.php               ← decideSolution, computeStats, remarks

resources/views/
├── layouts/app.blade.php               ← Bootstrap 5, green sidebar
├── auth/
├── dashboard.blade.php
├── samples/
│   ├── index.blade.php
│   ├── show.blade.php                  ← Parameter status grid + results
│   ├── create.blade.php
│   └── report.blade.php
├── ph-test/
│   └── show.blade.php                  ← 2-step pH wizard
├── parameter-test/
│   └── show.blade.php                  ← N/P/K capture page (reused)
├── farmers/
│   ├── index, create, edit, import, _form
└── help/
    └── index.blade.php                 ← In-app guidelines
```

---

## Color Science Pipeline

### Why Not RGB?

RGB is device-dependent — the same physical strip color looks different depending on camera white balance, ambient light, and screen settings. Reliable comparison to a reference chart requires a **perceptually uniform color space**.

### The Pipeline

```
Webcam pixel (sRGB)
    → linearise (remove gamma, IEC 61966-2-1)
    → XYZ (D65 illuminant)
    → CIE L*a*b*
    → CIEDE2000 distance to each reference swatch
    → inverse-distance weighted interpolation
    → ppm / pH value
```

### CIEDE2000 Matching

The system measures how far a captured color is from each reference in the chart using CIEDE2000 (ISO 11664-6). It finds the **3 nearest reference colors** then computes an inverse-distance weighted average:

```
weight_i = 1 / max(deltaE_i, 0.001)
result   = Σ(weight_i × value_i) / Σ(weight_i)
```

If `deltaE < 0.5` to the nearest reference, the value is returned directly (exact match).

### Confidence & Variance

After 3 captures, `PhTestService::computeStats()` computes:
- **Average** pH across 3 readings
- **Variance** — spread of the 3 readings
- **Confidence** — `High` if variance ≤ 0.09, `Low` otherwise

---

## Fertilizer Recommendation Engine

`FertilizerService::recommend(ph, n, p, k)` — BSWM/PhilRice guidelines, per hectare.

### Fixed BSWM Table (displayed on sample page)

| Parameter | Threshold | Recommendation |
|-----------|-----------|---------------|
| pH < 5.0 | Strongly acidic | 2 t/ha dolomitic lime |
| pH 5.0–5.5 | Moderately acidic | 1 t/ha lime |
| pH > 7.5 | Alkaline | Organic matter / sulfur note |
| N < 20 ppm | Low | 4 bags Urea (46-0-0) / ha |
| N 20–40 ppm | Medium | 2.5 bags Urea / ha |
| N > 40 ppm | High | 1 bag Urea / ha (maintenance) |
| P < 15 ppm | Low | 2.5 bags TSP (0-46-0) / ha |
| P 15–30 ppm | Medium | 1.5 bags TSP / ha |
| P > 30 ppm | High | No TSP |
| K < 20 ppm | Low | 2 bags MOP (0-0-60) / ha |
| K 20–40 ppm | Medium | 1 bag MOP / ha |
| K > 40 ppm | High | No MOP |

All bags = 50 kg commercial bags.

---

## Crop-Specific Calculator

Available on the sample results page (inside the Fertilizer Recommendation card).

- **Crop dropdown** — sourced from the `crops` table via DB (not hardcoded)
- **Farm area** — input in hectares
- **Primary fertilizer type** — Urea, Complete 14-14-14, Ammonium Sulfate, DAP, MOP, Organic

### Calculation Logic

1. Target N/P/K (ppm) = crop's `max_nitrogen / max_phosphorus / max_potassium` from DB
2. Deficit (ppm) = max(0, target − current soil reading)
3. Convert to kg/ha: deficit_ppm × 2 (1 ppm ≈ 2 kg/ha at 0–15 cm depth)
4. Bags of primary fertilizer = kg_deficit / (50 kg × fertilizer_analysis_fraction)
5. For multi-nutrient fertilizers (14-14-14), the most limiting nutrient drives the quantity
6. Supplemental TSP or MOP cards appear when the primary fertilizer doesn't cover P or K

### Adding New Crops

Edit `database/seeders/CropSeeder.php` and re-run `php artisan db:seed --class=CropSeeder`. The calculator dropdown and crop matching are automatically updated.

---

## AI Agronomic Advisor

- **Model:** `claude-haiku-4-5-20251001`
- **Trigger:** Manual button on the sample results page
- **Input:** All 4 soil readings + status labels, fertility score, top 3 matching crops, fertilizer summary
- **Output:** 3-section advisory letter (Soil Health Assessment, Fertilizer Plan, Crop & Planting Advice)
- **Storage:** Saved to `soil_samples.ai_recommendation` — regenerating overwrites it

### Setup

Add to `.env`:
```env
ANTHROPIC_API_KEY=sk-ant-your-key-here
```

When the key is absent, the AI section displays a clear setup notice with billing instructions. No errors are thrown.

---

## Farmer Management & Phase 2

### Farmers Module

Routes under `/farmers`:

| Route | Action |
|-------|--------|
| `GET /farmers` | List all farmers (admin: all; user: own) |
| `GET /farmers/create` | Create farmer form |
| `POST /farmers` | Store new farmer |
| `GET /farmers/{id}/edit` | Edit farmer |
| `PUT /farmers/{id}` | Update farmer |
| `DELETE /farmers/{id}` | Delete farmer |
| `GET /farmers/import` | CSV import form |
| `POST /farmers/import` | Process CSV upload |
| `GET /api/farmers/json` | JSON endpoint for sample create autocomplete |

### CSV Import Format

Headers (case-insensitive): `name`, `address`, `farm_location`, `farm_id`

- `farm_id` is optional — leave blank if not assigned
- Duplicate farmer names (same user) are skipped

### Phase 2 — Arduino Integration

The `farm_id` field on farmers is reserved for **Phase 2 hardware integration** with Arduino-based field sensors.

**Phase 2 Export:** `GET /export/phase2`

Exports a CSV with columns specifically required by the Phase 2 import format:

| Column | Source |
|--------|--------|
| id | `farmers.farm_id` |
| user_id | `farmers.id` |
| sample_name | `soil_samples.sample_name` |
| location | `soil_samples.location` |
| sample_date | `soil_samples.date_tested` |
| ph_level | Computed |
| nitrogen_level | Computed |
| phosphorus_level | Computed |
| potassium_level | Computed |
| recommendations | Fertilizer summary string |

Only analyzed samples with a linked farmer record are included.

---

## Export System

### Full CSV Export

`GET /export?sample_id={id}` — single sample
`GET /export` — all samples (admin: all; user: own)

Includes:
- Sample and farmer metadata
- Final NPK/pH values with status labels
- Fertility score and recommended crop
- All 12 individual test readings (hex + computed value)
- Averaged hex per parameter
- Fertilizer recommendation (bags/ha)
- AI recommendation text
- pH test outcome summary (step1/step2 results, remarks)
- Legend and method notes sections

Output is **UTF-8 BOM CSV** — opens correctly in Microsoft Excel.

---

## Calibration Guide

The accuracy of all readings depends on the reference color charts in `ColorScienceService.php`.

### How to Recalibrate

1. Obtain the physical BSWM Soil Test Kit from your Municipal Agriculture Office
2. Photograph the color comparison card under **neutral white light** (not direct sun)
3. Open the photo in any image editor (GIMP, Photoshop, MS Paint)
4. Use the eyedropper tool to record the hex value of the center of each color square
5. Update the corresponding array in `app/Services/ColorScienceService.php`:
   - `PH_CHART` — red/orange → yellow → green → blue (pH 4.5 to 8.5)
   - `NITROGEN_CHART` — colorless → pink → purple
   - `PHOSPHORUS_CHART` — colorless → deep blue
   - `POTASSIUM_CHART` — clear → turbid white/gray (photograph against black background)

No other file needs to change.

### Camera Calibration Tips

- Use consistent lighting — ideally a lightbox or the dedicated capture box
- Keep the camera at the same distance and angle for every capture
- Avoid shadows and reflections on the test tube
- For Potassium, ensure the background inside the capture box is **matte black**
- Clean the test tube exterior before capturing

---

## Key Reference Numbers

| Value | What it is |
|-------|-----------|
| `dE2000 < 3.0` | Color match threshold to a reference swatch |
| `dE2000 < 0.5` | "Exact match" — skip interpolation |
| `70 × 70 px` | Webcam sampling region (center circle, pH page) |
| `80 × 80 px` | Webcam sampling region (N/P/K pages) |
| `3` | Captures per parameter |
| `12` | Total captures per sample (4 × 3) |
| `0.09` | Variance threshold for High/Low confidence |
| `N: 35%, P: 25%, K: 25%, pH: 15%` | Fertility score weights |
| `Low N < 20 ppm` | Nitrogen: Low |
| `High N ≥ 40 ppm` | Nitrogen: High |
| `Low P < 15 ppm` | Phosphorus: Low |
| `High P ≥ 30 ppm` | Phosphorus: High |
| `Low K < 20 ppm` | Potassium: Low |
| `High K ≥ 40 ppm` | Potassium: High |
| `pH Optimal: 6.0–7.0` | For fertility scoring |
| `1 ppm ≈ 2 kg/ha` | Soil nutrient density conversion (0–15 cm depth) |
| `50 kg` | Standard Philippine commercial fertilizer bag |
| `8 minutes` | BSWM reaction timer (CPR, BCG, BTB) |
| `480 seconds` | Same as above in the countdown timer |

---

*Last updated: v2.0 — Laravel 11 rewrite with BSWM 2-step pH protocol, individual N/P/K test pages, farmer management, and Phase 2 export.*
