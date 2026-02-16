USE arcadia_smart_city;

-- إضافة الأعمدة الجديدة لجدول الحجوزات
ALTER TABLE mall_bookings 
ADD COLUMN user_id INT NOT NULL AFTER venue_id,
ADD COLUMN attendees INT NOT NULL DEFAULT 1,
ADD FOREIGN KEY (user_id) REFERENCES users(id);