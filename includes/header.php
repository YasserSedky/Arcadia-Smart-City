<?php
require_once __DIR__ . '/../backend/config.php';
ensure_session();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' | ' : ''; ?>Arcadia</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
  <link href="<?php echo APP_BASE; ?>/assets/css/style.css" rel="stylesheet">
  <?php if (strpos($_SERVER['PHP_SELF'], '/sports/') !== false): ?>
    <link href="<?php echo APP_BASE; ?>/assets/css/sports.css" rel="stylesheet">
  <?php endif; ?>
  <?php if (strpos($_SERVER['PHP_SELF'], '/conference/') !== false): ?>
    <link href="<?php echo APP_BASE; ?>/assets/css/conference.css" rel="stylesheet">
  <?php endif; ?>
  <?php if (strpos($_SERVER['PHP_SELF'], '/bank/') !== false): ?>
    <link href="<?php echo APP_BASE; ?>/assets/css/bank.css" rel="stylesheet">
  <?php endif; ?>
  <?php if (strpos($_SERVER['PHP_SELF'], '/security/') !== false): ?>
    <link href="<?php echo APP_BASE; ?>/assets/css/security.css" rel="stylesheet">
  <?php endif; ?>
  <?php if (strpos($_SERVER['PHP_SELF'], '/residential/') !== false): ?>
    <link href="<?php echo APP_BASE; ?>/assets/css/residential.css" rel="stylesheet">
  <?php endif; ?>
  <?php if (strpos($_SERVER['PHP_SELF'], '/services/') !== false): ?>
    <link href="<?php echo APP_BASE; ?>/assets/css/services.css" rel="stylesheet">
  <?php endif; ?>
  <?php if (strpos($_SERVER['PHP_SELF'], '/mall/') !== false): ?>
    <link href="<?php echo APP_BASE; ?>/assets/css/mall.css" rel="stylesheet">
  <?php endif; ?>
</head>

<body>
  <nav class="navbar navbar-expand-lg glass-nav">
    <div class="container">
      <a class="navbar-brand fw-bold text-gradient" href="<?php echo APP_BASE; ?>/index.php">Arcadia</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="nav">
        <ul class="navbar-nav ms-auto">
          <?php if (!empty($_SESSION['user'])): ?>
            <li class="nav-item"><a class="nav-link" href="<?php echo APP_BASE; ?>/dashboard/index.php">لوحة التحكم</a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo APP_BASE; ?>/auth/logout.php">تسجيل الخروج</a></li>
          <?php else: ?>
            <li class="nav-item"><a class="nav-link" href="<?php echo APP_BASE; ?>/auth/login.php">تسجيل الدخول</a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo APP_BASE; ?>/auth/register.php">إنشاء حساب</a></li>
          <?php endif; ?>
          <li class="nav-item ms-2"><button class="btn btn-outline-light btn-sm" id="theme-toggle" aria-label="تبديل الثيم"><i class="bi bi-moon"></i></button></li>
        </ul>
      </div>
    </div>
  </nav>
