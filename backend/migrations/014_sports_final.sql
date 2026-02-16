USE arcadia_smart_city;

-- Role for sports club management
INSERT IGNORE INTO roles(code, name_ar) VALUES('sports_admin','مدير النادي الرياضي');

-- Sports facilities types
CREATE TABLE IF NOT EXISTS sports_facility_types (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name_ar VARCHAR(128) NOT NULL,
  description TEXT,
  icon VARCHAR(64) DEFAULT NULL,
  location_zone VARCHAR(50) DEFAULT NULL, -- منطقة الموقع (شمال، جنوب، etc)
  maintenance_day TINYINT DEFAULT NULL -- يوم الصيانة الدورية (0=الأحد to 6=السبت)
);

-- Insert default facility types
INSERT INTO sports_facility_types(name_ar, icon, description, location_zone) VALUES 
('ملعب كرة قدم', 'dribbble', 'ملعب كرة قدم عشب صناعي', 'المنطقة الشمالية'),
('مسبح', 'water', 'مسبح أولمبي مغطى ومكيف', 'المنطقة الغربية'),
('صالة رياضية', 'bicycle', 'صالة متعددة الأغراض مع أجهزة', 'المبنى الرئيسي'),
('ملعب تنس', 'circle', 'ملعب تنس أرضي', 'المنطقة الجنوبية'),
('ملعب سلة', 'basketball', 'ملعب كرة سلة قانوني', 'المنطقة الشرقية'),
('صالة سكواش', 'square', 'ملاعب اسكواش مغلقة ومكيفة', 'المبنى الرئيسي'),
('ملعب كرة طائرة', 'circle-half', 'ملعب كرة طائرة قانوني', 'المنطقة الجنوبية'),
('صالة تدريب شخصي', 'person-arms-up', 'صالات خاصة للتدريب الشخصي', 'المبنى الرئيسي');

-- Sports facilities
CREATE TABLE IF NOT EXISTS sports_facilities (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type_id INT NOT NULL,
  name_ar VARCHAR(255) NOT NULL,
  capacity INT NOT NULL DEFAULT 0,
  price_per_hour DECIMAL(10,2) NOT NULL DEFAULT 0,
  description TEXT,
  features TEXT, -- مميزات إضافية (JSON)
  requirements TEXT, -- متطلبات الحجز أو الاستخدام
  status ENUM('available','maintenance','closed','reserved') DEFAULT 'available',
  maintenance_notes TEXT,
  last_maintenance_date DATE DEFAULT NULL,
  next_maintenance_date DATE DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (type_id) REFERENCES sports_facility_types(id)
);

-- Facility working hours (with special hours support)
CREATE TABLE IF NOT EXISTS sports_facility_hours (
  id INT AUTO_INCREMENT PRIMARY KEY,
  facility_id INT NOT NULL,
  day_of_week TINYINT NOT NULL, -- 0=Sunday to 6=Saturday
  opens_at TIME NOT NULL,
  closes_at TIME NOT NULL,
  is_special_hours BOOLEAN DEFAULT FALSE, -- للساعات الخاصة (رمضان، العطل، etc)
  special_date DATE DEFAULT NULL, -- تاريخ محدد للساعات الخاصة
  notes VARCHAR(255) DEFAULT NULL,
  UNIQUE KEY uq_facility_day_special (facility_id, day_of_week, special_date),
  FOREIGN KEY (facility_id) REFERENCES sports_facilities(id)
);

-- Sports activities/classes
CREATE TABLE IF NOT EXISTS sports_activities (
  id INT AUTO_INCREMENT PRIMARY KEY,
  facility_id INT NOT NULL,
  name_ar VARCHAR(255) NOT NULL,
  instructor_name VARCHAR(255),
  instructor_phone VARCHAR(20),
  instructor_speciality VARCHAR(100),
  capacity INT NOT NULL DEFAULT 0,
  min_capacity INT DEFAULT 1, -- الحد الأدنى للبدء
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  schedule TEXT,
  description TEXT,
  requirements TEXT, -- متطلبات المشاركة
  level ENUM('beginner','intermediate','advanced','all') DEFAULT 'all',
  age_min INT DEFAULT NULL,
  age_max INT DEFAULT NULL,
  gender ENUM('male','female','mixed') DEFAULT 'mixed',
  status ENUM('draft','active','cancelled','completed','full') DEFAULT 'draft',
  starts_at DATETIME NOT NULL,
  ends_at DATETIME,
  registration_closes_at DATETIME,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (facility_id) REFERENCES sports_facilities(id)
);

