<?php
$page_title = 'مواعيد العيادات';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'hospital_admin'])) {
  redirect('/dashboard/index.php');
}
$pdo = DB::conn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $clinic_id = (int)($_POST['clinic_id'] ?? 0);
  $patient_id = (int)($_POST['patient_id'] ?? 0);
  $starts_at = $_POST['starts_at'] ?? '';
  $notes = trim($_POST['notes'] ?? '');
  if ($clinic_id > 0 && $patient_id > 0 && $starts_at !== '') {
    $stmt = $pdo->prepare('INSERT INTO hospital_appointments(clinic_id, patient_id, starts_at, notes) VALUES(?,?,?,?)');
    try {
      $stmt->execute([$clinic_id, $patient_id, $starts_at, $notes ?: null]);
    } catch (PDOException $e) {
    }
  }
  redirect('/dashboard/hospital/appointments.php');
}

$clinics = $pdo->query('SELECT c.id, CONCAT(d.name_ar, " - ", c.name_ar) AS name FROM hospital_clinics c JOIN hospital_departments d ON d.id=c.department_id ORDER BY d.name_ar, c.name_ar')->fetchAll();
$patients = $pdo->query('SELECT id, full_name FROM hospital_patients ORDER BY full_name')->fetchAll();

$rows = $pdo->query('SELECT a.*, c.name_ar AS clinic_name, d.name_ar AS dept_name, p.full_name AS patient_name
  FROM hospital_appointments a
  JOIN hospital_clinics c ON c.id=a.clinic_id
  JOIN hospital_departments d ON d.id=c.department_id
  JOIN hospital_patients p ON p.id=a.patient_id
  ORDER BY a.starts_at DESC LIMIT 100')->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">المواعيد</h3>
    <a href="<?php echo APP_BASE; ?>/dashboard/hospital/index.php" class="btn btn-outline-light">رجوع</a>
  </div>

  <div class="feature-card mb-4">
    <form method="post" class="row g-3">
      <div class="col-md-5">
        <label class="form-label">العيادة</label>
        <select name="clinic_id" class="form-select" required>
          <option value="">اختر العيادة</option>
          <?php foreach ($clinics as $c): ?>
            <option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">المريض</label>
        <select name="patient_id" class="form-select" required>
          <option value="">اختر المريض</option>
          <?php foreach ($patients as $p): ?>
            <option value="<?php echo (int)$p['id']; ?>"><?php echo htmlspecialchars($p['full_name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">التاريخ والوقت</label>
        <input type="datetime-local" name="starts_at" class="form-control" required>
      </div>
      <div class="col-12">
        <label class="form-label">ملاحظات</label>
        <input type="text" name="notes" class="form-control" placeholder="اختياري">
      </div>
      <div class="col-12 d-grid d-md-flex gap-2">
        <button class="btn btn-gradient" type="submit">إضافة موعد</button>
      </div>
    </form>
  </div>

  <div class="table-responsive feature-card">
    <table class="table table-dark table-striped align-middle mb-0">
      <thead>
        <tr>
          <th>القسم</th>
          <th>العيادة</th>
          <th>المريض</th>
          <th>الوقت</th>
          <th>الحالة</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?php echo htmlspecialchars($r['dept_name']); ?></td>
            <td><?php echo htmlspecialchars($r['clinic_name']); ?></td>
            <td><?php echo htmlspecialchars($r['patient_name']); ?></td>
            <td><?php echo htmlspecialchars($r['starts_at']); ?></td>
            <td><?php echo htmlspecialchars($r['status']); ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?><tr>
            <td colspan="5" class="text-center text-muted">لا توجد مواعيد</td>
          </tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
