<?php
$page_title = 'إدارة السكن';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'residential_admin'])) {
  redirect('/dashboard/index.php');
}
include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <h2 class="mb-4">إدارة السكن</h2>
  <div class="row g-4">
    <div class="col-md-6" data-aos="fade-up">
      <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/dashboard/residential/residents.php">
        <div class="feature-card h-100">
          <i class="bi bi-people"></i>
          <h5 class="mt-3">إدارة السكان</h5>
          <p class="mb-0">عرض وإدارة سكان الوحدات السكنية</p>
        </div>
      </a>
    </div>
    <div class="col-md-6" data-aos="fade-up">
      <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/dashboard/residential/pending.php">
        <div class="feature-card h-100">
          <i class="bi bi-person-check"></i>
          <h5 class="mt-3">الطلبات المعلقة</h5>
          <p class="mb-0">الموافقة على حسابات المقيمين الجدد</p>
        </div>
      </a>
    </div>
    <div class="col-md-6" data-aos="fade-up">
      <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/residential/index.php">
        <div class="feature-card h-100">
          <i class="bi bi-building"></i>
          <h5 class="mt-3">الوحدات السكنية</h5>
          <p class="mb-0">عرض الوحدات السكنية والعمارات والفلل</p>
        </div>
      </a>
    </div>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

