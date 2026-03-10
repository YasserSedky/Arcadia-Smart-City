<?php
$page_title = 'نوبات الحراسة';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'security_admin'])) {
  redirect('/dashboard/index.php');
}
$pdo = DB::conn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $gate_id = (int)($_POST['gate_id'] ?? 0);
  $guard_user_id = (int)($_POST['guard_user_id'] ?? 0);
  $shift_start = $_POST['shift_start'] ?? '';
  $shift_end = $_POST['shift_end'] ?? '';
  $notes = trim($_POST['notes'] ?? '');
  if ($gate_id > 0 && $guard_user_id > 0 && $shift_start !== '' && $shift_end !== '') {
    $stmt = $pdo->prepare('INSERT INTO guard_shifts(gate_id, guard_user_id, shift_start, shift_end, notes) VALUES(?,?,?,?,?)');
    try {
      $stmt->execute([$gate_id, $guard_user_id, $shift_start, $shift_end, $notes ?: null]);
    } catch (PDOException $e) {
    }
  }
  redirect('/dashboard/security/shifts.php');
}

$gates = $pdo->query('SELECT id, name_ar, code FROM gates ORDER BY code')->fetchAll();
$guards = $pdo->query("SELECT u.id, u.full_name FROM users u JOIN roles r ON r.id=u.role_id WHERE r.code IN ('security_admin','admin_staff') ORDER BY u.full_name")->fetchAll();
$rows = $pdo->query('SELECT s.*, g.code AS gate_code, u.full_name FROM guard_shifts s JOIN gates g ON g.id=s.gate_id JOIN users u ON u.id=s.guard_user_id ORDER BY s.shift_start DESC LIMIT 200')->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">نوبات الحراسة</h3>
    <a href="<?php echo APP_BASE; ?>/dashboard/security/index.php" class="btn btn-outline-light">رجوع</a>
  </div>

  <div class="feature-card mb-4">
    <form method="post" class="row g-3">
      <div class="col-md-3"><label class="form-label">البوابة</label>
        <select name="gate_id" class="form-select" required>
          <option value="">اختر</option>
          <?php foreach ($gates as $g): ?><option value="<?php echo (int)$g['id']; ?>"><?php echo htmlspecialchars($g['code'] . ' - ' . $g['name_ar']); ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4"><label class="form-label">الحارس</label>
        <select name="guard_user_id" class="form-select" required>
          <option value="">اختر</option>
          <?php foreach ($guards as $u): ?><option value="<?php echo (int)$u['id']; ?>"><?php echo htmlspecialchars($u['full_name']); ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2"><label class="form-label">بداية</label><input type="datetime-local" class="form-control" name="shift_start" required></div>
      <div class="col-md-2"><label class="form-label">نهاية</label><input type="datetime-local" class="form-control" name="shift_end" required></div>
      <div class="col-md-12"><label class="form-label">ملاحظات</label><input class="form-control" name="notes"></div>
      <div class="col-12 d-grid d-md-flex gap-2"><button class="btn btn-gradient" type="submit">إضافة</button></div>
    </form>
  </div>

  <div class="table-responsive feature-card">
    <table class="table table-dark table-striped align-middle mb-0">
      <thead>
        <tr>
          <th>البوابة</th>
          <th>الحارس</th>
          <th>البداية</th>
          <th>النهاية</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?php echo htmlspecialchars($r['gate_code']); ?></td>
            <td><?php echo htmlspecialchars($r['full_name']); ?></td>
            <td><?php echo htmlspecialchars($r['shift_start']); ?></td>
            <td><?php echo htmlspecialchars($r['shift_end']); ?></td>
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
