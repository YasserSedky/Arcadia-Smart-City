<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
include __DIR__ . '/../includes/header.php';
$page_title = 'المدرسة';
?>
<main class="container section-padding">
    <h2 class="mb-4">قسم المدرسة</h2>
    <p class="text-white">مرحباً، <?php echo htmlspecialchars($_SESSION['user']['name'] ?? ''); ?> — استعرض الصفوف، المراحل، الطلاب والمعلمين.</p>

    <div class="row g-4 mt-3">
        <div class="col-md-3" data-aos="fade-up">
            <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/school/classes.php">
                <div class="feature-card h-100">
                    <i class="bi bi-journal-bookmark"></i>
                    <h5 class="mt-3">الصفوف</h5>
                    <p class="mb-0">قائمة الصفوف والمناهج</p>
                </div>
            </a>
        </div>
        <div class="col-md-3" data-aos="fade-up">
            <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/school/stages.php">
                <div class="feature-card h-100">
                    <i class="bi bi-building"></i>
                    <h5 class="mt-3">المراحل</h5>
                    <p class="mb-0">الحضانة، ابتدائي، إعدادي، ثانوي</p>
                </div>
            </a>
        </div>
        <div class="col-md-3" data-aos="fade-up">
            <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/school/students.php">
                <div class="feature-card h-100">
                    <i class="bi bi-people"></i>
                    <h5 class="mt-3">الطلاب</h5>
                    <p class="mb-0">سجل الطلاب والنتائج</p>
                </div>
            </a>
        </div>
        <div class="col-md-3" data-aos="fade-up">
            <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/school/teachers.php">
                <div class="feature-card h-100">
                    <i class="bi bi-person-badge"></i>
                    <h5 class="mt-3">المعلمين</h5>
                    <p class="mb-0">قائمة الكادر التعليمي والتواصل</p>
                </div>
            </a>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php';
