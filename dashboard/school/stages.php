<?php
$page_title = 'المراحل الدراسية';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'school_admin'])) {
  redirect('/dashboard/index.php');
}
$pdo = DB::conn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name_ar'] ?? '');
  $code = trim($_POST['code'] ?? '');
  if ($name !== '' && $code !== '') {
    $stmt = $pdo->prepare('INSERT INTO school_stages(name_ar, code) VALUES(?,?)');
    try {
      $stmt->execute([$name, $code]);
    } catch (PDOException $e) {
    }
  }
  redirect('/dashboard/school/stages.php');
}

$rows = $pdo->query('SELECT * FROM school_stages ORDER BY id')->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">المراحل الدراسية</h3>
    <a href="<?php echo APP_BASE; ?>/dashboard/school/index.php" class="btn btn-outline-light">رجوع</a>
  </div>

  <div class="feature-card mb-4">
    <form method="post" class="row g-3">
      <div class="col-md-6"><label class="form-label">الاسم</label><input class="form-control" name="name_ar" required></div>
      <div class="col-md-4"><label class="form-label">الكود</label><input class="form-control" name="code" required></div>
      <div class="col-md-2 d-grid"><label class="form-label">&nbsp;</label><button class="btn btn-gradient" type="submit">إضافة</button></div>
    </form>
  </div>

  <div class="table-responsive feature-card">
    <table class="table table-dark table-striped align-middle mb-0">
      <thead>
        <tr>
          <th>الاسم</th>
          <th>الكود</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?php echo htmlspecialchars($r['name_ar']); ?></td>
            <td><?php echo htmlspecialchars($r['code']); ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?><tr>
            <td colspan="2" class="text-center text-muted">لا توجد مراحل</td>
          </tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
