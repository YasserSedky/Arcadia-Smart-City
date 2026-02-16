<?php
require_once __DIR__ . '/../backend/config.php';

ensure_session();

function fail(string $msg){
  redirect('/auth/register.php?err=' . urlencode($msg));
}

try {
  $full_name = trim($_POST['full_name'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $role_code = trim($_POST['role_code'] ?? 'resident');
  $unit_code = trim($_POST['unit_code'] ?? '');
  $password = $_POST['password'] ?? '';
  $confirm = $_POST['confirm_password'] ?? '';

  if ($full_name === '' || $phone === '' || $email === '' || $password === '' || $confirm === '') {
    fail('يرجى ملء جميع الحقول المطلوبة');
  }
  if ($password !== $confirm) {
    fail('تأكيد الرقم السري غير متطابق');
  }

  $pdo = DB::conn();

  // Get role id
  $roleStmt = $pdo->prepare('SELECT id FROM roles WHERE code = ?');
  $roleStmt->execute([$role_code]);
  $role = $roleStmt->fetch();
  if (!$role) { fail('هوية المستخدم غير صحيحة'); }

  // Resolve unit id if provided
  $unit_id = null;
  if ($unit_code !== ''){
    $uStmt = $pdo->prepare('SELECT id FROM units WHERE unit_code = ?');
    $uStmt->execute([$unit_code]);
    $u = $uStmt->fetch();
    if (!$u) { fail('رقم الوحدة السكنية غير صحيح'); }
    $unit_id = (int)$u['id'];
  }

  // Residents must provide a unit
  if ($role_code === 'resident' && !$unit_id){
    fail('يجب إدخال رقم الوحدة السكنية للمقيم');
  }

  // For residents, set is_active to 0 (pending approval)
  // For other roles, set is_active to 1 (active by default)
  $is_active = ($role_code === 'resident') ? 0 : 1;

  // Create user
  $hash = password_hash($password, PASSWORD_BCRYPT);
  $stmt = $pdo->prepare('INSERT INTO users(full_name, phone, email, password_hash, role_id, unit_id, is_active) VALUES(?,?,?,?,?,?,?)');
  $stmt->execute([$full_name, $phone, $email, $hash, (int)$role['id'], $unit_id, $is_active]);

  if ($role_code === 'resident') {
    redirect('/auth/login.php?ok=' . urlencode('تم إنشاء الحساب بنجاح. سيتم تفعيل حسابك بعد موافقة مدير السكن'));
  } else {
    redirect('/auth/login.php?ok=' . urlencode('تم إنشاء الحساب بنجاح'));
  }
} catch (PDOException $e) {
  fail('حدث خطأ: ' . $e->getMessage());
}



