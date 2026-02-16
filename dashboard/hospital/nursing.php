<?php
$page_title = 'نوبات التمريض';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'hospital_admin'])) {
  redirect('/dashboard/index.php');
}
$pdo = DB::conn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $staff_user_id = (int)($_POST['staff_user_id'] ?? 0);
  $department_id = (int)($_POST['department_id'] ?? 0);
  $shift_date = $_POST['shift_date'] ?? '';
  $shift_type = $_POST['shift_type'] ?? 'morning';
  $notes = trim($_POST['notes'] ?? '');
  if ($staff_user_id > 0 && $shift_date !== '') {
    $stmt = $pdo->prepare('INSERT INTO nursing_shifts(staff_user_id, department_id, shift_date, shift_type, notes) VALUES(?,?,?,?,?)');
    try {
      $stmt->execute([$staff_user_id, $department_id ?: null, $shift_date, $shift_type, $notes ?: null]);
    } catch (PDOException $e) {
    }
  }
  redirect('/dashboard/hospital/nursing.php');
}

$nurses = $pdo->query("SELECT u.id, u.full_name FROM users u JOIN roles r ON r.id=u.role_id WHERE r.code IN ('nurse') ORDER BY u.full_name")->fetchAll();
$deps = $pdo->query('SELECT id, name_ar FROM hospital_departments ORDER BY name_ar')->fetchAll();
$rows = $pdo->query('SELECT s.*, u.full_name, d.name_ar AS dept_name FROM nursing_shifts s JOIN users u ON u.id=s.staff_user_id LEFT JOIN hospital_departments d ON d.id=s.department_id ORDER BY s.shift_date DESC LIMIT 100')->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">نوبات التمريض</h3>
    <a href="<?php echo APP_BASE; ?>/dashboard/hospital/index.php" class="btn btn-outline-light">رجوع</a>
  </div>

  <div class="feature-card mb-4">
    <form method="post" class="row g-3">
      <div class="col-md-4"><label class="form-label">الممرض</label>
        <select name="staff_user_id" class="form-select" required>
          <option value="">اختر</option>
          <?php foreach ($nurses as $n): ?><option value="<?php echo (int)$n['id']; ?>"><?php echo htmlspecialchars($n['full_name']); ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3"><label class="form-label">القسم</label>
        <select name="department_id" class="form-select">
          <option value="">—</option>
          <?php foreach ($deps as $d): ?><option value="<?php echo (int)$d['id']; ?>"><?php echo htmlspecialchars($d['name_ar']); ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2"><label class="form-label">التاريخ</label><input type="date" class="form-control" name="shift_date" required></div>
      <div class="col-md-2"><label class="form-label">الفترة</label>
        <select name="shift_type" class="form-select">
          <option value="morning">صباحية</option>
          <option value="evening">مسائية</option>
          <option value="night">ليلية</option>
        </select>
      </div>
      <div class="col-md-12"><label class="form-label">ملاحظات</label><input class="form-control" name="notes"></div>
      <div class="col-12 d-grid d-md-flex gap-2"><button class="btn btn-gradient" type="submit">إضافة</button></div>
    </form>
  </div>

  <div class="table-responsive feature-card">
    <table class="table table-dark table-striped align-middle mb-0">
      <thead>
        <tr>
          <th>الممرض</th>
          <th>القسم</th>
          <th>التاريخ</th>
          <th>الفترة</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?php echo htmlspecialchars($r['full_name']); ?></td>
            <td><?php echo htmlspecialchars($r['dept_name'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($r['shift_date']); ?></td>
            <td><?php echo htmlspecialchars($r['shift_type']); ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?><tr>
            <td colspan="4" class="text-center text-muted">لا توجد نوبات</td>
          </tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
