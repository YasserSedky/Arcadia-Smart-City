<?php
$page_title = 'الخدمات والصيانة';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'services_admin', 'residential_admin'])) {
  redirect('/dashboard/index.php');
}
include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <h2 class="mb-4">الخدمات والصيانة</h2>
  <div class="row g-4">
    <div class="col-md-6" data-aos="fade-up">
      <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/dashboard/services/tickets.php">
        <div class="feature-card h-100">
          <i class="bi bi-tools"></i>
          <h5 class="mt-3">بلاغات الصيانة</h5>
          <p class="mb-0">فتح البلاغات وإسنادها للعاملين</p>
        </div>
      </a>
    </div>
    <div class="col-md-6" data-aos="fade-up">
      <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/dashboard/services/gardens.php">
        <div class="feature-card h-100">
          <i class="bi bi-flower1"></i>
          <h5 class="mt-3">خدمات الحدائق</h5>
          <p class="mb-0">جدولة أعمال العناية بالمسطحات الخضراء</p>
        </div>
      </a>
    </div>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
