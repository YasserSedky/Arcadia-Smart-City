USE arcadia_smart_city;

-- Service tickets
CREATE TABLE IF NOT EXISTS service_tickets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  created_by_user_id INT NOT NULL,
  type ENUM('cleaning', 'electrical', 'plumbing', 'pest', 'roads', 'other') NOT NULL,
  title VARCHAR(200) NOT NULL,
  details TEXT NOT NULL,
  status ENUM('new', 'in_progress', 'resolved', 'closed') NOT NULL DEFAULT 'new',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by_user_id) REFERENCES users(id)
);

-- Service ticket notes
CREATE TABLE IF NOT EXISTS service_ticket_notes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ticket_id INT NOT NULL,
  user_id INT NOT NULL,
  note TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (ticket_id) REFERENCES service_tickets(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Add service worker role
INSERT IGNORE INTO roles (name, display_name) VALUES ('service_worker', 'عامل خدمات');