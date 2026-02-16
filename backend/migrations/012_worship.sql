USE arcadia_smart_city;

-- Role for worship places management
INSERT IGNORE INTO roles(code, name_ar) VALUES('worship_admin','مدير دور العبادة');

-- Worship places types
CREATE TABLE IF NOT EXISTS worship_types (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name_ar VARCHAR(128) NOT NULL,
  icon VARCHAR(64) DEFAULT NULL
);

-- Insert default worship types
INSERT INTO worship_types(name_ar, icon) VALUES 
('مسجد', 'mosque'),
('كنيسة', 'church');

-- Worship places
CREATE TABLE IF NOT EXISTS worship_places (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type_id INT NOT NULL,
  name_ar VARCHAR(255) NOT NULL,
  capacity INT NOT NULL DEFAULT 0,
  location TEXT,
  description TEXT,
  prayer_times JSON DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (type_id) REFERENCES worship_types(id)
);

-- Services/Activities in worship places
CREATE TABLE IF NOT EXISTS worship_services (
  id INT AUTO_INCREMENT PRIMARY KEY,
  place_id INT NOT NULL,
  name_ar VARCHAR(255) NOT NULL,
  schedule TEXT,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (place_id) REFERENCES worship_places(id)
);

-- Announcements for worship places
CREATE TABLE IF NOT EXISTS worship_announcements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  place_id INT NOT NULL,
  title_ar VARCHAR(255) NOT NULL,
  content TEXT NOT NULL,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (place_id) REFERENCES worship_places(id)
);