-- Facility bookings
CREATE TABLE IF NOT EXISTS sports_bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  facility_id INT NOT NULL,
  user_id INT NOT NULL,
  title VARCHAR(255) DEFAULT NULL, -- عنوان اختياري للحجز
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NOT NULL,
  num_persons INT DEFAULT 1,
  status ENUM('pending','confirmed','cancelled','completed','no_show') DEFAULT 'pending',
  payment_status ENUM('unpaid','paid','refunded') DEFAULT 'unpaid',
  total_price DECIMAL(10,2) NOT NULL DEFAULT 0,
  notes TEXT,
  cancellation_reason TEXT,
  cancelled_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (facility_id) REFERENCES sports_facilities(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Activity registrations
CREATE TABLE IF NOT EXISTS sports_registrations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  activity_id INT NOT NULL,
  user_id INT NOT NULL,
  emergency_contact VARCHAR(100), -- رقم للطوارئ
  health_conditions TEXT, -- حالات صحية يجب مراعاتها
  status ENUM('pending','active','cancelled','completed','suspended') DEFAULT 'pending',
  payment_status ENUM('unpaid','paid','refunded') DEFAULT 'unpaid',
  attendance_count INT DEFAULT 0, -- عدد مرات الحضور
  last_attendance DATE DEFAULT NULL,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_activity_user (activity_id, user_id),
  FOREIGN KEY (activity_id) REFERENCES sports_activities(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Attendance tracking for activities
CREATE TABLE IF NOT EXISTS sports_attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  registration_id INT NOT NULL,
  activity_id INT NOT NULL,
  user_id INT NOT NULL,
  attended_at DATETIME NOT NULL,
  notes VARCHAR(255) DEFAULT NULL,
  FOREIGN KEY (registration_id) REFERENCES sports_registrations(id),
  FOREIGN KEY (activity_id) REFERENCES sports_activities(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insert sample facilities with more details
INSERT INTO sports_facilities (type_id, name_ar, capacity, price_per_hour, description, features, requirements) VALUES
(1, 'ملعب كرة قدم الرئيسي', 22, 1000, 'ملعب عشب صناعي مع إضاءة ليلية وغرف تبديل ملابس', 
   '{"lighting": true, "showers": true, "lockers": true, "parking": true}',
   'يجب ارتداء الحذاء الرياضي المناسب. يمنع استخدام الملعب في حالة الأمطار.'),
(2, 'المسبح الأولمبي', 50, 150, 'مسبح مغطى ومكيف مع غرف تبديل ملابس وخزائن',
   '{"heated": true, "indoorPool": true, "lifeguard": true, "showers": true}',
   'إلزامية لبس غطاء الرأس وملابس السباحة المناسبة. يجب الاستحمام قبل السباحة.'),
(3, 'صالة اللياقة الرئيسية', 40, 125, 'صالة مجهزة بأحدث الأجهزة الرياضية مع مدربين متخصصين',
   '{"cardioArea": true, "weightsArea": true, "trainers": true, "waterDispenser": true}',
   'يجب ارتداء ملابس رياضية مناسبة وحذاء رياضي نظيف. يمنع التصوير داخل الصالة.'),
(4, 'ملعب التنس A', 4, 400, 'ملعب تنس مع إضاءة ليلية وأرضية احترافية',
   '{"lighting": true, "equipment": true, "seating": true}',
   'يجب استخدام حذاء خاص بملاعب التنس. يمكن استئجار المضارب من مكتب الحجز.'),
(5, 'ملعب السلة الرئيسي', 10, 500, 'ملعب كرة سلة قانوني مع مدرجات للمشاهدين',
   '{"lighting": true, "scoreboard": true, "seating": true}',
   'يجب ارتداء حذاء رياضي مناسب. يمكن استئجار الكرات من مكتب الحجز.'),
(6, 'ملعب سكواش 1', 2, 300, 'ملعب اسكواش مكيف مع إضاءة LED',
   '{"lighting": true, "airConditioning": true, "equipment": true}',
   'يجب استخدام حذاء رياضي بنعل نظيف. يمكن استئجار المضارب والكرات.'),
(7, 'صالة تدريب شخصي A', 3, 250, 'صالة خاصة للتدريب الشخصي مع مدرب',
   '{"privateTrainer": true, "equipment": true, "airConditioning": true}',
   'يجب الحجز مع مدرب معتمد. الحد الأقصى 3 أشخاص في نفس الوقت.');

-- Insert default working hours for all facilities
INSERT INTO sports_facility_hours (facility_id, day_of_week, opens_at, closes_at) 
SELECT 
  f.id,
  d.day_num,
  CASE 
    WHEN d.day_num = 5 THEN '14:00' -- الجمعة
    ELSE '06:00'
  END,
  CASE 
    WHEN d.day_num = 5 THEN '23:00' -- الجمعة
    ELSE '23:00'
  END
FROM sports_facilities f
CROSS JOIN (
  SELECT 0 as day_num UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6
) d;

-- Insert sample activities
INSERT INTO sports_activities (
  facility_id, name_ar, instructor_name, instructor_speciality,
  capacity, price, schedule, description, level, age_min, age_max,
  gender, status, starts_at, ends_at, registration_closes_at
) VALUES
(3, 'تدريب لياقة بدنية جماعي', 'أحمد محمد', 'مدرب لياقة معتمد',
 15, 200, 'الأحد والثلاثاء والخميس - 6:00 مساءً',
 'برنامج لياقة متكامل لتحسين القوة والتحمل', 'beginner', 16, 50,
 'mixed', 'active', 
 '2025-11-10 18:00:00', '2025-12-31 19:30:00', '2025-11-09 23:59:59'),
 
(2, 'تعليم سباحة للمبتدئين', 'سارة أحمد', 'مدربة سباحة معتمدة',
 10, 300, 'السبت والاثنين والأربعاء - 4:00 عصراً',
 'دورة تعليم أساسيات السباحة', 'beginner', 6, 15,
 'mixed', 'active',
 '2025-11-15 16:00:00', '2025-12-15 17:00:00', '2025-11-14 23:59:59');

-- Add indexes for performance
ALTER TABLE sports_bookings ADD INDEX idx_facility_dates (facility_id, starts_at, ends_at);
ALTER TABLE sports_activities ADD INDEX idx_dates (starts_at, ends_at);
ALTER TABLE sports_registrations ADD INDEX idx_user_status (user_id, status);
ALTER TABLE sports_attendance ADD INDEX idx_registration_date (registration_id, attended_at);