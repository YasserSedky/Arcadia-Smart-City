USE arcadia_smart_city;

-- School stages/levels
CREATE TABLE IF NOT EXISTS school_stages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name_ar VARCHAR(120) NOT NULL,
  code VARCHAR(50) UNIQUE NOT NULL
);

-- Classes per stage
CREATE TABLE IF NOT EXISTS school_classes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  stage_id INT NOT NULL,
  name_ar VARCHAR(120) NOT NULL,
  room_label VARCHAR(50) DEFAULT NULL,
  FOREIGN KEY (stage_id) REFERENCES school_stages(id)
);

-- Teachers (link to users)
CREATE TABLE IF NOT EXISTS school_teachers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  specialty VARCHAR(120) DEFAULT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Students registry
CREATE TABLE IF NOT EXISTS school_students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(200) NOT NULL,
  guardian_phone VARCHAR(32) DEFAULT NULL,
  date_of_birth DATE DEFAULT NULL,
  gender ENUM('male','female') DEFAULT NULL
);

-- Enrollments
CREATE TABLE IF NOT EXISTS school_enrollments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  class_id INT NOT NULL,
  year VARCHAR(20) NOT NULL,
  UNIQUE KEY uk_student_year (student_id, year),
  FOREIGN KEY (student_id) REFERENCES school_students(id),
  FOREIGN KEY (class_id) REFERENCES school_classes(id)
);

-- Seed stages: Nursery, Primary, Preparatory, Secondary
INSERT IGNORE INTO school_stages(name_ar, code) VALUES
 ('حضانة','nursery'),
 ('ابتدائي','primary'),
 ('إعدادي','prep'),
 ('ثانوي','secondary');



