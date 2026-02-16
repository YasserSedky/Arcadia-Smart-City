-- Add radiology and lab test types
CREATE TABLE hospital_test_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name_ar VARCHAR(255) NOT NULL,
    category ENUM('radiology', 'lab') NOT NULL,
    preparation_notes TEXT,
    price DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add radiology and lab test orders
CREATE TABLE hospital_test_orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    appointment_id INT,
    test_type_id INT NOT NULL,
    doctor_notes TEXT,
    status ENUM('ordered', 'scheduled', 'completed', 'cancelled') NOT NULL DEFAULT 'ordered',
    scheduled_for DATETIME,
    completed_at DATETIME,
    results_text TEXT,
    results_file VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES hospital_patients(id),
    FOREIGN KEY (appointment_id) REFERENCES hospital_appointments(id),
    FOREIGN KEY (test_type_id) REFERENCES hospital_test_types(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add pharmacy requests table
CREATE TABLE pharmacy_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'dispensed') NOT NULL DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME,
    FOREIGN KEY (patient_id) REFERENCES hospital_patients(id),
    FOREIGN KEY (item_id) REFERENCES pharmacy_items(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add follow-ups table to track patient follow-up appointments
CREATE TABLE hospital_followups (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    original_appointment_id INT,
    followup_appointment_id INT,
    doctor_notes TEXT,
    status ENUM('scheduled', 'completed', 'cancelled') NOT NULL DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES hospital_patients(id),
    FOREIGN KEY (original_appointment_id) REFERENCES hospital_appointments(id),
    FOREIGN KEY (followup_appointment_id) REFERENCES hospital_appointments(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add some initial radiology test types
INSERT INTO hospital_test_types (name_ar, category, preparation_notes) VALUES
('أشعة سينية للصدر', 'radiology', 'يجب أن تكون صائماً لمدة 4 ساعات قبل الموعد'),
('أشعة مقطعية', 'radiology', 'يجب أن تكون صائماً لمدة 6 ساعات قبل الموعد'),
('تصوير بالرنين المغناطيسي', 'radiology', 'لا تلبس أي مجوهرات معدنية'),
('أشعة فوق صوتية', 'radiology', 'يجب شرب كمية كافية من الماء قبل الموعد');

-- Add some initial lab test types
INSERT INTO hospital_test_types (name_ar, category, preparation_notes) VALUES
('تحليل الدم الكامل', 'lab', 'يجب أن تكون صائماً لمدة 8 ساعات'),
('وظائف الكبد', 'lab', 'يجب أن تكون صائماً لمدة 12 ساعة'),
('وظائف الكلى', 'lab', 'يجب أن تكون صائماً لمدة 12 ساعة'),
('تحليل السكر التراكمي', 'lab', 'لا يتطلب صيام');