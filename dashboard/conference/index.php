<?php
$page_title = 'قاعة المؤتمرات';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'conference_admin'])) {
  redirect('/dashboard/index.php');
}
include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <h2 class="mb-4">إدارة قاعة المؤتمرات</h2>
  <div class="row g-4">
    <div class="col-md-6" data-aos="fade-up">
      <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/dashboard/conference/venues.php">
        <div class="feature-card h-100">
          <i class="bi bi-building"></i>
          <h5 class="mt-3">القاعات</h5>
          <p class="mb-0">إدارة القاعات وغرف الاجتماعات</p>
        </div>
      </a>
    </div>
    <div class="col-md-6" data-aos="fade-up">
      <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/dashboard/conference/bookings.php">
        <div class="feature-card h-100">
          <i class="bi bi-calendar4-week"></i>
          <h5 class="mt-3">الحجوزات</h5>
          <p class="mb-0">تنظيم الفعاليات</p>
        </div>
      </a>
    </div>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
