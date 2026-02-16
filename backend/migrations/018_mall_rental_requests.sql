USE arcadia_smart_city;

CREATE TABLE IF NOT EXISTS mall_rental_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unit_id INT NOT NULL,
    applicant_name VARCHAR(200) NOT NULL,
    business_name VARCHAR(200) NOT NULL,
    category_id INT,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(200) NOT NULL,
    commercial_register VARCHAR(100),
    business_description TEXT,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (unit_id) REFERENCES mall_units(id),
    FOREIGN KEY (category_id) REFERENCES mall_categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;