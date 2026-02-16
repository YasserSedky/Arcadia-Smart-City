<?php
require_once __DIR__ . '/../backend/config.php';

ensure_session();

function fail(string $msg)
{
    redirect('/auth/admin_register.php?err=' . urlencode($msg));
}

try {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role_code = trim($_POST['role_code'] ?? 'super_admin');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $secret = trim($_POST['admin_secret'] ?? '');

    if ($full_name === '' || $phone === '' || $email === '' || $password === '' || $confirm === '' || $secret === '') {
        fail('يرجى ملء جميع الحقول المطلوبة');
    }
    if ($password !== $confirm) {
        fail('تأكيد الرقم السري غير متطابق');
    }

    // check secret
    if ($secret !== ADMIN_REG_SECRET) {
        fail('رمز الأمان غير صحيح');
    }

    $pdo = DB::conn();

    // Get role id
    $roleStmt = $pdo->prepare('SELECT id FROM roles WHERE code = ?');
    $roleStmt->execute([$role_code]);
    $role = $roleStmt->fetch();
    if (!$role) {
        fail('هوية المستخدم غير صحيحة');
    }

    // Create user (admins don't need unit)
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('INSERT INTO users(full_name, phone, email, password_hash, role_id, is_active) VALUES(?,?,?,?,?,1)');
    $stmt->execute([$full_name, $phone, $email, $hash, (int)$role['id']]);

    redirect('/auth/login.php?ok=' . urlencode('تم إنشاء حساب الأدمن بنجاح'));
} catch (PDOException $e) {
    fail('حدث خطأ: ' . $e->getMessage());
}
