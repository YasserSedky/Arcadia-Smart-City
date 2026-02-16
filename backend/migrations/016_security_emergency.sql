USE arcadia_smart_city;

-- Emergency contact points
CREATE TABLE IF NOT EXISTS emergency_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('police', 'ambulance', 'fire') NOT NULL,
    name_ar VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    location_label VARCHAR(200) DEFAULT NULL,
    working_hours VARCHAR(100) DEFAULT '24/7',
    notes VARCHAR(500) DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE
);

-- Initial emergency contacts
INSERT IGNORE INTO emergency_contacts (type, name_ar, phone, location_label) VALUES
('police', 'نقطة شرطة أركاديا', '123', 'بجوار البوابة 1'),
('ambulance', 'إسعاف أركاديا', '124', 'بجوار المستشفى'),
('fire', 'نقطة إطفاء أركاديا', '125', 'بجوار البوابة 3');