<?php
$page_title = 'الأمن والبوابات';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'security_admin'])) {
  redirect('/dashboard/index.php');
}
include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <h2 class="mb-4">إدارة الأمن والبوابات</h2>
  <div class="row g-4">
    <div class="col-md-6" data-aos="fade-up">
      <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/dashboard/security/gates.php">
        <div class="feature-card h-100">
          <i class="bi bi-door-closed"></i>
          <h5 class="mt-3">البوابات</h5>
          <p class="mb-0">قائمة البوابات ومواقعها</p>
        </div>
      </a>
    </div>
    <div class="col-md-6" data-aos="fade-up">
      <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/dashboard/security/shifts.php">
        <div class="feature-card h-100">
          <i class="bi bi-person-workspace"></i>
          <h5 class="mt-3">نوبات الحراسة</h5>
          <p class="mb-0">جدولة ومتابعة النوبات</p>
        </div>
      </a>
    </div>
    <div class="col-md-6" data-aos="fade-up">
      <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/dashboard/security/incidents.php">
        <div class="feature-card h-100">
          <i class="bi bi-exclamation-triangle"></i>
          <h5 class="mt-3">البلاغات والحوادث</h5>
          <p class="mb-0">تسجيل وتتبع الحوادث</p>
        </div>
      </a>
    </div>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
