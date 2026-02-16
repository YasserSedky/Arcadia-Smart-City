USE arcadia_smart_city;

-- Mall retail units
CREATE TABLE IF NOT EXISTS mall_units (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL UNIQUE,
  level VARCHAR(20) DEFAULT NULL,
  area_sqm DECIMAL(10,2) DEFAULT NULL,
  type ENUM('shop','barber_male','barber_female','restaurant','cafe','kiosk') NOT NULL DEFAULT 'shop'
);

CREATE TABLE IF NOT EXISTS mall_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name_ar VARCHAR(120) NOT NULL,
  UNIQUE KEY uk_name (name_ar)
);

-- Tenants assigned to units
CREATE TABLE IF NOT EXISTS mall_tenants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  unit_id INT NOT NULL UNIQUE,
  name_ar VARCHAR(200) NOT NULL,
  category_id INT DEFAULT NULL,
  phone VARCHAR(50) DEFAULT NULL,
  start_date DATE DEFAULT NULL,
  end_date DATE DEFAULT NULL,
  FOREIGN KEY (unit_id) REFERENCES mall_units(id),
  FOREIGN KEY (category_id) REFERENCES mall_categories(id)
);

-- Entertainment venues (cinemas, game halls)
CREATE TABLE IF NOT EXISTS mall_venues (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name_ar VARCHAR(200) NOT NULL,
  type ENUM('cinema','games','events') NOT NULL,
  capacity INT DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS mall_bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  venue_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME DEFAULT NULL,
  status ENUM('scheduled','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  notes VARCHAR(400) DEFAULT NULL,
  FOREIGN KEY (venue_id) REFERENCES mall_venues(id)
);

-- Seed some categories
INSERT IGNORE INTO mall_categories(name_ar) VALUES ('أزياء'),('إلكترونيات'),('مطاعم'),('كافيهات'),('حلاقة'),('خدمات');



