# Soil Fertility Analyzer — Office of the Municipal Agriculturist (OMA)

A PHP web application for soil nutrient analysis using webcam-captured colorimetric test strip images. Built for field use by agricultural extension workers in Philippine municipalities.

---

## Table of Contents

1. [What This System Does](#what-this-system-does)
2. [File Structure](#file-structure)
3. [Database Schema](#database-schema)
4. [How the Color Science Works](#how-the-color-science-works)
5. [The 3-Test Averaging System](#the-3-test-averaging-system)
6. [Fertilizer Recommendation Engine](#fertilizer-recommendation-engine)
7. [AI Recommendation (Claude API)](#ai-recommendation-claude-api)
8. [Crop Matching Algorithm](#crop-matching-algorithm)
9. [Authentication & User Roles](#authentication--user-roles)
10. [Export System](#export-system)
11. [Known Issues & Technical Debt](#known-issues--technical-debt)
12. [Calibration: BSWM Color Chart](#calibration-bswm-color-chart)
13. [Laravel 11 Upgrade Roadmap](#laravel-11-upgrade-roadmap)
14. [Setup Instructions](#setup-instructions)

---

## What This System Does

Agricultural extension workers collect soil samples from farmers' fields and perform colorimetric tests using the **Philippine BSWM (Bureau of Soils and Water Management) Soil Test Kit**. The kit produces color reactions in test tubes for four parameters: pH, Nitrogen (N), Phosphorus (P), and Potassium (K).

This system replaces visual chart comparison with a **webcam-based image analysis pipeline**:

1. Worker registers sample with farmer info
2. Points webcam at each test tube in turn
3. Captures 3 photos per parameter (12 total) for accuracy
4. System averages the 3 readings, converts color to nutrient value using CIE Lab + CIEDE2000 color science
5. Generates fertilizer recommendations (BSWM guidelines), crop recommendations from a database of 18 Philippine crops, and an optional AI-written agronomic advice letter via Claude API
6. Exports results to CSV for records and reporting

---

## File Structure

```
soil-image-processing/
├── config.php                  ← ALL core logic lives here (see below)
├── analysis.php                ← Form: register a new soil sample
├── results.php                 ← Main view: webcam capture + results display
├── save_soil_parameters.php    ← API endpoint: receives one color reading from JS
├── ai_recommendation.php       ← API endpoint: calls Claude API, returns advice
├── export_excel.php            ← Streams a CSV download (single sample or all)
├── login.php                   ← Login form + session creation
├── register.php                ← User registration form
├── user_dashboard.php          ← Dashboard for farmer/professional users
├── admin_dashboard.php         ← Dashboard for admin users
├── users.php                   ← Admin: user management
├── database.sql                ← Full DB schema + seed data (run once)
├── includes/
│   ├── header.php              ← Bootstrap 5 navbar + sidebar + HTML head
│   └── footer.php              ← Closes Bootstrap JS, closes HTML
└── logo.jpg                    ← System logo (referenced in header)
```

### config.php — The Core

Everything that is logic (not UI) lives in `config.php`. It is included with `require_once 'config.php'` at the top of every page. It contains:

| Section | What it does |
|---------|-------------|
| DB credentials & PDO connection | `$pdo` is the shared database handle used everywhere |
| `isLoggedIn()`, `isAdmin()`, `redirect()` | Auth helpers |
| `hexToRgb()`, `rgbToLab()` | Color conversion pipeline |
| `deltaE2000()`, `deltaE76()` | Color distance formulas |
| `matchColorToValue()` | Core strip-reading function |
| `PH_COLOR_CHART`, `NITROGEN_COLOR_CHART`, etc. | Reference color arrays |
| `colorToPhLevel()`, `colorToNitrogenLevel()`, etc. | Public API used by all pages |
| `getNutrientStatus()` | Classifies a value as Low/Medium/High |
| `computeFertilityScore()` | Weighted 0-100 soil health score |
| `getFertilizerRecommendation()` | BSWM-based fertilizer bags/ha calculator |

---

## Database Schema

Four tables. Run `database.sql` once to create them.

### `users`
| Column | Type | Notes |
|--------|------|-------|
| id | INT UNSIGNED PK | Auto-increment |
| username | VARCHAR(50) UNIQUE | Login name |
| email | VARCHAR(100) UNIQUE | |
| password | VARCHAR(255) | **Plain-text** — see Known Issues |
| user_type | ENUM | `farmer`, `professional`, `admin` |

### `crops`
Stores 18 Philippine crops with min/max tolerance ranges for pH, N, P, K. Used for crop matching. Seeded by `database.sql`. To add more crops, insert rows directly.

### `soil_samples`
The main table. One row = one farmer's soil sample.

| Column | Notes |
|--------|-------|
| user_id | FK to users |
| sample_name, farmer_name, address, location | Entered in analysis.php |
| sample_date, date_tested | Dates from form |
| color_hex | Overall soil color (legacy, not used in analysis) |
| ph_color_hex, nitrogen_color_hex, phosphorus_color_hex, potassium_color_hex | **Averaged** hex from 3 webcam tests — written by save_soil_parameters.php after 3rd test |
| ph_level, nitrogen_level, phosphorus_level, potassium_level | Computed nutrient values (ppm / pH scale) |
| fertility_score | 0–100 weighted score |
| ai_recommendation | TEXT — stored Claude API response |
| recommended_crop | Top crop name from matching query |
| tests_completed | Count 0–12 (progress tracker) |
| analyzed_at | Timestamp set when final computation runs |

### `soil_color_readings`
Stores each of the up to 12 individual test captures (4 parameters × 3 tests). The `UNIQUE KEY uq_reading (sample_id, parameter, test_number)` means re-capturing test #2 for nitrogen simply updates the existing row via `ON DUPLICATE KEY UPDATE`.

| Column | Notes |
|--------|-------|
| sample_id | FK to soil_samples |
| parameter | ENUM: ph, nitrogen, phosphorus, potassium |
| test_number | 1, 2, or 3 |
| color_hex, r, g, b | The captured color |
| computed_value | Individual reading already converted to ppm/pH |

---

## How the Color Science Works

> This is the most critical part to understand before making changes.

### Why not RGB?

RGB is device-dependent — the same physical color looks different depending on screen brightness, camera white balance, ambient lighting. We cannot reliably compare soil strip colors to a reference chart in RGB space.

### The Pipeline

```
Webcam pixel (sRGB) → linearise (remove gamma) → XYZ (D65) → CIE L*a*b*
```

This happens in `rgbToLab()` in `config.php`. The math follows IEC 61966-2-1.

**CIE L\*a\*b\*** is a perceptually uniform color space:
- `L*` = lightness (0 black → 100 white)
- `a*` = green-red axis
- `b*` = blue-yellow axis

Equal Euclidean distances in Lab ≈ equal perceived color differences by a human.

### CIEDE2000 (deltaE2000)

Used to measure how far a captured strip color is from each reference color in the chart. This is the ISO standard for accurate color difference (ISO 11664-6).

It is more complex than CIE76 (simple Euclidean distance) because it adds correction terms for:
- Lightness non-uniformity near black/white
- Chroma weighting for highly saturated colors
- The blue region instability (important for phosphorus molybdenum-blue test)

**Threshold for classification:** `dE2000 < 3.0` is considered a match. Values < 1 are imperceptible to the human eye.

### matchColorToValue() — Interpolation

The function finds the 3 nearest reference colors by CIEDE2000 distance, then returns an **inverse-distance weighted average** of their values. So if a captured color sits between the "25 ppm" and "30 ppm" reference swatches, you get approximately 27 ppm rather than a hard step.

```
weight_i = 1 / max(dE_i, 0.001)
result   = Σ(weight_i × value_i) / Σ(weight_i)    [top 3 only]
```

If `dE < 0.5` to the nearest reference (essentially identical color), it returns that value directly without interpolation.

### BSWM Reagent Chemistry — What Colors to Expect

This is critical for calibration. The BSWM STK uses different chemistry per parameter:

| Parameter | Reagent | Color Scale | Direction |
|-----------|---------|-------------|-----------|
| **pH** | Bromothymol Blue + Thymol Blue | Red-orange → green → blue | Acidic=red, Alkaline=blue |
| **Nitrogen** | Indophenol blue / diphenylamine | Colorless → pink → purple | More N = darker purple |
| **Phosphorus** | Molybdenum blue (ascorbic acid reduction) | Colorless → deep blue | More P = deeper blue |
| **Potassium** | Turbidimetric (NOT colorimetric) | Clear → milky white/gray | More K = more turbid |

**Important for Potassium:** Because K uses turbidity, not a color reaction, photograph the K test tube against a **black background** so that increasing cloudiness registers as an increase from dark to light. The reference chart uses a gray scale for this reason.

---

## The 3-Test Averaging System

Each of the 4 parameters requires 3 separate webcam captures. This reduces single-frame error by approximately 60%.

**Workflow (all in JavaScript inside results.php + PHP endpoint save_soil_parameters.php):**

1. Worker clicks "Capture #1" for pH
2. JS samples an 80×80 pixel region from the center of the webcam frame (the dashed circle guides placement)
3. Averages all pixels in that region → single `(r, g, b)` value → converts to hex
4. POSTs to `save_soil_parameters.php` with `{sample_id, parameter, color_hex, r, g, b, test_number}`
5. PHP saves row to `soil_color_readings`, calls `colorToPhLevel()` to store `computed_value`
6. After the 3rd test for a parameter: PHP averages the 3 RGB values, formats as hex, writes to `soil_samples.ph_color_hex`
7. When all 4 averaged hex values exist: the "Compute & View Results" button appears
8. On next page load: `results.php` detects all 4 colors present and calls the color conversion functions → writes final ppm values → sets `analyzed_at`

**Why the page reloads after each capture:** The JS calls `setTimeout(() => location.reload(), 600)` after each save. This is intentional — it refreshes the color swatch table from the database, ensuring the display matches what was actually saved.

---

## Fertilizer Recommendation Engine

`getFertilizerRecommendation(ph, n, p, k)` in `config.php`.

Based on **BSWM / PhilRice guidelines** for Philippine conditions. All rates are **per hectare**, using **50-kg bags** as the unit (standard Philippine commercial bags).

### Fertilizer Products Used

| Product | Grade | Use |
|---------|-------|-----|
| Urea | 46-0-0 | Nitrogen source |
| TSP (Triple Superphosphate) | 0-46-0 | Phosphorus source |
| MOP (Muriate of Potash) | 0-0-60 | Potassium source |
| Dolomitic Lime | — | pH correction (acidic soils) |

### Decision Logic

| Parameter | Threshold | Recommendation |
|-----------|-----------|---------------|
| pH < 5.0 | Strongly acidic | 2 t/ha lime |
| pH 5.0–5.5 | Moderately acidic | 1 t/ha lime |
| pH > 7.5 | Alkaline | Organic matter / sulfur note |
| N < 20 ppm | Low | 4 bags Urea/ha (split: ½ basal, ½ topdress) |
| N 20–40 ppm | Medium | 2.5 bags Urea/ha |
| N > 40 ppm | High | 1 bag Urea/ha (maintenance) |
| P < 15 ppm | Low | 2.5 bags TSP/ha (basal) |
| P 15–30 ppm | Medium | 1.5 bags TSP/ha |
| P > 30 ppm | High | 0 bags TSP |
| K < 20 ppm | Low | 2 bags MOP/ha |
| K 20–40 ppm | Medium | 1 bag MOP/ha |
| K > 40 ppm | High | 0 bags MOP |

The function returns an array with `lime_tons`, `urea_bags`, `tsp_bags`, `mop_bags`, and `notes` (array of advisory strings). This is used in both `results.php` (display) and `ai_recommendation.php` (passed to Claude as context) and `export_excel.php` (included in CSV).

---

## AI Recommendation (Claude API)

`ai_recommendation.php` — POST JSON endpoint, returns JSON.

**Model used:** `claude-haiku-4-5-20251001` (fast, cost-effective for form-letter style output).

**What it receives:**
- All 4 soil readings + status labels
- Fertility score
- Top 3 matching crops from the database
- Pre-computed fertilizer recommendation summary

**Prompt structure (3-section response):**
1. SOIL HEALTH ASSESSMENT
2. FERTILIZER APPLICATION PLAN
3. CROP & PLANTING ADVICE

**API key:** Set as environment variable `ANTHROPIC_API_KEY`. Do **not** hardcode it in config.php.

**Persistence:** The AI response is saved to `soil_samples.ai_recommendation`. Re-generating overwrites the stored text.

**Access control:** Admin can generate advice for any sample. Regular users can only generate for their own samples (enforced with `user_id` check in the query).

---

## Crop Matching Algorithm

A SQL scoring query used in both `results.php` and `ai_recommendation.php`:

```sql
SELECT name,
  (CASE WHEN :ph BETWEEN min_ph AND max_ph THEN 1 ELSE 0 END +
   CASE WHEN :n  BETWEEN min_nitrogen AND max_nitrogen THEN 1 ELSE 0 END +
   CASE WHEN :p  BETWEEN min_phosphorus AND max_phosphorus THEN 1 ELSE 0 END +
   CASE WHEN :k  BETWEEN min_potassium AND max_potassium THEN 1 ELSE 0 END) AS score
FROM crops
ORDER BY score DESC
```

Score 4/4 = perfect match. The results page shows all crops where pH is within range, ordered by score. The top match is stored as `recommended_crop`.

The crop database (18 crops) is seeded in `database.sql`. It includes common Philippine staples, vegetables, root crops, and cash crops with their known NPK/pH tolerance ranges.

---

## Authentication & User Roles

Three user types: `farmer`, `professional`, `admin`.

- `farmer` and `professional` behave identically in the current system — both see only their own samples
- `admin` sees all samples from all users, can export all, can manage users via `users.php`

**Session variables set on login:**
- `$_SESSION['user_id']`
- `$_SESSION['username']`
- `$_SESSION['user_type']`
- `$_SESSION['email']`

Auth checks use `isLoggedIn()` and `isAdmin()` from `config.php`.

---

## Export System

`export_excel.php` streams a UTF-8 BOM CSV directly (no temp file). Excel opens it correctly due to the BOM header.

**Two modes:**
- `?sample_id=N` — single sample with all 12 individual test readings included
- No parameter — all samples (admin sees all, users see own)

**CSV includes:**
- Sample and farmer metadata
- Final NPK/pH values + status labels
- Fertility score
- All 12 individual test readings (hex + computed value per test)
- Averaged color hex per parameter
- Fertilizer recommendation (bags/ha)
- Recommended crop
- AI recommendation text (full)
- Legend section explaining all columns
- Method notes section

The column names are fixed for "Phase 2 import version 1.0" — changing them will break any downstream import scripts.

---

## Known Issues & Technical Debt

These are things that work but should be fixed in the Laravel upgrade:

### 1. Plain-text passwords
`login.php` compares `$password == $user['password']` directly. The database seed has `admin123` stored as plain text.
**Fix in Laravel:** Use `Hash::make()` / `Hash::check()` with bcrypt.

### 2. Reference color charts are approximate
The BSWM does not publish their exact color chart values digitally — the physical chart is only in the printed kit. The current hex values in `config.php` are chemically-derived approximations.
**Fix:** Photograph the actual BSWM STK color chart from the agriculture office, extract each reference square's hex using an eyedropper, and update the 4 chart arrays in `config.php`. This single file change is all that's needed.

### 3. Potassium turbidity vs. colorimetry
The K test is turbidity-based (cloudiness scale) but is processed the same as the color-based tests. For best K readings, the test tube must be photographed against a pure black background.
**Future improvement:** A dedicated turbidity scoring path that measures the deviation from background darkness rather than hue.

### 4. No password hashing for registration
`register.php` stores passwords as entered. Same fix as #1.

### 5. Mixed HTML/PHP in page files
`results.php` is over 900 lines of interleaved PHP and HTML. It handles: list view, detail view, webcam UI, analysis results, fertilizer display, crop table, AI section, and two JS blocks.
**Fix in Laravel:** Split into Controller + Blade views.

### 6. No CSRF protection
Forms use plain `<form method="POST">` with no CSRF token.
**Fix in Laravel:** Blade's `@csrf` directive.

### 7. Temperature correction not implemented
Research confirms colorimetric strip readings shift with temperature above 30°C (common in Philippine field conditions). A correction factor should be applied to ppm values based on ambient temperature.
**Future feature:** Add a temperature input field in the capture UI; apply published correction factors per parameter.

### 8. No input sanitization on color_hex before DB write
`save_soil_parameters.php` validates the hex format with regex (`/^#[0-9A-F]{6}$/i`) but does not sanitize the parameter ENUM beyond an `in_array` check. This is sufficient but should become model validation in Laravel.

---

## Calibration: BSWM Color Chart

The accuracy of every reading depends entirely on how well the reference color charts in `config.php` match the actual printed BSWM STK chart.

### How to re-calibrate

1. Obtain the physical BSWM Soil Test Kit from your Municipal Agriculture Office
2. Photograph the included color comparison card under neutral white light (or natural daylight, not direct sun)
3. Open the photo in any image editor with an eyedropper tool (GIMP, Photoshop, even MS Paint)
4. Click the center of each color square and record the hex value
5. Update the corresponding array in `config.php`:
   - `PH_COLOR_CHART` — label each hex with its pH value (4.5 to 8.5)
   - `NITROGEN_COLOR_CHART` — pale/colorless to pink to purple (not yellow)
   - `PHOSPHORUS_COLOR_CHART` — colorless to deep blue
   - `POTASSIUM_COLOR_CHART` — clear to turbid white/gray (photograph against black)

The format is always: `'#RRGGBB' => float_value`

No other file needs to change — the four public functions (`colorToPhLevel()` etc.) call `matchColorToValue()` which uses whatever is in these arrays.

---

## Laravel 11 Upgrade Roadmap

The system is already structured in a way that maps cleanly to MVC. Here is the suggested mapping:

### Controllers

| Current file | Laravel Controller | Methods |
|---|---|---|
| `analysis.php` | `SampleController` | `create()`, `store()` |
| `results.php` (list) | `SampleController` | `index()` |
| `results.php` (detail) | `SampleController` | `show()` |
| `results.php` (redo) | `SampleController` | `reset()` |
| `save_soil_parameters.php` | `ColorReadingController` | `store()` |
| `ai_recommendation.php` | `AiRecommendationController` | `generate()` |
| `export_excel.php` | `ExportController` | `sample()`, `all()` |
| `login.php` | Built-in Laravel Auth | — |
| `users.php` | `UserController` | `index()`, `store()`, `destroy()` |

### Models

- `User` — with `password` hashed, roles via `user_type`
- `SoilSample` — with relationships to `User`, `SoilColorReading`, `Crop`
- `SoilColorReading` — belongs to `SoilSample`
- `Crop` — standalone, seeded via `DatabaseSeeder`

### Services / Classes

| Current location | Laravel Service |
|---|---|
| `config.php` color functions | `App\Services\ColorScienceService` |
| `config.php` `getFertilizerRecommendation()` | `App\Services\FertilizerService` |
| `config.php` reference chart arrays | `App\Config\BswmColorCharts` (or config file) |
| `ai_recommendation.php` Claude call | `App\Services\AiRecommendationService` |
| Crop matching SQL | `App\Models\Crop::matchingForSoil($ph, $n, $p, $k)` scope |

### Migrations

Run `database.sql` logic through Laravel migrations. Key constraints to preserve:
- `UNIQUE KEY uq_reading (sample_id, parameter, test_number)` on `soil_color_readings`
- `ON DELETE CASCADE` from soil_color_readings to soil_samples
- The `ALTER TABLE ... ADD COLUMN IF NOT EXISTS` pattern becomes separate migration files

### Views (Blade)

`results.php` alone should become:
- `resources/views/samples/index.blade.php`
- `resources/views/samples/show.blade.php`
- `resources/views/samples/_webcam_capture.blade.php` (Livewire component or Alpine.js)
- `resources/views/samples/_results_grid.blade.php`
- `resources/views/samples/_fertilizer_card.blade.php`
- `resources/views/samples/_crop_table.blade.php`
- `resources/views/samples/_ai_section.blade.php`

### Environment

Move from `config.php` constants to `.env`:
```env
DB_HOST=localhost
DB_DATABASE=soil_analyzer
DB_USERNAME=root
DB_PASSWORD=
ANTHROPIC_API_KEY=sk-ant-...
```

### JavaScript

The webcam capture logic in `results.php` can stay as vanilla JS or be wrapped in an Alpine.js component. The fetch calls to `save_soil_parameters.php` become calls to a Laravel API route (`POST /api/samples/{id}/readings`).

---

## Setup Instructions

### Requirements
- PHP 8.1+
- MySQL / MariaDB 10.4+
- A web server (Apache/Nginx) or PHP built-in server
- A webcam accessible via the browser

### Steps

1. Clone the repository into your web server's document root
2. Create the database:
   ```sql
   mysql -u root -p < database.sql
   ```
3. Set your Anthropic API key as an environment variable (optional — AI feature is disabled gracefully if absent):
   ```bash
   export ANTHROPIC_API_KEY=sk-ant-your-key-here
   ```
   Or add it to your Apache/Nginx virtual host config.
4. Update `config.php` database credentials if needed (default: host=localhost, db=soil_analyzer, user=root, pass=empty)
5. Open the app in your browser and log in with:
   - Username: `admin`
   - Password: `admin123`
   *(Change this immediately after first login via the Users page)*

### Local development (PHP built-in server)
```bash
cd /path/to/soil-image-processing
php -S localhost:8000
```

Then open `http://localhost:8000` in Chrome or Firefox (webcam requires HTTPS or localhost).

---

## Quick Reference: Key Numbers

| Value | What it is |
|-------|-----------|
| `dE2000 < 3.0` | Threshold for a color match to a reference swatch |
| `0.5` | "Perfect match" threshold — skip interpolation |
| `80 × 80 px` | Webcam sampling region (centered in frame) |
| `3` | Tests per parameter before averaging |
| `12` | Total captures per sample (4 params × 3 tests) |
| `N: 35%, P: 25%, K: 25%, pH: 15%` | Fertility score weights |
| `Low N < 20 ppm`, `High N > 40 ppm` | Nitrogen thresholds |
| `Low P < 15 ppm`, `High P > 30 ppm` | Phosphorus thresholds |
| `Low K < 20 ppm`, `High K > 40 ppm` | Potassium thresholds |
| `pH optimal: 6.0–7.0` | For fertility scoring |
| `400` | Max words in AI recommendation prompt |
| `claude-haiku-4-5-20251001` | Claude model used for AI recommendations |
