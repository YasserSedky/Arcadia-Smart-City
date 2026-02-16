<?php
require_once __DIR__ . '/../backend/config.php';

ensure_session();

function fail_login(string $msg){
  // Redirect to appropriate page based on referrer or use admin_login if unit_code is not provided
  $redirect_to = '/auth/login.php';
  if (empty($_POST['unit_code']) && (strpos($_SERVER['HTTP_REFERER'] ?? '', 'admin_login') !== false || filter_var($_POST['phone'] ?? '', FILTER_VALIDATE_EMAIL) || strpos($_POST['phone'] ?? '', '@') !== false)) {
    $redirect_to = '/auth/admin_login.php';
  }
  redirect($redirect_to . '?err=' . urlencode($msg));
}

try {
  $phone = trim($_POST['phone'] ?? '');
  $unit_code = trim($_POST['unit_code'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($phone === '' || $password === '') {
    fail_login('يرجى إدخال البيانات المطلوبة');
  }

  $pdo = DB::conn();

  $user = null;
  
  // Check if input contains @ (email-like) or is a valid email
  $is_email_like = (strpos($phone, '@') !== false) || filter_var($phone, FILTER_VALIDATE_EMAIL);
  
  if ($is_email_like) {
    // Try to find user by email (for admins or any user)
    $stmt = $pdo->prepare('SELECT u.*, r.code AS role_code FROM users u JOIN roles r ON r.id=u.role_id WHERE u.email = ? LIMIT 1');
    $stmt->execute([$phone]);
    $user = $stmt->fetch();
  } else {
    // Try to find user by phone - first check if it's an admin (no unit required)
    $stmt = $pdo->prepare('SELECT u.*, r.code AS role_code FROM users u JOIN roles r ON r.id=u.role_id WHERE u.phone = ? AND u.unit_id IS NULL LIMIT 1');
    $stmt->execute([$phone]);
    $user = $stmt->fetch();
    
    // If not admin and unit_code is provided, try regular user login
    if (!$user && $unit_code !== '') {
      $stmt = $pdo->prepare('SELECT u.*, r.code AS role_code FROM users u JOIN roles r ON r.id=u.role_id JOIN units un ON un.id=u.unit_id WHERE u.phone = ? AND un.unit_code = ? LIMIT 1');
      $stmt->execute([$phone, $unit_code]);
      $user = $stmt->fetch();
    } elseif (!$user && $unit_code === '') {
      // User not found and no unit_code provided - could be admin trying without unit_code
      fail_login('بيانات الدخول غير صحيحة');
    }
  }

  if (!$user || !password_verify($password, $user['password_hash'])) {
    fail_login('بيانات الدخول غير صحيحة');
  }

  if ((int)$user['is_active'] !== 1){
    fail_login('الحساب غير مفعّل');
  }

  $_SESSION['user'] = [
    'id' => (int)$user['id'],
    'name' => $user['full_name'],
    'phone' => $user['phone'],
    'email' => $user['email'],
    'role_id' => (int)$user['role_id'],
    'role_code' => $user['role_code'],
    'unit_id' => $user['unit_id'] ? (int)$user['unit_id'] : null,
  ];

  redirect('/dashboard/index.php');
} catch (PDOException $e) {
  fail_login('حدث خطأ: ' . $e->getMessage());
}



