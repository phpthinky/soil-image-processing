-- ============================================================
-- Soil Fertility Analyzer - Database Setup
-- Compatible with: MariaDB 10.4+ (XAMPP 8.2)
-- ============================================================

-- Drop database if exists (optional - comment out if you want to preserve data)
-- DROP DATABASE IF EXISTS soil_analyzer;

CREATE DATABASE IF NOT EXISTS soil_analyzer
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE soil_analyzer;

-- ============================================================
-- TABLE: users
-- Stores all system users (farmers, professionals, admins)
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    username    VARCHAR(50)     NOT NULL,
    email       VARCHAR(100)    NOT NULL,
    password    VARCHAR(255)    NOT NULL,
    user_type   ENUM('farmer', 'professional', 'admin') NOT NULL DEFAULT 'farmer',
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP(),

    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email    (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: crops
-- Crop database used for recommendation matching
-- Crops are matched against soil NPK/pH readings
-- ============================================================
CREATE TABLE IF NOT EXISTS crops (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    name            VARCHAR(100)    NOT NULL,
    description     TEXT,
    -- pH tolerance range (0-14 scale)
    min_ph          DECIMAL(4,2)    NOT NULL DEFAULT 0.00,
    max_ph          DECIMAL(4,2)    NOT NULL DEFAULT 14.00,
    -- Nitrogen level (ppm): low=<20, medium=20-40, high=>40
    min_nitrogen    DECIMAL(6,2)    NOT NULL DEFAULT 0.00,
    max_nitrogen    DECIMAL(6,2)    NOT NULL DEFAULT 100.00,
    -- Phosphorus level (ppm)
    min_phosphorus  DECIMAL(6,2)    NOT NULL DEFAULT 0.00,
    max_phosphorus  DECIMAL(6,2)    NOT NULL DEFAULT 100.00,
    -- Potassium level (ppm)
    min_potassium   DECIMAL(6,2)    NOT NULL DEFAULT 0.00,
    max_potassium   DECIMAL(6,2)    NOT NULL DEFAULT 100.00,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP(),

    PRIMARY KEY (id),
    UNIQUE KEY uq_crops_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: soil_samples
-- Core table — stores all soil sample records and their
-- webcam-captured color readings + computed NPK/pH values
-- ============================================================
CREATE TABLE IF NOT EXISTS soil_samples (
    id                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    user_id             INT UNSIGNED    NOT NULL,

    -- Sample & farmer info (from analysis.php form)
    sample_name         VARCHAR(150)    NOT NULL,
    location            VARCHAR(200)    DEFAULT NULL,
    sample_date         DATE            NOT NULL,
    farmer_name         VARCHAR(150)    NOT NULL,
    address             VARCHAR(255)    NOT NULL,
    date_tested         DATE            NOT NULL,

    -- Raw soil color captured from webcam (overall)
    color_hex           VARCHAR(7)      NOT NULL DEFAULT '#8B4513',

    -- Per-parameter color readings captured via webcam
    -- (saved by save_soil_parameters.php)
    ph_color_hex            VARCHAR(7)  DEFAULT NULL,
    nitrogen_color_hex      VARCHAR(7)  DEFAULT NULL,
    phosphorus_color_hex    VARCHAR(7)  DEFAULT NULL,
    potassium_color_hex     VARCHAR(7)  DEFAULT NULL,

    -- Computed soil nutrient values (derived from color analysis)
    ph_level            DECIMAL(4,2)    DEFAULT NULL COMMENT 'pH scale 0-14',
    nitrogen_level      DECIMAL(6,2)    DEFAULT NULL COMMENT 'ppm',
    phosphorus_level    DECIMAL(6,2)    DEFAULT NULL COMMENT 'ppm',
    potassium_level     DECIMAL(6,2)    DEFAULT NULL COMMENT 'ppm',

    -- Overall fertility score shown in dashboards (0-100)
    fertility_score     TINYINT UNSIGNED DEFAULT NULL,

    -- Timestamps
    analyzed_at         TIMESTAMP       NULL DEFAULT NULL,
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP(),

    PRIMARY KEY (id),
    CONSTRAINT fk_soil_samples_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    INDEX idx_soil_samples_user_id   (user_id),
    INDEX idx_soil_samples_created   (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED DATA: Default admin account
-- Username: admin | Password: admin123 (plain-text as used
-- in the current login.php; change after first login)
-- ============================================================
INSERT INTO users (username, email, password, user_type) VALUES
('admin', 'admin@soilanalyzer.local', 'admin123', 'admin');

-- ============================================================
-- SEED DATA: Crop database for recommendation engine
-- NPK values in ppm; pH on a 0-14 scale
-- Common crops suited to Philippine agricultural conditions
-- ============================================================
INSERT INTO crops (name, description, min_ph, max_ph, min_nitrogen, max_nitrogen, min_phosphorus, max_phosphorus, min_potassium, max_potassium) VALUES

-- Staple grains
('Rice',
 'Major staple crop; prefers slightly acidic to neutral, waterlogged soil.',
 5.0, 6.5,  20.00, 60.00,  10.00, 30.00,  15.00, 40.00),

('Corn / Maize',
 'Second major cereal; needs well-drained, moderately fertile soil.',
 5.8, 7.0,  25.00, 70.00,  15.00, 40.00,  20.00, 50.00),

-- Vegetables
('Tomato',
 'Warm-season vegetable requiring rich, well-drained loamy soil.',
 6.0, 7.0,  30.00, 80.00,  20.00, 50.00,  25.00, 60.00),

('Eggplant (Talong)',
 'Thrives in warm climate with fertile, well-drained soil.',
 5.5, 6.8,  25.00, 65.00,  15.00, 40.00,  20.00, 50.00),

('Ampalaya (Bitter Gourd)',
 'Tropical vine; tolerates a wide pH range, needs good drainage.',
 5.5, 7.0,  20.00, 55.00,  10.00, 35.00,  15.00, 45.00),

('Kangkong (Water Spinach)',
 'Fast-growing leafy vegetable; tolerates slightly acidic, moist conditions.',
 5.5, 7.0,  25.00, 70.00,  10.00, 30.00,  15.00, 40.00),

('Pechay (Bok Choy)',
 'Cool-season leafy vegetable; prefers fertile, moist, well-drained soil.',
 6.0, 7.0,  30.00, 75.00,  20.00, 45.00,  20.00, 50.00),

('Sitaw (String Beans)',
 'Warm-season legume; fixes nitrogen; prefers slightly acidic to neutral soil.',
 6.0, 7.5,  10.00, 40.00,  15.00, 40.00,  20.00, 50.00),

-- Root crops
('Sweet Potato (Camote)',
 'Tolerant of poor soils; prefers slightly acidic, well-drained sandy loam.',
 5.0, 6.5,  10.00, 35.00,  10.00, 30.00,  20.00, 55.00),

('Cassava',
 'Drought-tolerant root crop; grows in low-fertility, well-drained soils.',
 5.0, 6.5,  10.00, 30.00,   5.00, 25.00,  15.00, 45.00),

('Gabi (Taro)',
 'Tropical root crop; prefers moist, fertile, slightly acidic soil.',
 5.5, 7.0,  20.00, 55.00,  10.00, 30.00,  15.00, 40.00),

-- Fruit crops
('Banana (Saging)',
 'Tropical fruit; needs deep, fertile, well-drained loam with high organic matter.',
 5.5, 7.0,  30.00, 80.00,  20.00, 50.00,  30.00, 80.00),

('Papaya',
 'Fast-growing tropical fruit; prefers rich, well-drained, slightly acidic soil.',
 6.0, 7.0,  25.00, 65.00,  15.00, 40.00,  20.00, 55.00),

('Mango',
 'Long-season tropical tree fruit; tolerates a wide pH and drought conditions.',
 5.5, 7.5,  15.00, 50.00,  10.00, 35.00,  15.00, 50.00),

-- Cash/industrial crops
('Sugarcane',
 'High-input cash crop; needs fertile, well-drained loamy soil.',
 6.0, 7.5,  30.00, 80.00,  20.00, 55.00,  25.00, 70.00),

('Peanut (Mani)',
 'Legume; fixes nitrogen; needs light, well-drained, slightly acidic sandy loam.',
 5.8, 7.0,  10.00, 35.00,  15.00, 40.00,  15.00, 45.00),

('Coffee (Arabica/Robusta)',
 'Shade-grown tropical crop; needs well-drained, fertile, acidic volcanic soil.',
 5.0, 6.5,  20.00, 55.00,  10.00, 30.00,  15.00, 45.00),

('Coconut',
 'Multipurpose palm; tolerates a wide range of soils; prefers sandy loam.',
 5.5, 8.0,  15.00, 45.00,  10.00, 30.00,  20.00, 60.00);

-- ============================================================
-- VERIFICATION: Show created tables
-- ============================================================
SHOW TABLES;