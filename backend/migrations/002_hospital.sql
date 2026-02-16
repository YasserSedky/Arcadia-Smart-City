USE arcadia_smart_city;

-- Hospital core tables
CREATE TABLE IF NOT EXISTS hospital_departments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name_ar VARCHAR(200) NOT NULL,
  code VARCHAR(64) UNIQUE NOT NULL
);

CREATE TABLE IF NOT EXISTS hospital_clinics (
  id INT AUTO_INCREMENT PRIMARY KEY,
  department_id INT NOT NULL,
  name_ar VARCHAR(200) NOT NULL,
  room_label VARCHAR(64) DEFAULT NULL,
  FOREIGN KEY (department_id) REFERENCES hospital_departments(id)
);

-- Staff link to users (doctors, nurses, hospital_staff)
CREATE TABLE IF NOT EXISTS hospital_staff (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  position ENUM('doctor','nurse','staff') NOT NULL,
  department_id INT DEFAULT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (department_id) REFERENCES hospital_departments(id)
);

-- Patients registry
CREATE TABLE IF NOT EXISTS hospital_patients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(200) NOT NULL,
  phone VARCHAR(32) NOT NULL,
  national_id VARCHAR(64) DEFAULT NULL,
  date_of_birth DATE DEFAULT NULL,
  gender ENUM('male','female') DEFAULT NULL,
  UNIQUE KEY uk_phone (phone)
);

-- Appointments
CREATE TABLE IF NOT EXISTS hospital_appointments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  clinic_id INT NOT NULL,
  patient_id INT NOT NULL,
  staff_id INT DEFAULT NULL,
  starts_at DATETIME NOT NULL,
  status ENUM('scheduled','checked_in','done','cancelled') NOT NULL DEFAULT 'scheduled',
  notes VARCHAR(500) DEFAULT NULL,
  FOREIGN KEY (clinic_id) REFERENCES hospital_clinics(id),
  FOREIGN KEY (patient_id) REFERENCES hospital_patients(id),
  FOREIGN KEY (staff_id) REFERENCES hospital_staff(id)
);

-- Seed some departments
INSERT IGNORE INTO hospital_departments(name_ar, code) VALUES
 ('الباطنة','internal'),
 ('الجراحة','surgery'),
 ('الأطفال','pediatrics'),
 ('النساء والتوليد','obgyn'),
 ('الأنف والأذن والحنجرة','ent'),
 ('العيون','ophthalmology');



