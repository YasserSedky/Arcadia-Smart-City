-- كود SQL لإنشاء حساب أدمن
-- الإيميل: yassersedky07@admin
-- الباسورد: 100100

USE arcadia_smart_city;

-- إنشاء حساب أدمن (super_admin)
INSERT INTO users(
    full_name, 
    phone, 
    email, 
    password_hash, 
    role_id, 
    unit_id, 
    is_active
) 
VALUES(
    'Yasser Sedky',                    -- الاسم الكامل
    '0500000000',                      -- رقم الهاتف (يمكن تغييره)
    'yassersedky07@admin',             -- البريد الإلكتروني
    '$2y$10$VtXm9qpNMjqkodavZEB1XeZ1Nq6gKLMyNhTdPXNIfBmgE2dwAQQ3u',  -- الباسورد المشفر (100100)
    1,                                  -- role_id = 1 (super_admin)
    NULL,                               -- unit_id = NULL (الأدمن لا يحتاج وحدة سكنية)
    1                                   -- is_active = 1 (الحساب مفعل)
)
ON DUPLICATE KEY UPDATE 
    email = VALUES(email),
    password_hash = VALUES(password_hash),
    is_active = 1;

