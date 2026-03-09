# pH Color Chart Calibration Guide

## Overview

The soil pH analyzer matches a captured strip color against a set of reference hex values using CIEDE2000 delta-E color distance. Each indicator solution (CPR, BCG, BTB) has its own reference chart stored in the `ph_color_charts` database table. This guide documents how those reference values were obtained and how to update them when re-calibration is needed.

---

## Indicator Solutions and pH Ranges

| Indicator | Full Name                           | pH Range | Card Points               | BSWM Step |
|-----------|-------------------------------------|----------|---------------------------|-----------|
| **CPR**   | Cresol Red + Phenolphthalein        | 4.8–6.0  | 4.8, 5.0, 5.2, 5.4, 5.6, 5.8, 6.0 | Step 1 (always) |
| **BCG**   | Bromocresol Green                   | 4.0–5.4  | 4.0, 4.2, 4.4, 4.6, 4.8, 5.0, 5.2, 5.4 | Step 2 (acidic soils, CPR ≤ 5.4) |
| **BTB**   | Bromothymol Blue                    | 6.0–7.6  | 6.0, 6.4, 6.8, 7.2, 7.6  | Step 2 (near-neutral, CPR > 5.8) |

---

## Color Progressions (What to Expect on the Card)

### CPR (Step 1) — Amber-Yellow to Dark Red
```
pH 4.8  →  Orange-amber        (#FF8800)
pH 5.0  →  Tan/caramel         (#D2A65A)
pH 5.2  →  Yellow-orange       (#FFC800)
pH 5.4  →  Brown-red           (#B0622D)
pH 5.6  →  Yellow-olive        (#EDE800)
pH 5.8  →  Dark red            (#9D2529)
pH 6.0  →  Deep burgundy       (#7E2938)
```

### BCG (Step 2, Acidic) — Yellow-Green to Teal-Blue
```
pH 4.0  →  Yellow-green        (#CABB05)
pH 4.2  →  Yellow-green        (#C1BE07)
pH 4.4  →  Yellow-green        (#B6C209)
pH 4.6  →  Olive-green         (#80B21B)
pH 4.8  →  Green               (#3C9B32)
pH 5.0  →  Teal-green          (#1A8D54)
pH 5.2  →  Teal                (#008071)
pH 5.4  →  Teal-blue           (#007382)
```
> **Calibration status:** ✅ Measured from physical BSWM BCG card.

### BTB (Step 2, Near-Neutral) — Yellow to Blue
```
pH 6.0  →  Bright yellow       (#DDDD00)
pH 6.4  →  Yellow-green        (#88BB00)
pH 6.8  →  Green               (#33AA44)
pH 7.2  →  Teal                (#009977)
pH 7.6  →  Blue                (#0066CC)
```
> **Calibration status:** ⚠️ Approximate values only — physical BTB card not yet measured.
> Recalibrate following the procedure below before relying on BTB readings in production.

---

## Calibration Procedure

Follow this procedure each time you remeasure a card, or when adding a new indicator.

### Equipment Required
- Physical BSWM soil test kit color card (CPR, BCG, or BTB card)
- Calibration box with consistent diffused lighting (same box/bulb used in production)
- Webcam or capture device used in production (not a phone camera)
- Access to the admin panel → **pH Color Charts**

### Step-by-Step

1. **Set up the lighting box** — Place the color card inside the calibration box. Ensure the light source and diffuser are in the same position as during actual soil tests. Do not use ambient room light.

2. **Warm up the light** — Turn on the light and wait at least 2 minutes before capturing. Fluorescent and LED bulbs shift color temperature when cold.

3. **Position the card** — Lay the card flat, perpendicular to the camera. The card surface should fill most of the capture frame.

4. **Capture each card point** — For each discrete pH value printed on the card:
   - Point the capture region at that specific color square.
   - Use the live hex readout (the "captured color" box in the pH test UI) to read the hex value.
   - Record the hex value next to its pH label.
   - Repeat 3 times and take the mode (most common) or average if readings are consistent.

5. **Verify the color progression** — The hex values must form a monotonic visual gradient. For BCG: yellow-green → green → teal-green → teal-blue with no sudden hue reversals. If a point looks out of order, recapture it.

6. **Update the database** — In Admin → pH Color Charts:
   - Select the indicator tab (CPR / BCG / BTB).
   - Deactivate or delete old entries for the indicator being recalibrated.
   - Add the new entries one by one (indicator, pH value, hex color).
   - Verify the color swatches display in the correct ascending order.

---

## Chart-Point Snapping Rules

After the CIEDE2000 delta-E algorithm produces a continuous scientific pH (e.g., 4.73), the system snaps it to the nearest printed card point using **ceiling-snap**:

- The first card point **≥ scientific pH** is chosen.
- This guarantees: **chart_ph ≥ scientific_ph** always.
- If scientific pH exceeds the highest card point (out-of-range), it clamps to the highest point.

**Example (BCG):**
```
Scientific = 4.05  →  Chart point = 4.2  (first BCG point >= 4.05)
Scientific = 4.80  →  Chart point = 4.8  (exact match)
Scientific = 5.41  →  Chart point = 5.4  (clamped to BCG max)
```

---

## Why Multiple Hex Entries Per pH Point?

Adding 2–3 hex values for the same pH point improves matching accuracy when:
- The physical card has slight color variation across its surface.
- Lighting conditions introduce minor hue drift.
- The sample color falls between two printed squares.

The CIEDE2000 algorithm considers all active entries and picks the globally closest match. More reference points per pH level = smaller gaps in color space = more accurate interpolation.

> Use the **Activate/Deactivate** toggle in the admin UI to test individual entries without deleting them. Only active entries are used in matching.

---

## Common Mistakes to Avoid

| Mistake | Effect | Fix |
|---------|--------|-----|
| Capturing colors under room light (not the calibration box) | Systematic hue shift — all pH readings off by a fixed amount | Always use the calibration box |
| Using a different camera than production | Different color response curves | Use the exact same webcam |
| Adding colors from different lighting sessions without recalibrating all points | Non-monotonic gradient → wrong matching | Recalibrate the entire indicator in one session |
| Having old and new entries active for the same indicator | CIEDE2000 finds old wrong reference → incorrect pH | Deactivate all old entries before adding new ones |
| Using only 1 entry per pH point for a hue that shifts across the card | Low-pH colors bleed into high-pH matches | Add 2–3 representative entries for wide-spread card points |

---

## Troubleshooting

### Two different strip colors both match the same pH point
- The reference colors in the DB for that indicator are not covering the full visual range of the strip.
- Open Admin → pH Color Charts and check if the swatches for that indicator form a clear gradient.
- If not, the entries need recalibration. Follow the Calibration Procedure above.

### Chart pH is much higher than scientific pH
- Usually means the CIEDE2000 match found a wrong reference color (wrong hue family in the DB).
- Check the DB entries for that indicator — old or incorrect entries may be active.
- Deactivate any entries that don't belong to the correct color progression.

### BCG always returns pH 4.0 or 5.4 (stuck at extremes)
- The reference colors at the intermediate points may be too far in CIEDE2000 space from the actual strip colors.
- Recalibrate the intermediate BCG points (4.2–5.2) under the production lighting box.

---

*Last updated: 2026-03-09 | Maintained by the system administrator.*
