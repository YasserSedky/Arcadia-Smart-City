<?php
$page_title = 'إدارة المدرسة';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'school_admin'])) {
  redirect('/dashboard/index.php');
}
include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <h2 class="mb-4">إدارة المدرسة</h2>
  <div class="row g-4">
    <div class="col-md-6" data-aos="fade-up">
      <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/dashboard/school/stages.php">
        <div class="feature-card h-100">
          <i class="bi bi-layers"></i>
          <h5 class="mt-3">المراحل الدراسية</h5>
          <p class="mb-0">حضانة - ابتدائي - إعدادي - ثانوي</p>
        </div>
      </a>
    </div>
    <div class="col-md-6" data-aos="fade-up">
      <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/dashboard/school/classes.php">
        <div class="feature-card h-100">
          <i class="bi bi-grid-3x3-gap"></i>
          <h5 class="mt-3">الفصول</h5>
          <p class="mb-0">إدارة الفصول والغرف</p>
        </div>
      </a>
    </div>
    <div class="col-md-6" data-aos="fade-up">
      <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/dashboard/school/students.php">
        <div class="feature-card h-100">
          <i class="bi bi-people"></i>
          <h5 class="mt-3">الطلاب</h5>
          <p class="mb-0">سجل الطلاب والتسجيل بالفصول</p>
        </div>
      </a>
    </div>
    <div class="col-md-6" data-aos="fade-up">
      <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/dashboard/school/teachers.php">
        <div class="feature-card h-100">
          <i class="bi bi-person-badge"></i>
          <h5 class="mt-3">المعلمون</h5>
          <p class="mb-0">ربط المعلمين بالنظام</p>
        </div>
      </a>
    </div>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
