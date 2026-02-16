USE arcadia_smart_city;

-- Conference center venues and bookings
CREATE TABLE IF NOT EXISTS conf_venues (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name_ar VARCHAR(200) NOT NULL,
  type ENUM('hall','meeting_room') NOT NULL DEFAULT 'hall',
  capacity INT DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS conf_bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  venue_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME DEFAULT NULL,
  status ENUM('scheduled','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  notes VARCHAR(400) DEFAULT NULL,
  FOREIGN KEY (venue_id) REFERENCES conf_venues(id)
);



