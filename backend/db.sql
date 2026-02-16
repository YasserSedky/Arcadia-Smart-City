-- Arcadia Smart City schema (compatible with XAMPP)
CREATE DATABASE IF NOT EXISTS arcadia_smart_city
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- IMPORTANT: switch to the database
USE arcadia_smart_city;

-- ========================
-- Roles Table
-- ========================
CREATE TABLE IF NOT EXISTS roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(64) UNIQUE NOT NULL,
  name_ar VARCHAR(128) NOT NULL
);

INSERT IGNORE INTO roles (id, code, name_ar) VALUES
  (1,'super_admin','المدير الرئيسي'),
  (2,'hospital_admin','مدير المستشفى'),
  (3,'mall_admin','مدير المول'),
  (4,'school_admin','مدير المدرسة'),
  (5,'sports_admin','مدير النادي الرياضي'),
  (6,'conference_admin','مدير قاعة المؤتمرات'),
  (7,'bank_admin','مدير البنك'),
  (8,'security_admin','مدير الأمن والطوارئ'),
  (9,'residential_admin','مدير السكن'),
  (10,'services_admin','مدير الخدمات'),
  (20,'resident','مقيم'),
  (21,'maintenance_worker','عامل صيانة'),
  (22,'doctor','طبيب'),
  (23,'nurse','ممرض'),
  (24,'hospital_staff','عامل بالمستشفى'),
  (25,'admin_staff','إداري');

-- ========================
-- Buildings Table
-- ========================
CREATE TABLE IF NOT EXISTS buildings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type ENUM('apartment_block','villa','hq','gate') NOT NULL,
  label VARCHAR(64) NOT NULL,
  UNIQUE KEY unique_building (type, label)
);

-- ========================
-- Units Table
-- ========================
CREATE TABLE IF NOT EXISTS units (
  id INT AUTO_INCREMENT PRIMARY KEY,
  building_id INT NOT NULL,
  unit_number VARCHAR(32) NOT NULL,
  unit_code VARCHAR(64) NOT NULL UNIQUE,
  FOREIGN KEY (building_id) REFERENCES buildings(id)
);

-- ========================
-- Users Table
-- ========================
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(200) NOT NULL,
  phone VARCHAR(32) NOT NULL,
  email VARCHAR(200) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role_id INT NOT NULL,
  unit_id INT DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_phone_unit (phone, unit_id),
  UNIQUE KEY uk_email (email),
  FOREIGN KEY (role_id) REFERENCES roles(id),
  FOREIGN KEY (unit_id) REFERENCES units(id)
);

-- ========================
-- Seed Data Procedure
-- ========================
DELIMITER $$

DROP PROCEDURE IF EXISTS seed_residential $$
CREATE PROCEDURE seed_residential()
BEGIN
  DECLARE b INT DEFAULT 1;
  DECLARE v INT DEFAULT 1;

  -- Insert apartment blocks
  WHILE b <= 20 DO
    INSERT IGNORE INTO buildings(type,label)
    VALUES('apartment_block', CONCAT('B', LPAD(b,2,'0')));
    SET b = b + 1;
  END WHILE;

  -- Insert villas
  WHILE v <= 80 DO
    INSERT IGNORE INTO buildings(type,label)
    VALUES('villa', CONCAT('V', LPAD(v,3,'0')));
    SET v = v + 1;
  END WHILE;

  -- Insert units for apartment blocks (6 per block)
  INSERT IGNORE INTO units(building_id, unit_number, unit_code)
  SELECT b.id, CONCAT('A', n.num), CONCAT(b.label,'-A', n.num)
  FROM buildings b
  JOIN (
    SELECT 1 AS num UNION SELECT 2 UNION SELECT 3 UNION
    SELECT 4 UNION SELECT 5 UNION SELECT 6
  ) n
  WHERE b.type = 'apartment_block';

  -- Insert villas as single-unit
  INSERT IGNORE INTO units(building_id, unit_number, unit_code)
  SELECT b.id, b.label, b.label
  FROM buildings b
  WHERE b.type = 'villa';
END $$
DELIMITER ;

-- ========================
-- Execute the procedure
-- ========================
CALL seed_residential();

-- ========================
-- Super Admin Account
-- ========================
INSERT INTO users(full_name, phone, email, password_hash, role_id, unit_id)
VALUES('Super Admin','0000000000','admin@arcadia.local',
  '$2y$10$JgF2mA9l0q5r5z8T53nEpe5j4.0m9v8H1d8Gm5xH2mXkT2s9qjVva', -- password: Admin@123
  1, NULL)
ON DUPLICATE KEY UPDATE email = VALUES(email);

