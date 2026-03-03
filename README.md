# Soil Fertility Analyzer

A web-based soil analysis system developed for the **Office of the Municipal Agriculturist (OMA)**. It uses a webcam to capture color readings from soil indicator test strips, estimates NPK and pH levels from those colors, and recommends suitable crops based on the results.

---

## Features

- Webcam-based soil color capture for pH, Nitrogen, Phosphorus, and Potassium
- Automatic computation of soil nutrient levels from RGB color values
- Fertility score calculation (0–100%) per sample
- Crop recommendation engine matched against a built-in crop database
- Role-based access: Admin and regular users (Farmer / Agricultural Professional)
- Admin panel for user management (add, edit, delete)
- Full sample history with per-sample detailed results

---

## How It Works

1. A farmer or technician adds a soil sample record (name, location, farmer info, dates)
2. Soil indicator test strips are placed under a webcam — one strip per parameter (pH, N, P, K)
3. The system captures the strip color via the browser's webcam API
4. The sampled RGB color is saved to the database via `save_soil_parameters.php`
5. Once all 4 colors are captured, the system computes numeric values using color-to-parameter formulas:
   - **pH** — strip hue mapped on a red (acidic) → blue (alkaline) scale (pH 4–9)
   - **Nitrogen** — green dominance and darkness mapped to 0–100 ppm
   - **Phosphorus** — blue hue intensity mapped to 0–100 ppm
   - **Potassium** — warm/purple saturation mapped to 0–100 ppm
6. A fertility score (0–100%) is calculated from how close each value is to the optimal range
7. The crops table is queried to find crops whose NPK/pH tolerance matches the soil readings
8. Results and crop recommendations are displayed on `results.php`

---

## Project Structure

```
soil-image-processing/
├── config.php                  # DB connection, auth helpers, color conversion functions
├── login.php                   # Login page
├── register.php                # User registration
├── admin_dashboard.php         # Admin overview (user count, sample count, crop count)
├── user_dashboard.php          # User overview (recent samples, fertility scores)
├── analysis.php                # Form to add a new soil sample
├── results.php                 # Sample list + detail view, webcam capture UI, crop recommendations
├── save_soil_parameters.php    # JSON API endpoint — saves captured color per parameter
├── users.php                   # Admin: full user CRUD
├── database.sql                # Full database schema + seed data
├── includes/
│   ├── header.php              # HTML head, Bootstrap 5 navbar, sidebar
│   └── footer.php              # Closing layout tags, footer bar, Bootstrap JS
└── SOURCE-CODES.docx           # Original source code documentation
```

---

## Database

Three tables — import `database.sql` to create them all:

| Table | Purpose |
|---|---|
| `users` | System accounts (farmer, professional, admin) |
| `soil_samples` | Soil records with color readings, computed NPK/pH values, and fertility scores |
| `crops` | Crop database with NPK/pH tolerance ranges used for recommendations |

### `soil_samples` key columns

| Column | Description |
|---|---|
| `ph_color_hex` / `nitrogen_color_hex` / `phosphorus_color_hex` / `potassium_color_hex` | Hex color captured from webcam per parameter |
| `ph_level` | Computed pH value (0–14) |
| `nitrogen_level` / `phosphorus_level` / `potassium_level` | Computed values in ppm |
| `fertility_score` | Overall score 0–100% |
| `analyzed_at` | Timestamp set when computation completes |

---

## Installation

### Requirements
- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.3+
- A web server (Apache / Nginx) with a webcam-accessible browser

### Steps

**1. Clone the repository**
```bash
git clone <repo-url>
cd soil-image-processing
```

**2. Create the database**
```bash
mysql -u root -p < database.sql
```

Or import `database.sql` via phpMyAdmin.

**3. Configure the database connection**

Open `config.php` and update the credentials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'soil_analyzer');
define('DB_USER', 'root');
define('DB_PASS', '');
```

**4. Place files in your web server root**
```bash
cp -r . /var/www/html/soil-analyzer/
```

**5. Log in with the default admin account**

| Field | Value |
|---|---|
| Username | `admin` |
| Password | `admin123` |

> **Change the password immediately after first login via the Users page.**

---

## User Roles

| Role | Access |
|---|---|
| `admin` | Full access — user management, all samples, admin dashboard |
| `professional` | Soil analysis, results, crop recommendations |
| `farmer` | Soil analysis, results, crop recommendations |

---

## Soil Analysis Workflow

```
login.php
   └─► analysis.php          (fill in sample + farmer details)
         └─► results.php     (webcam capture for pH, N, P, K strips)
               └─► save_soil_parameters.php  (AJAX — saves each color)
                     └─► results.php         (auto-computes values, shows recommendations)
```

---

## Crop Recommendations

The `crops` table ships with **18 pre-loaded crops** common to Philippine agriculture:

Rice, Corn, Tomato, Eggplant (Talong), Ampalaya, Kangkong, Pechay, Sitaw, Sweet Potato (Camote), Cassava, Gabi (Taro), Banana (Saging), Papaya, Mango, Sugarcane, Peanut (Mani), Coffee, Coconut

Each crop has defined `min`/`max` ranges for pH, N, P, and K. On the results page, crops are ranked by **match score (0–4)** — the number of parameters within that crop's tolerance range.

---

## Security Notes

- The current login compares passwords in **plain text** (as originally documented). For production use, replace with `password_hash()` on registration and `password_verify()` on login.
- `save_soil_parameters.php` validates session, sample ownership, parameter name, hex format, and RGB range before writing to the database.
- All user-facing output is passed through `htmlspecialchars()` to prevent XSS.

---

## Dependencies (CDN — no npm required)

| Library | Version | Purpose |
|---|---|---|
| Bootstrap | 5.3.2 | Layout, components, responsive grid |
| Font Awesome | 6.5.0 | Icons throughout the UI |
