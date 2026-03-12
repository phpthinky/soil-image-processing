# Nitrogen (N) Color Chart Calibration Guide

## Overview

The soil nitrogen analyzer matches a captured reagent color against a set of reference hex values using CIEDE2000 delta-E color distance. Reference values are stored in the `NITROGEN_COLOR_CHART` constant in `app/Services/ColorScienceService.php` and the classification thresholds are in `app/Services/FertilizerService.php`. This guide documents the correct physical card colors, the correct ppm ranges, and the known discrepancies in the current system.

---

## Nitrogen Level Ranges (BSWM Card)

| Level  | Color Family                        | ppm Range   |
|--------|-------------------------------------|-------------|
| LOW    | Orange → Light Brown → Dark Brown   | 15 – 45     |
| MEDIUM | Green → Dark Green → Teal Green     | 60 – 150    |
| HIGH   | Blue-Green → Blue → Teal Blue       | 160 – 240   |

> **Note:** There is a gap between LOW (≤ 45 ppm) and MEDIUM (≥ 60 ppm). This is intentional — the physical card has no printed reference between 45 and 60 ppm. Values in this gap should snap up to the first MEDIUM point (60 ppm) using the same ceiling-snap rule as pH.

---

## Color Progressions (What to Expect on the Card)

### LOW Nitrogen — Orange to Dark Brown
```
~15 ppm  →  Orange             (to be measured from card)
~22 ppm  →  Light orange-tan   (to be measured from card)
~30 ppm  →  Light brown        (to be measured from card)
~38 ppm  →  Brown              (to be measured from card)
~45 ppm  →  Dark brown         (to be measured from card)
```
> **Calibration status:** ⚠️ NOT YET MEASURED — hex values must be captured from the physical BSWM card under the production lighting box.

### MEDIUM Nitrogen — Green to Teal Green
```
~60 ppm  →  Green              (to be measured from card)
~80 ppm  →  Mid-green          (to be measured from card)
~100 ppm →  Dark green         (to be measured from card)
~125 ppm →  Dark teal-green    (to be measured from card)
~150 ppm →  Teal green         (to be measured from card)
```
> **Calibration status:** ⚠️ NOT YET MEASURED — hex values must be captured from the physical BSWM card under the production lighting box.

### HIGH Nitrogen — Blue-Green to Teal Blue
```
~160 ppm →  Blue-green         (to be measured from card)
~190 ppm →  Blue               (to be measured from card)
~215 ppm →  Deep blue          (to be measured from card)
~240 ppm →  Teal blue          (to be measured from card)
```
> **Calibration status:** ⚠️ NOT YET MEASURED — hex values must be captured from the physical BSWM card under the production lighting box.

---

## ⚠️ Known System Bugs (Must Fix Before Use)

### Bug 1 — Wrong Reference Colors in `ColorScienceService.php`

**File:** `app/Services/ColorScienceService.php`, lines 79–84

The current `NITROGEN_COLOR_CHART` constant uses a **pink-to-magenta gradient** that does not correspond to any physical BSWM nitrogen card:

```php
// CURRENT (WRONG) — pink/magenta colors, nothing like the physical card
public const NITROGEN_COLOR_CHART = [
    '#FFF5F5' =>  2.0,  // near-white pink
    '#FFE0E8' =>  8.0,  // very light pink
    '#FFB3C6' => 15.0,  // pink
    '#FF80A0' => 22.0,  // medium pink
    '#FF4D80' => 30.0,  // hot pink
    '#E6006B' => 40.0,  // deep pink
    '#CC0066' => 50.0,  // magenta
    '#990066' => 60.0,  // dark magenta
    '#660066' => 70.0,  // dark purple
    '#440044' => 80.0,  // near-black purple
];
```

**Effect:** The physical card shows orange/brown/green/blue. The system references pink/purple. CIEDE2000 will always find a poor match, causing every color to land near whichever pink reference is closest in Lab space — not the correct ppm level.

**Fix required:** Replace all hex values with colors measured from the physical BSWM card following the Calibration Procedure below.

---

### Bug 2 — Wrong ppm Scale (Max 80 Instead of 240)

**File:** `app/Services/ColorScienceService.php`, line 83

The current chart tops out at **80 ppm**. The physical card HIGH range goes up to **240 ppm**. As a result:

- A real 70 ppm reading (LOW-end of MEDIUM) hits the top of the system scale and is forced into HIGH.
- A real 45 ppm reading (top of LOW) is mapped to the system's maximum, also classified wrongly.

**Fix required:** The chart must be extended to 240 ppm after recalibration.

---

### Bug 3 — Output Hard-Clamped to 100 ppm in `ColorScienceService.php`

**File:** `app/Services/ColorScienceService.php`, line 200

