<?php
$page_title = 'مرافق النادي';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'sports_admin'])) {
  redirect('/dashboard/index.php');
}
$pdo = DB::conn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name_ar'] ?? '');
  $type = $_POST['type'] ?? 'field';
  $cap = $_POST['capacity'] !== '' ? (int)$_POST['capacity'] : null;
  $loc = trim($_POST['location_label'] ?? '');
  if ($name !== '') {
    $stmt = $pdo->prepare('INSERT INTO sports_facilities(name_ar, type, capacity, location_label) VALUES(?,?,?,?)');
    try {
      $stmt->execute([$name, $type, $cap, $loc ?: null]);
    } catch (PDOException $e) {
    }
  }
  redirect('/dashboard/sports/facilities.php');
}

$rows = $pdo->query('SELECT * FROM sports_facilities ORDER BY type, name_ar')->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">المرافق</h3>
    <a href="<?php echo APP_BASE; ?>/dashboard/sports/index.php" class="btn btn-outline-light">رجوع</a>
  </div>

  <div class="feature-card mb-4">
    <form method="post" class="row g-3">
      <div class="col-md-4"><label class="form-label">الاسم</label><input class="form-control" name="name_ar" required></div>
      <div class="col-md-3"><label class="form-label">النوع</label>
        <select name="type" class="form-select">
          <option value="field">ملعب</option>
          <option value="pool">مسبح</option>
          <option value="gym">صالة رياضية</option>
          <option value="court">ملعب مغلق</option>
        </select>
      </div>
      <div class="col-md-2"><label class="form-label">السعة</label><input type="number" class="form-control" name="capacity"></div>
      <div class="col-md-2"><label class="form-label">الموقع</label><input class="form-control" name="location_label"></div>
      <div class="col-md-1 d-grid"><label class="form-label">&nbsp;</label><button class="btn btn-gradient" type="submit">إضافة</button></div>
    </form>
  </div>

  <div class="table-responsive feature-card">
    <table class="table table-dark table-striped align-middle mb-0">
      <thead>
        <tr>
          <th>الاسم</th>
          <th>النوع</th>
          <th>السعة</th>
          <th>الموقع</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?php echo htmlspecialchars($r['name_ar']); ?></td>
            <td><?php echo htmlspecialchars($r['type']); ?></td>
            <td><?php echo $r['capacity'] !== null ? (int)$r['capacity'] : '—'; ?></td>
            <td><?php echo htmlspecialchars($r['location_label'] ?? ''); ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?><tr>
            <td colspan="4" class="text-center text-muted">لا توجد مرافق</td>
          </tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
