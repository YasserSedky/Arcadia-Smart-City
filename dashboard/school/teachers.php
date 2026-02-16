<?php
$page_title = 'المعلمون';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'school_admin'])) {
  redirect('/dashboard/index.php');
}
$pdo = DB::conn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $user_id = (int)($_POST['user_id'] ?? 0);
  $specialty = trim($_POST['specialty'] ?? '');
  if ($user_id > 0) {
    $stmt = $pdo->prepare('INSERT INTO school_teachers(user_id, specialty) VALUES(?,?)');
    try {
      $stmt->execute([$user_id, $specialty ?: null]);
    } catch (PDOException $e) {
    }
  }
  redirect('/dashboard/school/teachers.php');
}

$users = $pdo->query("SELECT u.id, u.full_name FROM users u JOIN roles r ON r.id=u.role_id WHERE r.code='admin_staff' OR r.code='school_admin' ORDER BY u.full_name")->fetchAll();
$rows = $pdo->query('SELECT t.*, u.full_name FROM school_teachers t JOIN users u ON u.id=t.user_id ORDER BY u.full_name')->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">المعلمون</h3>
    <a href="<?php echo APP_BASE; ?>/dashboard/school/index.php" class="btn btn-outline-light">رجوع</a>
  </div>

  <div class="feature-card mb-4">
    <form method="post" class="row g-3">
      <div class="col-md-6"><label class="form-label">المستخدم</label>
        <select name="user_id" class="form-select" required>
          <option value="">اختر</option>
          <?php foreach ($users as $u): ?><option value="<?php echo (int)$u['id']; ?>"><?php echo htmlspecialchars($u['full_name']); ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-5"><label class="form-label">التخصص</label><input class="form-control" name="specialty"></div>
      <div class="col-md-1 d-grid"><label class="form-label">&nbsp;</label><button class="btn btn-gradient" type="submit">إضافة</button></div>
    </form>
  </div>

  <div class="table-responsive feature-card">
    <table class="table table-dark table-striped align-middle mb-0">
      <thead>
        <tr>
          <th>المعلم</th>
          <th>التخصص</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?php echo htmlspecialchars($r['full_name']); ?></td>
            <td><?php echo htmlspecialchars($r['specialty'] ?? ''); ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?><tr>
            <td colspan="2" class="text-center text-muted">لا يوجد معلمون</td>
          </tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
