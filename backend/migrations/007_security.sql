USE arcadia_smart_city;

-- Gates
CREATE TABLE IF NOT EXISTS gates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(20) UNIQUE NOT NULL,
  name_ar VARCHAR(120) NOT NULL,
  location_label VARCHAR(120) DEFAULT NULL
);

-- Guard shifts at gates
CREATE TABLE IF NOT EXISTS guard_shifts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  gate_id INT NOT NULL,
  guard_user_id INT NOT NULL,
  shift_start DATETIME NOT NULL,
  shift_end DATETIME NOT NULL,
  notes VARCHAR(300) DEFAULT NULL,
  FOREIGN KEY (gate_id) REFERENCES gates(id),
  FOREIGN KEY (guard_user_id) REFERENCES users(id)
);

-- Incidents log
CREATE TABLE IF NOT EXISTS security_incidents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  gate_id INT DEFAULT NULL,
  reported_by_user_id INT DEFAULT NULL,
  title VARCHAR(200) NOT NULL,
  details VARCHAR(800) DEFAULT NULL,
  level ENUM('info','warning','critical') NOT NULL DEFAULT 'info',
  occurred_at DATETIME NOT NULL,
  FOREIGN KEY (gate_id) REFERENCES gates(id),
  FOREIGN KEY (reported_by_user_id) REFERENCES users(id)
);

-- Seed 6 gates
INSERT IGNORE INTO gates(code, name_ar) VALUES
 ('G1','البوابة 1'),('G2','البوابة 2'),('G3','البوابة 3'),('G4','البوابة 4'),('G5','البوابة 5'),('G6','البوابة 6');



