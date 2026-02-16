USE arcadia_smart_city;

-- Add role for HQ admin
INSERT IGNORE INTO roles(code, name_ar) VALUES('hq_admin','مدير HQ');

-- HQ notices/announcements
CREATE TABLE IF NOT EXISTS hq_notices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  body TEXT NOT NULL,
  created_by_user_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by_user_id) REFERENCES users(id)
);



