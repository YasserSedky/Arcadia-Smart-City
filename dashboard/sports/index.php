<?php
$page_title = 'إدارة النادي الرياضي';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'sports_admin'])) {
  redirect('/dashboard/index.php');
}
include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <h2 class="mb-4">إدارة النادي الرياضي</h2>
  <div class="row g-4">
    <div class="col-md-6" data-aos="fade-up">
      <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/dashboard/sports/facilities.php">
        <div class="feature-card h-100">
          <i class="bi bi-collection"></i>
          <h5 class="mt-3">المرافق</h5>
          <p class="mb-0">ملاعب، مسابح، صالات</p>
        </div>
      </a>
    </div>
    <div class="col-md-6" data-aos="fade-up">
      <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/dashboard/sports/bookings.php">
        <div class="feature-card h-100">
          <i class="bi bi-calendar3"></i>
          <h5 class="mt-3">الحجوزات</h5>
          <p class="mb-0">إدارة حجوزات المرافق</p>
        </div>
      </a>
    </div>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
