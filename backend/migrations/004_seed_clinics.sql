USE arcadia_smart_city;

-- Seed clinics (at least 7) linked to existing departments by code
INSERT IGNORE INTO hospital_clinics(department_id, name_ar, room_label)
SELECT id, 'عيادة الباطنة العامة', 'I-101' FROM hospital_departments WHERE code='internal' LIMIT 1;

INSERT IGNORE INTO hospital_clinics(department_id, name_ar, room_label)
SELECT id, 'عيادة القلب (تابع الباطنة)', 'I-102' FROM hospital_departments WHERE code='internal' LIMIT 1;

INSERT IGNORE INTO hospital_clinics(department_id, name_ar, room_label)
SELECT id, 'عيادة الجراحة العامة', 'S-201' FROM hospital_departments WHERE code='surgery' LIMIT 1;

INSERT IGNORE INTO hospital_clinics(department_id, name_ar, room_label)
SELECT id, 'عيادة الأطفال', 'P-301' FROM hospital_departments WHERE code='pediatrics' LIMIT 1;

INSERT IGNORE INTO hospital_clinics(department_id, name_ar, room_label)
SELECT id, 'عيادة النساء والتوليد', 'OBG-401' FROM hospital_departments WHERE code='obgyn' LIMIT 1;

INSERT IGNORE INTO hospital_clinics(department_id, name_ar, room_label)
SELECT id, 'عيادة الأنف والأذن والحنجرة', 'ENT-501' FROM hospital_departments WHERE code='ent' LIMIT 1;

INSERT IGNORE INTO hospital_clinics(department_id, name_ar, room_label)
SELECT id, 'عيادة العيون', 'EYE-601' FROM hospital_departments WHERE code='ophthalmology' LIMIT 1;

-- Optional: ensure ordering or duplicates are avoided via IGNORE
