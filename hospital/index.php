<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
include __DIR__ . '/../includes/header.php';
$page_title = 'المستشفى';
?>
<main class="container section-padding">
    <h2 class="mb-4">قسم المستشفى</h2>
    <p class="text-white">مرحباً، <?php echo htmlspecialchars($_SESSION['user']['name'] ?? ''); ?> — يمكنك حجز كشف، استعراض مواعيدك، ومعرفة الصيدلية.</p>

    <div class="row g-4">
        <div class="col-md-4" data-aos="fade-up">
            <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/hospital/clinics.php">
                <div class="feature-card h-100">
                    <i class="bi bi-clipboard2-pulse"></i>
                    <h5 class="mt-3">العيادات</h5>
                    <p class="mb-0">استعرض العيادات المتاحة وحجوزات الكشوف</p>
                </div>
            </a>
        </div>
        <div class="col-md-4" data-aos="fade-up">
            <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/hospital/book_appointment.php">
                <div class="feature-card h-100">
                    <i class="bi bi-calendar-plus"></i>
                    <h5 class="mt-3">حجز كشف</h5>
                    <p class="mb-0">احجز موعداً لدى العيادة المختارة</p>
                </div>
            </a>
        </div>
        <div class="col-md-4" data-aos="fade-up">
            <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/hospital/appointments.php">
                <div class="feature-card h-100">
                    <i class="bi bi-calendar-event"></i>
                    <h5 class="mt-3">مواعيدي</h5>
                    <p class="mb-0">قائمة مواعيدك وحالة الحجز</p>
                </div>
            </a>
        </div>
        <div class="col-md-4" data-aos="fade-up">
            <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/hospital/tests.php">
                <div class="feature-card h-100">
                    <i class="bi bi-file-medical"></i>
                    <h5 class="mt-3">الأشعة والتحاليل</h5>
                    <p class="mb-0">نتائج الأشعة والتحاليل المخبرية</p>
                </div>
            </a>
        </div>
        <div class="col-md-4" data-aos="fade-up">
            <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/hospital/followups.php">
                <div class="feature-card h-100">
                    <i class="bi bi-journal-medical"></i>
                    <h5 class="mt-3">المتابعة والسجل</h5>
                    <p class="mb-0">متابعة الحالة والسجل الطبي</p>
                </div>
            </a>
        </div>
        <div class="col-md-4" data-aos="fade-up">
            <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/hospital/pharmacy.php">
                <div class="feature-card h-100">
                    <i class="bi bi-capsule"></i>
                    <h5 class="mt-3">الصيدلية الداخلية</h5>
                    <p class="mb-0">طلب صرف الأدوية ومتابعة الطلبات</p>
                </div>
            </a>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php';
