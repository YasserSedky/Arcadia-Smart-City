<?php
$page_title = 'إدارة المول';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'mall_admin'])) {
  redirect('/dashboard/index.php');
}
include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <h2 class="mb-4">إدارة المول</h2>
  <div class="row g-4">
    <div class="col-md-6" data-aos="fade-up">
      <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/dashboard/mall/units.php">
        <div class="feature-card h-100">
          <i class="bi bi-shop"></i>
          <h5 class="mt-3">الوحدات</h5>
          <p class="mb-0">المحلات والأكشاك وبرفانات الحلاقة</p>
        </div>
      </a>
    </div>
    <div class="col-md-6" data-aos="fade-up">
      <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/dashboard/mall/tenants.php">
        <div class="feature-card h-100">
          <i class="bi bi-briefcase"></i>
          <h5 class="mt-3">المستأجرون</h5>
          <p class="mb-0">إدارة المستأجرين وربطهم بالوحدات</p>
        </div>
      </a>
    </div>
    <div class="col-md-6" data-aos="fade-up">
      <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/dashboard/mall/venues.php">
        <div class="feature-card h-100">
          <i class="bi bi-film"></i>
          <h5 class="mt-3">المرافق الترفيهية</h5>
          <p class="mb-0">دور السينما وصالات الألعاب</p>
        </div>
      </a>
    </div>
    <div class="col-md-6" data-aos="fade-up">
      <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/dashboard/mall/bookings.php">
        <div class="feature-card h-100">
          <i class="bi bi-calendar2-check"></i>
          <h5 class="mt-3">الحجوزات</h5>
          <p class="mb-0">حجوزات الفعاليات والعروض</p>
        </div>
      </a>
    </div>
    <div class="col-md-6" data-aos="fade-up">
      <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/dashboard/mall/rental_requests.php">
        <div class="feature-card h-100">
          <i class="bi bi-building-add"></i>
          <h5 class="mt-3">طلبات الإيجار</h5>
          <p class="mb-0">إدارة طلبات إيجار المحلات الجديدة</p>
        </div>
      </a>
    </div>
    <div class="col-md-6" data-aos="fade-up">
      <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/dashboard/mall/categories.php">
        <div class="feature-card h-100">
          <i class="bi bi-tags"></i>
          <h5 class="mt-3">تصنيفات المحلات</h5>
          <p class="mb-0">إدارة تصنيفات المحلات والأنشطة</p>
        </div>
      </a>
    </div>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>