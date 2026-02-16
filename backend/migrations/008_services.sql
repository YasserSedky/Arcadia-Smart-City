USE arcadia_smart_city;

-- Maintenance tickets
CREATE TABLE IF NOT EXISTS maintenance_tickets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  created_by_user_id INT NOT NULL,
  unit_id INT DEFAULT NULL,
  title VARCHAR(200) NOT NULL,
  details VARCHAR(800) DEFAULT NULL,
  priority ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  status ENUM('open','assigned','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by_user_id) REFERENCES users(id),
  FOREIGN KEY (unit_id) REFERENCES units(id)
);

CREATE TABLE IF NOT EXISTS maintenance_assignments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ticket_id INT NOT NULL,
  worker_user_id INT NOT NULL,
  assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  notes VARCHAR(400) DEFAULT NULL,
  FOREIGN KEY (ticket_id) REFERENCES maintenance_tickets(id),
  FOREIGN KEY (worker_user_id) REFERENCES users(id)
);

-- Garden tasks
CREATE TABLE IF NOT EXISTS garden_tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  area_label VARCHAR(120) NOT NULL,
  task VARCHAR(200) NOT NULL,
  scheduled_date DATE NOT NULL,
  status ENUM('scheduled','in_progress','done','cancelled') NOT NULL DEFAULT 'scheduled',
  notes VARCHAR(400) DEFAULT NULL
);



