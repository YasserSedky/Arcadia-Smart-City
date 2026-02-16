<?php
$page_title = 'الطوارئ';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'hospital_admin'])) {
  redirect('/dashboard/index.php');
}
$pdo = DB::conn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['patient_name'] ?? '');
  $severity = $_POST['severity'] ?? 'medium';
  $notes = trim($_POST['notes'] ?? '');
  if ($name !== '') {
    $stmt = $pdo->prepare('INSERT INTO emergency_cases(patient_name, severity, arrived_at, notes) VALUES(?,?,NOW(),?)');
    try {
      $stmt->execute([$name, $severity, $notes ?: null]);
    } catch (PDOException $e) {
    }
  }
  redirect('/dashboard/hospital/emergency.php');
}

$rows = $pdo->query('SELECT * FROM emergency_cases ORDER BY arrived_at DESC LIMIT 200')->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">قسم الطوارئ</h3>
    <a href="<?php echo APP_BASE; ?>/dashboard/hospital/index.php" class="btn btn-outline-light">رجوع</a>
  </div>

  <div class="feature-card mb-4">
    <form method="post" class="row g-3">
      <div class="col-md-5"><label class="form-label">اسم الحالة</label><input class="form-control" name="patient_name" required></div>
      <div class="col-md-3"><label class="form-label">درجة الخطورة</label>
        <select name="severity" class="form-select">
          <option value="low">منخفضة</option>
          <option value="medium" selected>متوسطة</option>
          <option value="high">مرتفعة</option>
          <option value="critical">حرجة</option>
        </select>
      </div>
      <div class="col-md-4"><label class="form-label">ملاحظات</label><input class="form-control" name="notes"></div>
      <div class="col-12 d-grid d-md-flex gap-2"><button class="btn btn-gradient" type="submit">تسجيل</button></div>
    </form>
  </div>

  <div class="table-responsive feature-card">
    <table class="table table-dark table-striped align-middle mb-0">
      <thead>
        <tr>
          <th>الاسم</th>
          <th>الخطورة</th>
          <th>الوصول</th>
          <th>الحالة</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?php echo htmlspecialchars($r['patient_name']); ?></td>
            <td><?php echo htmlspecialchars($r['severity']); ?></td>
            <td><?php echo htmlspecialchars($r['arrived_at']); ?></td>
            <td><?php echo htmlspecialchars($r['status']); ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?><tr>
            <td colspan="4" class="text-center text-muted">لا توجد حالات</td>
          </tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
