<?php
$page_title = 'جدولة العمليات';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'hospital_admin'])) {
  redirect('/dashboard/index.php');
}
$pdo = DB::conn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $patient_id = (int)($_POST['patient_id'] ?? 0);
  $department_id = (int)($_POST['department_id'] ?? 0);
  $title = trim($_POST['title'] ?? '');
  $scheduled_at = $_POST['scheduled_at'] ?? '';
  $room = trim($_POST['room_label'] ?? '');
  $notes = trim($_POST['notes'] ?? '');
  if ($patient_id > 0 && $title !== '' && $scheduled_at !== '') {
    $stmt = $pdo->prepare('INSERT INTO surgeries(patient_id, department_id, title, scheduled_at, room_label, notes) VALUES(?,?,?,?,?,?)');
    try {
      $stmt->execute([$patient_id, $department_id ?: null, $title, $scheduled_at, $room ?: null, $notes ?: null]);
    } catch (PDOException $e) {
    }
  }
  redirect('/dashboard/hospital/operations.php');
}

$patients = $pdo->query('SELECT id, full_name FROM hospital_patients ORDER BY full_name')->fetchAll();
$deps = $pdo->query('SELECT id, name_ar FROM hospital_departments ORDER BY name_ar')->fetchAll();
$rows = $pdo->query('SELECT s.*, p.full_name AS patient_name, d.name_ar AS dept_name FROM surgeries s LEFT JOIN hospital_patients p ON p.id=s.patient_id LEFT JOIN hospital_departments d ON d.id=s.department_id ORDER BY s.scheduled_at DESC LIMIT 100')->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">العمليات</h3>
    <a href="<?php echo APP_BASE; ?>/dashboard/hospital/index.php" class="btn btn-outline-light">رجوع</a>
  </div>

  <div class="feature-card mb-4">
    <form method="post" class="row g-3">
      <div class="col-md-4"><label class="form-label">المريض</label>
        <select name="patient_id" class="form-select" required>
          <option value="">اختر</option>
          <?php foreach ($patients as $p): ?><option value="<?php echo (int)$p['id']; ?>"><?php echo htmlspecialchars($p['full_name']); ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3"><label class="form-label">القسم</label>
        <select name="department_id" class="form-select">
          <option value="">—</option>
          <?php foreach ($deps as $d): ?><option value="<?php echo (int)$d['id']; ?>"><?php echo htmlspecialchars($d['name_ar']); ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-5"><label class="form-label">العنوان</label><input class="form-control" name="title" required placeholder="مثال: استئصال المرارة"></div>
      <div class="col-md-3"><label class="form-label">الوقت</label><input type="datetime-local" class="form-control" name="scheduled_at" required></div>
      <div class="col-md-2"><label class="form-label">غرفة</label><input class="form-control" name="room_label" placeholder="OR-1"></div>
      <div class="col-md-7"><label class="form-label">ملاحظات</label><input class="form-control" name="notes"></div>
      <div class="col-12 d-grid d-md-flex gap-2"><button class="btn btn-gradient" type="submit">إضافة</button></div>
    </form>
  </div>

  <div class="table-responsive feature-card">
    <table class="table table-dark table-striped align-middle mb-0">
      <thead>
        <tr>
          <th>المريض</th>
          <th>القسم</th>
          <th>العنوان</th>
          <th>الوقت</th>
          <th>الغرفة</th>
          <th>الحالة</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?php echo htmlspecialchars($r['patient_name']); ?></td>
            <td><?php echo htmlspecialchars($r['dept_name'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($r['title']); ?></td>
            <td><?php echo htmlspecialchars($r['scheduled_at']); ?></td>
            <td><?php echo htmlspecialchars($r['room_label'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($r['status']); ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?><tr>
            <td colspan="6" class="text-center text-muted">لا توجد عمليات</td>
          </tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
