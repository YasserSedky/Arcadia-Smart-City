<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
include __DIR__ . '/../includes/header.php';
$page_title = 'المول';
?>
<main class="container section-padding">
    <h2 class="mb-4">قسم المول</h2>
    <p class="text-white">مرحباً، <?php echo htmlspecialchars($_SESSION['user']['name'] ?? ''); ?> — استعرض المتاجر، الوحدات، أو احجز قاعة للفعالية.</p>

    <div class="row g-4 mt-3">
        <div class="col-md-4" data-aos="fade-up">
            <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/mall/tenants.php">
                <div class="feature-card h-100">
                    <i class="bi bi-shop"></i>
                    <h5 class="mt-3">المحلات (Tenants)</h5>
                    <p class="mb-0">قائمة المحلات المستأجرة ومعلومات الاتصال.</p>
                </div>
            </a>
        </div>
        <div class="col-md-4" data-aos="fade-up">
            <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/mall/units.php">
                <div class="feature-card h-100">
                    <i class="bi bi-grid-1x2"></i>
                    <h5 class="mt-3">الوحدات / المساحات</h5>
                    <p class="mb-0">استعرض الوحدات المتاحة وخصائص كل وحدة.</p>
                </div>
            </a>
        </div>
        <div class="col-md-4" data-aos="fade-up">
            <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/mall/venues.php">
                <div class="feature-card h-100">
                    <i class="bi bi-building"></i>
                    <h5 class="mt-3">الفعاليات والقاعات</h5>
                    <p class="mb-0">حجوزات القاعات والفعاليات في المول.</p>
                </div>
            </a>
        </div>
        <div class="col-12" data-aos="fade-up">
            <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/mall/bookings.php">
                <div class="feature-card h-100">
                    <i class="bi bi-calendar-event"></i>
                    <h5 class="mt-3">حجوزاتي</h5>
                    <p class="mb-0">عرض الحجوزات التي قمت بها ومتابعة حالة الطلب.</p>
                </div>
            </a>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php';
