-- Migration: 023_add_water_stations.sql
-- Adds water_stations table for managing water stations in the city

CREATE TABLE IF NOT EXISTS water_stations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    location VARCHAR(255),
    supplied_units JSON, -- Array of unit_ids supplied by the station
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample data
INSERT IGNORE INTO water_stations (name, location, supplied_units) VALUES
('محطة المياه الرئيسية', 'وسط المدينة', '["1", "2", "3"]'),
('محطة المياه الشمالية', 'المنطقة الشمالية', '["4", "5"]');