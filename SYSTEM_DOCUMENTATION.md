# SYSTEM DOCUMENTATION
## Soil Fertility Analyzer — Webcam-Based Colorimetric Soil Testing System
**Office of the Municipal Agriculturist (OMA)**
*Built on Laravel 11 · PHP 8.2 · Bootstrap 5.3*

---

## 1. SYSTEM OVERVIEW

The **Soil Fertility Analyzer** is a web-based application that guides field technicians through the Bureau of Soils and Water Management (BSWM) colorimetric procedure for measuring soil pH, Nitrogen (N), Phosphorus (P), and Potassium (K) using a portable test kit and a webcam-based image capture box.

The system automates color recognition using CIE L\*a\*b\* color space conversion and the CIEDE2000 perceptual color difference formula to match captured test tube colors against calibrated reference charts, eliminating the need for manual subjective color comparison.

---

## 2. SYSTEM ARCHITECTURE

```
┌────────────────────────────────────────────────────────────┐
│                     Web Browser (Client)                   │
│  Bootstrap 5 UI · Webcam JS · AJAX API calls               │
└──────────────────────────┬─────────────────────────────────┘
                           │ HTTP / JSON
┌──────────────────────────▼─────────────────────────────────┐
│                   Laravel 11 Application                   │
│                                                            │
│  Controllers  →  Services  →  Models  →  Database (MySQL)  │
│                                                            │
│  ColorScienceService  — CIEDE2000 color matching           │
│  FertilizerService    — BSWM/PhilRice NPK thresholds       │
│  PhTestService        — pH 2-step workflow logic           │
│  AiRecommendationService — Claude AI agronomist            │
│  GeminiCropRecommendationService — Gemini AI crops         │
└────────────────────────────────────────────────────────────┘
```

### Technology Stack

| Layer       | Technology                        |
|-------------|-----------------------------------|
| Framework   | Laravel 11 (PHP 8.2)              |
| Frontend    | Bootstrap 5.3, Font Awesome 6.5   |
| Database    | MySQL (via Laravel Eloquent ORM)  |
| Auth        | Laravel session-based (username)  |
| AI Services | Anthropic Claude API, Google Gemini API |
| Build       | Laravel Mix, Sass, Bootstrap JS   |

---

## 3. USER ROLES

| Role          | Access Level                                                              |
|---------------|---------------------------------------------------------------------------|
| **Admin**     | Full access: all samples, all users, color chart calibration, deletion   |
| **Technician**| Own samples only, farmer management, export, crop requirements reference  |

---

## 4. KEY MODULES

### 4.1 Authentication

- Username + password login (not email-based)
- Session-based authentication with CSRF protection
- Role-based redirection: admin → Admin Dashboard, others → User Dashboard
- Automatic session invalidation on logout

### 4.2 Farmer Management

- CRUD operations for farmer profiles (name, address, farm location, farm ID)
- CSV bulk import for batch onboarding
- JSON autocomplete endpoint used by the Sample creation form
- Each farmer is scoped to the creating technician (admins see all)

### 4.3 Soil Sample Management

- Samples are linked to a farmer, a technician (user), and a test date
- Sample limit: 5 per non-admin user (configurable)
- After all 4 parameters are captured and averaged, the system auto-computes:
  - pH level, N/P/K levels (ppm)
  - Fertility Score (weighted: N 35%, P 25%, K 25%, pH 15%)
  - Top matching crop name
- Sample reset wipes all readings for re-capture
- Admin-only deletion requires password confirmation

### 4.4 pH Testing — BSWM 2-Step Protocol

The pH test follows the Bureau of Soils and Water Management (BSWM) two-step colorimetric procedure:

```
Step 1 — CPR (Cresol Red + Phenolphthalein)
  → 3 webcam captures, averaged
  → Decision: pH ≤ 5.4 → BCG | pH 5.4–5.8 → CPR final | pH > 5.8 → BTB

Step 2 — BCG (Bromocresol Green) or BTB (Bromothymol Blue)
  → 3 webcam captures, averaged
  → Final pH recorded; outcome: confirmed / borderline / inconsistent
```

Each capture is stored as a JPEG snapshot and a hex color value. The system snaps computed pH values to discrete card reference points (e.g., 4.8, 5.0, 5.2…) to match what a technician reads from the physical BSWM color card.

### 4.5 N/P/K Color Capture

Each nutrient parameter (Nitrogen, Phosphorus, Potassium) uses:
- 3 webcam captures → RGB averaged → hex color
- Color matched to ppm value using CIEDE2000 against the BSWM reagent color chart
- Upsert to `soil_color_readings` table; averaged hex stored to `soil_samples`

### 4.6 Color-to-Value Algorithm (ColorScienceService)

The core algorithm converts any hex color to a soil parameter value:

1. **RGB → CIE L\*a\*b\*** — perceptually uniform color space conversion
2. **CIEDE2000 ΔE** — compute perceptual distance between captured color and each reference chart point
3. **Nearest-match with interpolation** — find the 3 closest chart colors; if minimum ΔE > 0.5, compute a weighted average of their values (inverse-distance weighting) for smooth interpolation
4. **Clamp** — restrict result to valid measurement range

Reference charts (CPR, BCG, BTB for pH; N, P, K) are stored in the database and editable by admins. Constants in `ColorScienceService` serve as fallbacks.

