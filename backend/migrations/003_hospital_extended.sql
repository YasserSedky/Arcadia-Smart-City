USE arcadia_smart_city;

-- Pharmacy inventory
CREATE TABLE IF NOT EXISTS pharmacy_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name_ar VARCHAR(200) NOT NULL,
  sku VARCHAR(100) UNIQUE,
  unit VARCHAR(50) DEFAULT 'pcs',
  quantity INT NOT NULL DEFAULT 0,
  min_quantity INT NOT NULL DEFAULT 0,
  expiry_date DATE DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS pharmacy_transactions (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  item_id INT NOT NULL,
  ts DATETIME NOT NULL,
  type ENUM('in','out') NOT NULL,
  amount INT NOT NULL,
  note VARCHAR(300) DEFAULT NULL,
  FOREIGN KEY (item_id) REFERENCES pharmacy_items(id)
);

-- Operations (surgeries)
CREATE TABLE IF NOT EXISTS surgeries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  department_id INT DEFAULT NULL,
  title VARCHAR(200) NOT NULL,
  scheduled_at DATETIME NOT NULL,
  room_label VARCHAR(64) DEFAULT NULL,
  status ENUM('scheduled','in_progress','done','cancelled') NOT NULL DEFAULT 'scheduled',
  notes VARCHAR(500) DEFAULT NULL,
  FOREIGN KEY (patient_id) REFERENCES hospital_patients(id),
  FOREIGN KEY (department_id) REFERENCES hospital_departments(id)
);

-- Emergency
CREATE TABLE IF NOT EXISTS emergency_cases (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_name VARCHAR(200) NOT NULL,
  severity ENUM('low','medium','high','critical') NOT NULL,
  arrived_at DATETIME NOT NULL,
  status ENUM('waiting','treated','transferred','deceased') NOT NULL DEFAULT 'waiting',
  notes VARCHAR(500) DEFAULT NULL
);

-- Nursing shifts
CREATE TABLE IF NOT EXISTS nursing_shifts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  staff_user_id INT NOT NULL,
  department_id INT DEFAULT NULL,
  shift_date DATE NOT NULL,
  shift_type ENUM('morning','evening','night') NOT NULL,
  notes VARCHAR(300) DEFAULT NULL,
  FOREIGN KEY (staff_user_id) REFERENCES users(id),
  FOREIGN KEY (department_id) REFERENCES hospital_departments(id)
);



