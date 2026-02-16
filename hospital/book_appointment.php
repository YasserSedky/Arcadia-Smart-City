<?php
require_once __DIR__ . '/../backend/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
include __DIR__ . '/../includes/header.php';

$pdo = DB::conn();
$clinic_id = isset($_GET['clinic_id']) ? (int)$_GET['clinic_id'] : null;
$clinics = $pdo->query('SELECT c.id, CONCAT(d.name_ar, " - ", c.name_ar) AS name FROM hospital_clinics c JOIN hospital_departments d ON d.id=c.department_id ORDER BY d.name_ar, c.name_ar')->fetchAll();

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clinic_id = (int)($_POST['clinic_id'] ?? 0);
    $user_id = $_SESSION['user']['id'];
    $starts_at = trim($_POST['starts_at'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (!$clinic_id || $starts_at === '') {
        $err = 'يرجى اختيار العيادة وتحديد موعد.';
    } else {
        // ensure a hospital_patients record exists for this user (match by phone or name)
        $pStmt = $pdo->prepare('SELECT id FROM hospital_patients WHERE phone = ? LIMIT 1');
        $pStmt->execute([$_SESSION['user']['phone'] ?? '']);
        $pRow = $pStmt->fetch();
        if ($pRow) {
            $patient_id = (int)$pRow['id'];
        } else {
            // create a minimal patient record
            $ins = $pdo->prepare('INSERT INTO hospital_patients(full_name, phone, national_id, date_of_birth, gender) VALUES(?,?,?,?,?)');
            $ins->execute([$_SESSION['user']['name'] ?? '', $_SESSION['user']['phone'] ?? '', null, null, null]);
            $patient_id = (int)$pdo->lastInsertId();
        }

        $stmt = $pdo->prepare('INSERT INTO hospital_appointments(clinic_id, patient_id, starts_at, notes) VALUES(?,?,?,?)');
        $stmt->execute([$clinic_id, $patient_id, $starts_at, $notes]);
        $ok = 'تم حجز الموعد بنجاح';
    }
}

?>
<main class="container section-padding" style="max-width:720px;">
    <div class="feature-card auth-card" data-aos="fade-up">
        <h2 class="mb-4">حجز كشف</h2>
        <?php if (!empty($err)): ?><div class="alert alert-danger"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>
        <?php if (!empty($ok)): ?><div class="alert alert-success"><?php echo htmlspecialchars($ok); ?></div><?php endif; ?>

        <form method="post" action="<?php echo APP_BASE; ?>/hospital/book_appointment.php" class="row g-3">
            <div class="col-12">
                <label class="form-label">اختر العيادة</label>
                <select name="clinic_id" class="form-select" required>
                    <option value="">-- اختر --</option>
                    <?php foreach ($clinics as $c): ?>
                        <option value="<?php echo (int)$c['id']; ?>" <?php echo ($clinic_id == $c['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">تاريخ ووقت الموعد</label>
                <input type="datetime-local" name="starts_at" class="form-control" required>
            </div>
            <div class="col-12">
                <label class="form-label">ملاحظات</label>
                <textarea name="notes" class="form-control" rows="3"></textarea>
            </div>
            <div class="col-12 d-grid d-md-flex gap-2 auth-actions">
                <button class="btn btn-gradient" type="submit">حجز الموعد</button>
                <a class="btn btn-outline-light" href="<?php echo APP_BASE; ?>/hospital/index.php">الغاء</a>
            </div>
        </form>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php';