### 4.7 Fertilizer Recommendation Engine (BSWM/PhilRice)

Based on soil levels, the system recommends:

| Nutrient | Low           | Medium         | High         |
|----------|---------------|----------------|--------------|
| N (ppm)  | < 45 → 4 bags/ha Urea | 45–160 → 2.5 bags/ha | ≥ 160 → 1 bag/ha |
| P (ppm)  | < 15 → 2.5 bags/ha TSP | 15–30 → 1.5 bags/ha | ≥ 30 → none |
| K (ppm)  | < 20 → 2 bags/ha MOP | 20–40 → 1 bag/ha | ≥ 40 → none |
| pH       | < 5.0 → 2 t/ha lime | 5.0–5.5 → 1 t/ha lime | ≥ 5.5 → none |

The **Fertilizer Calculator** on the sample page provides crop-specific rates by computing NPK deficits (1 ppm ≈ 2 kg/ha) and translating them to 50-kg fertilizer bags, with supplemental TSP/MOP when the primary fertilizer cannot fully meet P or K needs.

### 4.8 Crop Matching Algorithm

Three matching strategies rank crops from the database:

| Strategy | Filter | Rank by |
|----------|--------|---------|
| Tolerance Match | pH within crop's range | # of NPK parameters also in range |
| Fertility Match | No pH filter | # of NPK parameters in range |
| pH Threshold | pH within crop's range | NPK score |

The **Crop Requirements** reference page (`/crop-requirements`) lists all crops with their min/max pH, N, P, K ranges and is exportable to CSV for manual Excel verification.

### 4.9 AI Recommendation

- **Claude (Anthropic)** — generates a 3-section agronomist report: Soil Health Assessment, Fertilizer Application Plan, and Crop & Planting Advice. Requires `ANTHROPIC_API_KEY`.
- **Gemini (Google)** — returns the top 10 crop recommendations with fertilizer adjustments. Requires `GEMINI_API_KEY`.

Both AI features are optional; the system works without them.

### 4.10 Data Export

| Export | Format | Contents |
|--------|--------|----------|
| Full Export | CSV (UTF-8 BOM) | All sample data, color readings, fertilizer recommendations |
| Phase 2 Export | CSV | Arduino-compatible format with farm_id matching |
| Crop Requirements | CSV | All crop pH/NPK ranges for offline verification |
| Sample PDF | Print-CSS page | Individual sample report for printing |

---

## 5. DATABASE SCHEMA OVERVIEW

```
users
  id · username · email · password · user_type (admin/farmer/professional)

farmers
  id · user_id → users · name · address · farm_location · farm_id

soil_samples
  id · user_id → users · farmer_id → farmers
  sample_name · location · sample_date · date_tested · farmer_name · address
  ph_color_hex · nitrogen_color_hex · phosphorus_color_hex · potassium_color_hex
  ph_level · nitrogen_level · phosphorus_level · potassium_level
  fertility_score · recommended_crop · ai_recommendation · analyzed_at

soil_color_readings
  id · sample_id → soil_samples · parameter (ph/nitrogen/phosphorus/potassium)
  test_number (1/2/3) · color_hex · r · g · b · computed_value
  captured_image · captured_at

ph_tests
  id · sample_id → soil_samples (unique)
  step1_readings (JSON) · step1_ph · step1_outcome · next_solution
  step2_solution (BCG/BTB) · step2_readings (JSON) · step2_ph · step2_outcome
  final_ph · status (step1/step2/complete/retest)

crops
  id · name · description
  min_ph · max_ph · min_nitrogen · max_nitrogen
  min_phosphorus · max_phosphorus · min_potassium · max_potassium

ph_color_charts
  id · indicator (CPR/BCG/BTB) · ph_value · hex_value · active

npk_color_charts
  id · nutrient (N/P/K) · ppm_value · hex_value · category · active
```

---

## 6. ROUTE SUMMARY

| Method | URI | Module |
|--------|-----|--------|
| GET/POST | `/login`, `/register` | Authentication |
| GET | `/dashboard` | User Dashboard |
| GET/POST | `/samples`, `/samples/create` | Sample CRUD |
| GET | `/samples/{id}` | Sample Detail + Auto-compute |
| GET | `/samples/{id}/ph-test` | pH 2-Step Test Page |
| GET | `/samples/{id}/test/{param}` | N/P/K Capture Page |
| POST | `/api/color-readings` | Color Reading API |
| POST | `/api/ph-test/capture` | pH Capture API |
| POST | `/api/ai-recommendation` | Claude AI API |
| GET/POST | `/farmers` | Farmer CRUD + Import |
| GET | `/export`, `/export/phase2` | CSV Export |
| GET | `/crop-requirements` | Crop Reference Page |
| GET | `/admin/*` | Admin Panel (admin only) |

---

## 7. SECURITY MEASURES

- All routes (except login/register) protected by `auth` middleware
- Admin routes protected by `can:admin` Gate policy
- CSRF tokens on all POST/PUT/DELETE forms
- Sample ownership enforced on all read/write operations
- Admin-only sample deletion requires password re-confirmation
- Captured images stored in `public/captures/` with per-sample directories
- Sample directory auto-deleted when a sample is permanently removed

---

*Document version: 1.0 — Generated for the Soil Fertility Analyzer system*
