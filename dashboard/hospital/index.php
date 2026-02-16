<?php
$page_title = 'إدارة المستشفى';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'hospital_admin'])) {
  redirect('/dashboard/index.php');
}
include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <h2 class="mb-4">إدارة المستشفى</h2>
  <div class="row g-4">
    <div class="col-md-6" data-aos="fade-up">
      <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/dashboard/hospital/departments.php">
        <div class="feature-card h-100">
          <i class="bi bi-diagram-3"></i>
          <h5 class="mt-3">الأقسام</h5>
          <p class="mb-0">إدارة الأقسام الطبية</p>
        </div>
      </a>
    </div>
    <div class="col-md-6" data-aos="fade-up">
      <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/dashboard/hospital/clinics.php">
        <div class="feature-card h-100">
          <i class="bi bi-clipboard2-pulse"></i>
          <h5 class="mt-3">العيادات</h5>
          <p class="mb-0">إدارة العيادات والغرف</p>
        </div>
      </a>
    </div>
    <div class="col-md-6" data-aos="fade-up">
      <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/dashboard/hospital/patients.php">
        <div class="feature-card h-100">
          <i class="bi bi-people"></i>
          <h5 class="mt-3">المرضى</h5>
          <p class="mb-0">سجل المرضى</p>
        </div>
      </a>
    </div>
    <div class="col-md-6" data-aos="fade-up">
      <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/dashboard/hospital/appointments.php">
        <div class="feature-card h-100">
          <i class="bi bi-calendar-event"></i>
          <h5 class="mt-3">المواعيد</h5>
          <p class="mb-0">حجوزات ومتابعة المواعيد</p>
        </div>
      </a>
    </div>
    <div class="col-md-6" data-aos="fade-up">
      <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/dashboard/hospital/pharmacy.php">
        <div class="feature-card h-100">
          <i class="bi bi-capsule"></i>
          <h5 class="mt-3">الصيدلية</h5>
          <p class="mb-0">إدارة المخزون</p>
        </div>
      </a>
    </div>
    <div class="col-md-6" data-aos="fade-up">
      <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/dashboard/hospital/pharmacy_requests.php">
        <div class="feature-card h-100">
          <i class="bi bi-journal-medical"></i>
          <h5 class="mt-3">طلبات الأدوية</h5>
          <p class="mb-0">إدارة طلبات صرف الأدوية</p>
        </div>
      </a>
    </div>
    <div class="col-md-6" data-aos="fade-up">
      <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/dashboard/hospital/radiology.php">
        <div class="feature-card h-100">
          <i class="bi bi-file-medical"></i>
          <h5 class="mt-3">الأشعة والتحاليل</h5>
          <p class="mb-0">إدارة طلبات الأشعة والتحاليل</p>
        </div>
      </a>
    </div>
    <div class="col-md-6" data-aos="fade-up">
      <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/dashboard/hospital/operations.php">
        <div class="feature-card h-100">
          <i class="bi bi-scissors"></i>
          <h5 class="mt-3">العمليات</h5>
          <p class="mb-0">جدولة العمليات وغرف العمليات</p>
        </div>
      </a>
    </div>
    <div class="col-md-6" data-aos="fade-up">
      <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/dashboard/hospital/emergency.php">
        <div class="feature-card h-100">
          <i class="bi bi-life-preserver"></i>
          <h5 class="mt-3">الطوارئ</h5>
          <p class="mb-0">فرز الحالات والمتابعة</p>
        </div>
      </a>
    </div>
    <div class="col-md-6" data-aos="fade-up">
      <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/dashboard/hospital/nursing.php">
        <div class="feature-card h-100">
          <i class="bi bi-clipboard-data"></i>
          <h5 class="mt-3">التمريض</h5>
          <p class="mb-0">نوبات التمريض حسب الأقسام</p>
        </div>
      </a>
    </div>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>