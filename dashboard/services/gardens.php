<?php
$page_title = 'خدمات الحدائق';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'services_admin', 'residential_admin'])) {
  redirect('/dashboard/index.php');
}
$pdo = DB::conn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $area = trim($_POST['area_label'] ?? '');
  $task = trim($_POST['task'] ?? '');
  $date = $_POST['scheduled_date'] ?? '';
  $notes = trim($_POST['notes'] ?? '');
  if ($area !== '' && $task !== '' && $date !== '') {
    $stmt = $pdo->prepare('INSERT INTO garden_tasks(area_label, task, scheduled_date, notes) VALUES(?,?,?,?)');
    try {
      $stmt->execute([$area, $task, $date, $notes ?: null]);
    } catch (PDOException $e) {
    }
  }
  redirect('/dashboard/services/gardens.php');
}

$rows = $pdo->query('SELECT * FROM garden_tasks ORDER BY scheduled_date DESC LIMIT 200')->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">خدمات الحدائق</h3>
    <a href="<?php echo APP_BASE; ?>/dashboard/services/index.php" class="btn btn-outline-light">رجوع</a>
  </div>

  <div class="feature-card mb-4">
    <form method="post" class="row g-3">
      <div class="col-md-4"><label class="form-label">المنطقة</label><input class="form-control" name="area_label" required placeholder="حديقة A"></div>
      <div class="col-md-4"><label class="form-label">المهمة</label><input class="form-control" name="task" required placeholder="قص العشب"></div>
      <div class="col-md-3"><label class="form-label">التاريخ</label><input type="date" class="form-control" name="scheduled_date" required></div>
      <div class="col-md-12"><label class="form-label">ملاحظات</label><input class="form-control" name="notes"></div>
      <div class="col-12 d-grid d-md-flex gap-2"><button class="btn btn-gradient" type="submit">إضافة</button></div>
    </form>
  </div>

  <div class="table-responsive feature-card">
    <table class="table table-dark table-striped align-middle mb-0">
      <thead>
        <tr>
          <th>المنطقة</th>
          <th>المهمة</th>
          <th>التاريخ</th>
          <th>الحالة</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?php echo htmlspecialchars($r['area_label']); ?></td>
            <td><?php echo htmlspecialchars($r['task']); ?></td>
            <td><?php echo htmlspecialchars($r['scheduled_date']); ?></td>
            <td><?php echo htmlspecialchars($r['status']); ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?><tr>
            <td colspan="4" class="text-center text-muted">لا توجد مهام</td>
          </tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
