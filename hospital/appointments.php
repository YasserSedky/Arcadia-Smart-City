<?php
require_once __DIR__ . '/../backend/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
include __DIR__ . '/../includes/header.php';

$pdo = DB::conn();
// resolve hospital patient id for current logged-in user
$user_phone = $_SESSION['user']['phone'] ?? '';
$pStmt = $pdo->prepare('SELECT id FROM hospital_patients WHERE phone = ? LIMIT 1');
$pStmt->execute([$user_phone]);
$pRow = $pStmt->fetch();
if ($pRow) {
    $patient_id = (int)$pRow['id'];
} else {
    $patient_id = null;
}

// Cancellation
if (isset($_GET['cancel_id']) && $patient_id) {
    $id = (int)$_GET['cancel_id'];
    $pdo->prepare('DELETE FROM hospital_appointments WHERE id = ? AND patient_id = ?')->execute([$id, $patient_id]);
    redirect('/hospital/appointments.php');
}

if ($patient_id) {
    $rows = $pdo->prepare('SELECT a.*, c.name_ar AS clinic_name, d.name_ar AS dept_name, p.full_name as patient_name FROM hospital_appointments a JOIN hospital_clinics c ON c.id=a.clinic_id JOIN hospital_departments d ON d.id=c.department_id JOIN hospital_patients p ON p.id=a.patient_id WHERE a.patient_id = ? ORDER BY a.starts_at DESC');
    $rows->execute([$patient_id]);
    $appointments = $rows->fetchAll();
} else {
    $appointments = [];
}

?>
<main class="container section-padding">
    <h2 class="mb-4">مواعيدي</h2>
    <div class="row g-4">
        <?php if (empty($appointments)): ?>
            <div class="col-12">
                <div class="info-card">لا توجد مواعيد حالياً</div>
            </div>
        <?php else: ?>
            <?php foreach ($appointments as $a): ?>
                <div class="col-md-6" data-aos="fade-up">
                    <div class="feature-card h-100">
                        <h5><?php echo htmlspecialchars($a['dept_name'] . ' - ' . $a['clinic_name']); ?></h5>
                        <p class="mb-2">الموعد: <?php echo htmlspecialchars($a['starts_at']); ?></p>
                        <p class="mb-2">حالة: <?php echo htmlspecialchars($a['status'] ?? 'مؤكد'); ?></p>
                        <div class="d-flex gap-2">
                            <a class="btn btn-outline-light" href="<?php echo APP_BASE; ?>/hospital/appointments.php?cancel_id=<?php echo (int)$a['id']; ?>" onclick="return confirm('هل تريد إلغاء هذا الموعد؟');">إلغاء</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php';
