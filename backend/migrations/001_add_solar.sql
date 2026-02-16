USE arcadia_smart_city;

-- Role for energy management
INSERT IGNORE INTO roles(code, name_ar) VALUES('energy_admin','مدير الطاقة');

-- Solar arrays per building
CREATE TABLE IF NOT EXISTS solar_arrays (
  id INT AUTO_INCREMENT PRIMARY KEY,
  building_id INT NOT NULL,
  name VARCHAR(128) NOT NULL,
  capacity_kw DECIMAL(10,2) NOT NULL,
  install_date DATE DEFAULT NULL,
  notes VARCHAR(500) DEFAULT NULL,
  UNIQUE KEY uq_building_name (building_id, name),
  FOREIGN KEY (building_id) REFERENCES buildings(id)
);

-- Optional inverters per array
CREATE TABLE IF NOT EXISTS solar_inverters (
  id INT AUTO_INCREMENT PRIMARY KEY,
  array_id INT NOT NULL,
  manufacturer VARCHAR(128) DEFAULT NULL,
  model VARCHAR(128) DEFAULT NULL,
  capacity_kw DECIMAL(10,2) DEFAULT NULL,
  serial_no VARCHAR(128) DEFAULT NULL,
  FOREIGN KEY (array_id) REFERENCES solar_arrays(id)
);

-- Optional meters per array
CREATE TABLE IF NOT EXISTS solar_meters (
  id INT AUTO_INCREMENT PRIMARY KEY,
  array_id INT NOT NULL,
  meter_id VARCHAR(128) NOT NULL,
  type ENUM('production','export','import') NOT NULL DEFAULT 'production',
  UNIQUE KEY uq_array_meter (array_id, meter_id, type),
  FOREIGN KEY (array_id) REFERENCES solar_arrays(id)
);

-- Telemetry readings per array
CREATE TABLE IF NOT EXISTS solar_readings (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  array_id INT NOT NULL,
  ts DATETIME NOT NULL,
  power_kw DECIMAL(10,3) DEFAULT NULL,
  energy_kwh DECIMAL(12,3) DEFAULT NULL,
  temperature_c DECIMAL(6,2) DEFAULT NULL,
  status ENUM('ok','warning','fault') DEFAULT 'ok',
  INDEX idx_array_ts (array_id, ts),
  FOREIGN KEY (array_id) REFERENCES solar_arrays(id)
);



