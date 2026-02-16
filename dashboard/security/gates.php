<?php
$page_title = 'البوابات';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'security_admin'])) {
  redirect('/dashboard/index.php');
}
$pdo = DB::conn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $code = trim($_POST['code'] ?? '');
  $name = trim($_POST['name_ar'] ?? '');
  $loc = trim($_POST['location_label'] ?? '');
  if ($code !== '' && $name !== '') {
    $stmt = $pdo->prepare('INSERT INTO gates(code, name_ar, location_label) VALUES(?,?,?)');
    try {
      $stmt->execute([$code, $name, $loc ?: null]);
    } catch (PDOException $e) {
    }
  }
  redirect('/dashboard/security/gates.php');
}

$rows = $pdo->query('SELECT * FROM gates ORDER BY code')->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">البوابات</h3>
    <a href="<?php echo APP_BASE; ?>/dashboard/security/index.php" class="btn btn-outline-light">رجوع</a>
  </div>

  <div class="feature-card mb-4">
    <form method="post" class="row g-3">
      <div class="col-md-3"><label class="form-label">الكود</label><input class="form-control" name="code" placeholder="G7" required></div>
      <div class="col-md-5"><label class="form-label">الاسم</label><input class="form-control" name="name_ar" required></div>
      <div class="col-md-3"><label class="form-label">الموقع</label><input class="form-control" name="location_label"></div>
      <div class="col-md-1 d-grid"><label class="form-label">&nbsp;</label><button class="btn btn-gradient" type="submit">إضافة</button></div>
    </form>
  </div>

  <div class="table-responsive feature-card">
    <table class="table table-dark table-striped align-middle mb-0">
      <thead>
        <tr>
          <th>الكود</th>
          <th>الاسم</th>
          <th>الموقع</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?php echo htmlspecialchars($r['code']); ?></td>
            <td><?php echo htmlspecialchars($r['name_ar']); ?></td>
            <td><?php echo htmlspecialchars($r['location_label'] ?? ''); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
