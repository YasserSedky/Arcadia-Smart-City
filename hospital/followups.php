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

// Get all follow-ups for this patient
$followups = [];
if ($patient_id) {
    try {
        $stmt = $pdo->prepare(
            'SELECT f.*,
                    c_orig.name_ar AS orig_clinic_name,
                    d_orig.name_ar AS orig_dept_name,
                    a_orig.starts_at AS orig_date,
                    c_follow.name_ar AS followup_clinic_name,
                    d_follow.name_ar AS followup_dept_name,
                    a_follow.starts_at AS followup_date
             FROM hospital_followups f
             JOIN hospital_appointments a_orig ON a_orig.id = f.original_appointment_id
             JOIN hospital_clinics c_orig ON c_orig.id = a_orig.clinic_id
             JOIN hospital_departments d_orig ON d_orig.id = c_orig.department_id
             LEFT JOIN hospital_appointments a_follow ON a_follow.id = f.followup_appointment_id
             LEFT JOIN hospital_clinics c_follow ON c_follow.id = a_follow.clinic_id
             LEFT JOIN hospital_departments d_follow ON d_follow.id = c_follow.department_id
             WHERE f.patient_id = ?
             ORDER BY f.created_at DESC'
        );
        $stmt->execute([$patient_id]);
        $followups = $stmt->fetchAll();

        // Get patient records/history
        $stmt = $pdo->prepare(
            'SELECT a.*, c.name_ar AS clinic_name, d.name_ar AS dept_name,
                    GROUP_CONCAT(DISTINCT t.name_ar SEPARATOR \', \') AS tests,
                    GROUP_CONCAT(DISTINCT ph.name_ar SEPARATOR \', \') AS medications
             FROM hospital_appointments a
             JOIN hospital_clinics c ON c.id = a.clinic_id
             JOIN hospital_departments d ON d.id = c.department_id
             LEFT JOIN hospital_test_orders o ON o.appointment_id = a.id
             LEFT JOIN hospital_test_types t ON t.id = o.test_type_id
             LEFT JOIN pharmacy_requests pr ON pr.patient_id = a.patient_id AND DATE(pr.created_at) = DATE(a.starts_at)
             LEFT JOIN pharmacy_items ph ON ph.id = pr.item_id
             WHERE a.patient_id = ?
             GROUP BY a.id
             ORDER BY a.starts_at DESC'
        );
        $stmt->execute([$patient_id]);
        $history = $stmt->fetchAll();
    } catch (PDOException $e) {
        $err = $e->getMessage();
    }
}

include __DIR__ . '/../includes/header.php';
?>
<main class="container section-padding">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">المتابعة والسجل الطبي</h2>
        <a href="<?php echo APP_BASE; ?>/hospital/index.php" class="btn btn-outline-light">رجوع</a>
    </div>

    <?php if (!empty($err)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($err); ?></div>
    <?php endif; ?>

    <?php if (!empty($followups)): ?>
        <h3 class="mb-3">متابعات مطلوبة</h3>
        <div class="row g-4 mb-5">
            <?php foreach ($followups as $f): ?>
                <div class="col-md-6" data-aos="fade-up">
                    <div class="feature-card h-100">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h5 class="mb-0">
                                متابعة <?php echo htmlspecialchars($f['orig_dept_name'] . ' - ' . $f['orig_clinic_name']); ?>
                            </h5>
                            <span class="badge <?php
                                                echo match ($f['status']) {
                                                    'completed' => 'bg-success',
                                                    'scheduled' => 'bg-info',
                                                    'cancelled' => 'bg-danger',
                                                    default => 'bg-warning'
                                                };
                                                ?>">
                                <?php echo match ($f['status']) {
                                    'completed' => 'مكتمل',
                                    'scheduled' => 'مجدول',
                                    'cancelled' => 'ملغي',
                                    default => 'قيد الانتظار'
                                }; ?>
                            </span>
                        </div>

                        <p class="mb-2">
                            <strong>الزيارة السابقة:</strong>
                            <?php echo date('Y/m/d', strtotime($f['orig_date'])); ?>
                        </p>

                        <?php if ($f['followup_date']): ?>
                            <p class="mb-2">
                                <strong>موعد المتابعة:</strong>
                                <?php echo date('Y/m/d', strtotime($f['followup_date'])); ?>
                                في <?php echo htmlspecialchars($f['followup_dept_name'] . ' - ' . $f['followup_clinic_name']); ?>
                            </p>
                        <?php endif; ?>

                        <?php if ($f['doctor_notes']): ?>
                            <p class="mb-0">
                                <strong>ملاحظات الطبيب:</strong><br>
                                <?php echo nl2br(htmlspecialchars($f['doctor_notes'])); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($history)): ?>
        <h3 class="mb-3">السجل الطبي</h3>
        <div class="table-responsive feature-card">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>القسم - العيادة</th>
                        <th>الفحوصات</th>
                        <th>الأدوية</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $h): ?>
                        <tr>
                            <td><?php echo date('Y/m/d', strtotime($h['starts_at'])); ?></td>
                            <td><?php echo htmlspecialchars($h['dept_name'] . ' - ' . $h['clinic_name']); ?></td>
                            <td><?php echo $h['tests'] ? htmlspecialchars($h['tests']) : '—'; ?></td>
                            <td><?php echo $h['medications'] ? htmlspecialchars($h['medications']) : '—'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="feature-card">
            <p class="mb-0">لا يوجد سجل طبي حتى الآن.</p>
        </div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php';
