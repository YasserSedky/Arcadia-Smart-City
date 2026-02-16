<?php
require_once __DIR__ . '/../backend/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pdo = DB::conn();
// Get patient ID for current user
$user_phone = $_SESSION['user']['phone'] ?? '';
$pStmt = $pdo->prepare('SELECT id FROM hospital_patients WHERE phone = ? LIMIT 1');
$pStmt->execute([$user_phone]);
$pRow = $pStmt->fetch();
$patient_id = $pRow ? (int)$pRow['id'] : null;

// Get all radiology and lab tests for this patient
$tests = [];
if ($patient_id) {
    try {
        $stmt = $pdo->prepare(
            'SELECT o.*, t.name_ar AS test_name, t.category, t.preparation_notes,
                    c.name_ar AS clinic_name, d.name_ar AS dept_name,
                    a.starts_at AS appointment_date
             FROM hospital_test_orders o
             JOIN hospital_test_types t ON t.id = o.test_type_id
             LEFT JOIN hospital_appointments a ON a.id = o.appointment_id
             LEFT JOIN hospital_clinics c ON c.id = a.clinic_id
             LEFT JOIN hospital_departments d ON d.id = c.department_id
             WHERE o.patient_id = ?
             ORDER BY o.created_at DESC'
        );
        $stmt->execute([$patient_id]);
        $tests = $stmt->fetchAll();
    } catch (PDOException $e) {
        $err = $e->getMessage();
    }
}

include __DIR__ . '/../includes/header.php';
?>
<main class="container section-padding">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">نتائج الأشعة والتحاليل</h2>
        <a href="<?php echo APP_BASE; ?>/hospital/index.php" class="btn btn-outline-light">رجوع</a>
    </div>

    <?php if (!empty($err)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($err); ?></div>
    <?php endif; ?>

    <?php if (empty($tests)): ?>
        <div class="feature-card">
            <p class="mb-0">لا توجد أشعة أو تحاليل حتى الآن.</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($tests as $test): ?>
                <div class="col-md-6" data-aos="fade-up">
                    <div class="feature-card h-100">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h5 class="mb-0"><?php echo htmlspecialchars($test['test_name']); ?></h5>
                            <span class="badge <?php
                                                echo match ($test['status']) {
                                                    'completed' => 'bg-success',
                                                    'scheduled' => 'bg-info',
                                                    'cancelled' => 'bg-danger',
                                                    default => 'bg-warning'
                                                };
                                                ?>">
                                <?php echo match ($test['status']) {
                                    'completed' => 'مكتمل',
                                    'scheduled' => 'مجدول',
                                    'cancelled' => 'ملغي',
                                    default => 'قيد الانتظار'
                                }; ?>
                            </span>
                        </div>

                        <p class="mb-2">
                            <strong>النوع:</strong>
                            <?php echo $test['category'] === 'radiology' ? 'أشعة' : 'تحليل مخبري'; ?>
                        </p>

                        <?php if ($test['appointment_date']): ?>
                            <p class="mb-2">
                                <strong>موعد الزيارة:</strong>
                                <?php echo date('Y/m/d', strtotime($test['appointment_date'])); ?>
                            </p>
                        <?php endif; ?>

                        <?php if ($test['scheduled_for']): ?>
                            <p class="mb-2">
                                <strong>موعد <?php echo $test['category'] === 'radiology' ? 'الأشعة' : 'التحليل'; ?>:</strong>
                                <?php echo date('Y/m/d H:i', strtotime($test['scheduled_for'])); ?>
                            </p>
                        <?php endif; ?>

                        <?php if ($test['clinic_name']): ?>
                            <p class="mb-2">
                                <strong>العيادة:</strong>
                                <?php echo htmlspecialchars($test['dept_name'] . ' - ' . $test['clinic_name']); ?>
                            </p>
                        <?php endif; ?>

                        <?php if ($test['status'] === 'scheduled' && $test['preparation_notes']): ?>
                            <div class="alert alert-info mb-2">
                                <strong>تعليمات التحضير:</strong><br>
                                <?php echo nl2br(htmlspecialchars($test['preparation_notes'])); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($test['status'] === 'completed'): ?>
                            <?php if ($test['results_text']): ?>
                                <div class="mb-2">
                                    <strong>النتيجة:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($test['results_text'])); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($test['results_file']): ?>
                                <div class="mb-2">
                                    <a href="<?php echo APP_BASE; ?>/uploads/results/<?php echo htmlspecialchars($test['results_file']); ?>"
                                        class="btn btn-sm btn-outline-light" target="_blank">
                                        <i class="bi bi-download me-1"></i>
                                        تحميل النتيجة
                                    </a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php';
