-- إنشاء قاعدة البيانات واستخدامها
CREATE DATABASE IF NOT EXISTS arcadia_smart_city
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;
USE arcadia_smart_city;

-- جدول فئات المحلات (Mall Categories)
CREATE TABLE IF NOT EXISTS mall_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name_ar VARCHAR(255) NOT NULL,
  UNIQUE KEY uq_mall_categories_name (name_ar)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- جدول وحدات المول (Mall Units)
CREATE TABLE IF NOT EXISTS mall_units (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL UNIQUE,
  level VARCHAR(100),
  area_sqm DECIMAL(10,2),
  type ENUM('shop','barber_male','barber_female','restaurant','cafe','kiosk','cinema','gaming','furniture','electronics') 
    NOT NULL DEFAULT 'shop',
  status ENUM('available','rented','maintenance') DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- جدول المستأجرين (Tenants)
CREATE TABLE IF NOT EXISTS mall_tenants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  unit_id INT NOT NULL,
  name_ar VARCHAR(255) NOT NULL,
  category_id INT,
  phone VARCHAR(20),
  FOREIGN KEY (unit_id) REFERENCES mall_units(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (category_id) REFERENCES mall_categories(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- جدول المرافق الترفيهية داخل المول (Venues)
CREATE TABLE IF NOT EXISTS mall_venues (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name_ar VARCHAR(255) NOT NULL,
  type ENUM('cinema','games','event') NOT NULL,
  capacity INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- ✅ الآن ندخل البيانات بعد التأكد من وجود الجداول أعلاه
-- -------------------------------------------------------------

-- إدخال الفئات (التصنيفات) مع تجاهل التكرار
INSERT IGNORE INTO mall_categories (name_ar) VALUES 
('ملابس نسائية'),
('ملابس رجالية'),
('مطاعم'),
('كافيهات'),
('حلاق رجالي'),
('كوافير حريمي'),
('اكسسوارات'),
('أجهزة كهربائية'),
('أثاث منزلي'),
('ترفيه');

-- إدخال الوحدات (المحلات والمرافق)
INSERT IGNORE INTO mall_units (code, level, area_sqm, type) VALUES 
-- Women's Fashion
('W101', 'First Floor', 120.00, 'shop'),
('W102', 'First Floor', 150.00, 'shop'),
('W103', 'First Floor', 100.00, 'shop'),

-- Men's Fashion
('M101', 'First Floor', 120.00, 'shop'),
('M102', 'First Floor', 140.00, 'shop'),

-- Restaurants
('R101', 'Ground Floor', 200.00, 'restaurant'),
('R102', 'Ground Floor', 180.00, 'restaurant'),
('R103', 'Ground Floor', 150.00, 'restaurant'),

-- Cafes
('C101', 'Ground Floor', 120.00, 'cafe'),
('C102', 'Ground Floor', 100.00, 'cafe'),

-- Barbers & Beauty Salons
('B101', 'Second Floor', 80.00, 'barber_male'),
('B102', 'Second Floor', 100.00, 'barber_female'),

-- Accessories
('A101', 'First Floor', 60.00, 'shop'),
('A102', 'First Floor', 70.00, 'shop'),

-- Electronics
('E101', 'Second Floor', 200.00, 'electronics'),
('E102', 'Second Floor', 180.00, 'electronics'),

-- Furniture
('F101', 'Third Floor', 300.00, 'furniture'),
('F102', 'Third Floor', 250.00, 'furniture');

-- إدخال المرافق الترفيهية (صالة سينما، ألعاب، الخ)
INSERT IGNORE INTO mall_venues (name_ar, type, capacity) VALUES 
('صالة سينما 1', 'cinema', 120),
('صالة سينما 2', 'cinema', 120),
('صالة سينما VIP', 'cinema', 50),
('صالة البولينج', 'games', 100),
('صالة البلياردو', 'games', 50),
('قاعة الألعاب الإلكترونية', 'games', 200);

-- إدخال المستأجرين (Tenants)
INSERT IGNORE INTO mall_tenants (unit_id, name_ar, category_id, phone) VALUES 
((SELECT id FROM mall_units WHERE code = 'W101'), 'زارا للأزياء النسائية', (SELECT id FROM mall_categories WHERE name_ar = 'ملابس نسائية'), '0501234567'),
((SELECT id FROM mall_units WHERE code = 'M101'), 'بيير كاردان للأزياء الرجالية', (SELECT id FROM mall_categories WHERE name_ar = 'ملابس رجالية'), '0501234568'),
((SELECT id FROM mall_units WHERE code = 'R101'), 'مطعم الشرق', (SELECT id FROM mall_categories WHERE name_ar = 'مطاعم'), '0501234569'),
((SELECT id FROM mall_units WHERE code = 'C101'), 'ستاربكس', (SELECT id FROM mall_categories WHERE name_ar = 'كافيهات'), '0501234570'),
((SELECT id FROM mall_units WHERE code = 'B101'), 'صالون الأناقة للرجال', (SELECT id FROM mall_categories WHERE name_ar = 'حلاق رجالي'), '0501234571'),
((SELECT id FROM mall_units WHERE code = 'B102'), 'صالون لمسات للسيدات', (SELECT id FROM mall_categories WHERE name_ar = 'كوافير حريمي'), '0501234572'),
((SELECT id FROM mall_units WHERE code = 'A101'), 'اكسسوارات الأميرة', (SELECT id FROM mall_categories WHERE name_ar = 'اكسسوارات'), '0501234573'),
((SELECT id FROM mall_units WHERE code = 'E101'), 'اكسترا للإلكترونيات', (SELECT id FROM mall_categories WHERE name_ar = 'أجهزة كهربائية'), '0501234574'),
((SELECT id FROM mall_units WHERE code = 'F101'), 'هوم سنتر', (SELECT id FROM mall_categories WHERE name_ar = 'أثاث منزلي'), '0501234575');
