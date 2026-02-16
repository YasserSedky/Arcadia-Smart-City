<?php
require_once __DIR__ . '/../backend/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
include __DIR__ . '/../includes/header.php';

$pdo = DB::conn();
$clinics = $pdo->query('SELECT c.*, d.name_ar AS dept_name FROM hospital_clinics c JOIN hospital_departments d ON d.id=c.department_id ORDER BY d.name_ar, c.name_ar')->fetchAll();

?>
<main class="container section-padding">
    <h2 class="mb-4">العيادات</h2>
    <div class="row g-4">
        <?php foreach ($clinics as $c): ?>
            <div class="col-md-6" data-aos="fade-up">
                <div class="feature-card h-100">
                    <h5><?php echo htmlspecialchars($c['dept_name'] . ' - ' . $c['name_ar']); ?></h5>
                    <p class="mb-2">غرفة: <?php echo htmlspecialchars($c['room_label']); ?></p>
                    <div class="d-flex gap-2">
                        <a class="btn btn-gradient" href="<?php echo APP_BASE; ?>/hospital/book_appointment.php?clinic_id=<?php echo (int)$c['id']; ?>">حجز كشف</a>
                        <a class="btn btn-outline-light" href="<?php echo APP_BASE; ?>/dashboard/hospital/clinics.php">معلومات (للإدارة)</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php';
