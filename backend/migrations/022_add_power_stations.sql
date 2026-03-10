CREATE TABLE IF NOT EXISTS power_stations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    location VARCHAR(255),
    responsible_units JSON, -- Array of unit_ids responsible for the station
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample data
INSERT IGNORE INTO power_stations (name, location, responsible_units) VALUES
('محطة الكهرباء الرئيسية', 'وسط المدينة', '["1", "2", "3"]'),
('محطة الطاقة الشمسية الشمالية', 'المنطقة الشمالية', '["4", "5"]');