```php
// CURRENT (WRONG) — clamps output to 100, but HIGH nitrogen goes up to 240
return round(min(100.0, max(0.0, $this->matchColorToValue($hex, self::NITROGEN_COLOR_CHART))), 2);
```

**Effect:** Even if the reference chart is correctly calibrated with hex values mapped to 160–240 ppm, this line will crush every HIGH reading to 100 and classify it as MEDIUM. The clamp must be raised to 240.

**Fix required:**
```php
return round(min(240.0, max(0.0, $this->matchColorToValue($hex, self::NITROGEN_COLOR_CHART))), 2);
```

---

### Bug 4 — Wrong Classification Thresholds in `FertilizerService.php`

**File:** `app/Services/FertilizerService.php`, line 79

Current thresholds:
```php
'nitrogen' => ['low_max' => 20.0, 'high_min' => 40.0],
```

Correct thresholds per BSWM card:
```php
'nitrogen' => ['low_max' => 45.0, 'high_min' => 160.0],
```

**Effect:** Any reading above 40 ppm is currently classified as HIGH. A correct MEDIUM reading of 80–150 ppm will always show as HIGH. This compounds Bugs 1, 2, and 3.

---

## Calibration Procedure

Follow this procedure to capture correct hex values from the physical BSWM nitrogen card.

### Equipment Required
- Physical BSWM soil test kit nitrogen color card
- Calibration box with consistent diffused lighting (same box/bulb used in production)
- Webcam or capture device used in production (not a phone camera)
- Notebook to record hex values per ppm point

### Step-by-Step

1. **Set up the lighting box** — Place the nitrogen color card inside the calibration box. Same position and diffuser as used during actual soil tests. Do not use ambient room light.

2. **Warm up the light** — Turn on the light and wait at least 2 minutes before capturing.

3. **Position the card** — Lay the card flat, perpendicular to the camera.

4. **Capture each color square** — For each ppm level printed on the card:
   - Center the crosshair over that color square.
   - Read the hex value from the live hex readout in the pH test UI (or use a separate color capture tool).
   - Record the hex value next to its ppm label.
   - Repeat 3 times and take the mode or average.

5. **Verify the color progression** — LOW range must go orange → brown (no green or blue). MEDIUM must be clearly green family. HIGH must be clearly blue family. Any overlap between families indicates a capture error — recapture that point.

6. **Update `ColorScienceService.php`** — Replace the `NITROGEN_COLOR_CHART` constant with the new hex → ppm pairs, ordered from lowest to highest ppm.

7. **Update `FertilizerService.php`** — Correct the nitrogen thresholds:
   - `low_max`: 45.0
   - `high_min`: 160.0

8. **Update fertilizer recommendation ranges** in `FertilizerService.php` to match the new scale.

---

## Chart-Point Snapping Rules

Same ceiling-snap rule as pH:

- The first chart point **≥ scientific ppm** is chosen.
- If scientific ppm falls in the gap between LOW and MEDIUM (45–60), it snaps up to the first MEDIUM point (60 ppm).
- If scientific ppm exceeds 240 (out of range), it clamps to 240.

**Example:**
```
Scientific =  18  →  Chart point =  22  (first LOW point >= 18)
Scientific =  45  →  Chart point =  45  (exact LOW max)
Scientific =  50  →  Chart point =  60  (gap zone, snaps to first MEDIUM)
Scientific = 130  →  Chart point = 150  (first MEDIUM point >= 130)
Scientific = 245  →  Chart point = 240  (clamped to HIGH max)
```

---

## Classification Summary (Post-Fix)

| ppm Range     | Classification | Fertilizer Action (indicative)         |
|---------------|----------------|----------------------------------------|
| 15 – 45       | LOW            | High urea application needed           |
| 46 – 59       | (gap)          | Snap to MEDIUM (60), moderate action   |
| 60 – 150      | MEDIUM         | Moderate urea or split application     |
| 151 – 240     | HIGH           | Maintenance dose only                  |

---

## Common Mistakes to Avoid

| Mistake | Effect | Fix |
|---------|--------|-----|
| Using current pink/magenta reference colors without recalibrating | Every nitrogen reading will be wrong | Recalibrate from physical card |
| Capturing nitrogen card under room light | Systematic color shift — orange looks tan, green looks yellow | Always use the calibration box |
| Not extending ppm scale beyond 80 | All MEDIUM and HIGH readings are compressed into a tiny range | Update chart to 240 ppm max |
| Leaving `min(100.0, ...)` clamp in `colorToNitrogenLevel()` | Output is capped at 100 — no HIGH result is ever possible | Change clamp to `min(240.0, ...)` |
| Leaving old wrong entries active during recalibration | CIEDE2000 picks wrong reference | Remove all old entries before adding new ones |

---

*Last updated: 2026-03-10 | Maintained by the system administrator.*
