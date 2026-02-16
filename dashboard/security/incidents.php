<?php
$page_title = 'البلاغات والحوادث';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'security_admin'])) {
  redirect('/dashboard/index.php');
}
$pdo = DB::conn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $gate_id = (int)($_POST['gate_id'] ?? 0);
  $title = trim($_POST['title'] ?? '');
  $details = trim($_POST['details'] ?? '');
  $level = $_POST['level'] ?? 'info';
  if ($title !== '') {
    $stmt = $pdo->prepare('INSERT INTO security_incidents(gate_id, reported_by_user_id, title, details, level, occurred_at) VALUES(?,?,?,?,?,NOW())');
    try {
      $stmt->execute([$gate_id ?: null, $_SESSION['user']['id'], $title, $details ?: null, $level]);
    } catch (PDOException $e) {
    }
  }
  redirect('/dashboard/security/incidents.php');
}

$gates = $pdo->query('SELECT id, code FROM gates ORDER BY code')->fetchAll();
$rows = $pdo->query('SELECT i.*, g.code AS gate_code FROM security_incidents i LEFT JOIN gates g ON g.id=i.gate_id ORDER BY i.occurred_at DESC LIMIT 300')->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">البلاغات والحوادث</h3>
    <a href="<?php echo APP_BASE; ?>/dashboard/security/index.php" class="btn btn-outline-light">رجوع</a>
  </div>

  <div class="feature-card mb-4">
    <form method="post" class="row g-3">
      <div class="col-md-2"><label class="form-label">البوابة</label>
        <select name="gate_id" class="form-select">
          <option value="">—</option>
          <?php foreach ($gates as $g): ?><option value="<?php echo (int)$g['id']; ?>"><?php echo htmlspecialchars($g['code']); ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6"><label class="form-label">العنوان</label><input class="form-control" name="title" required></div>
      <div class="col-md-2"><label class="form-label">المستوى</label>
        <select name="level" class="form-select">
          <option value="info">معلومة</option>
          <option value="warning">تحذير</option>
          <option value="critical">خطير</option>
        </select>
      </div>
      <div class="col-md-12"><label class="form-label">التفاصيل</label><textarea name="details" class="form-control" rows="2"></textarea></div>
      <div class="col-12 d-grid d-md-flex gap-2"><button class="btn btn-gradient" type="submit">تسجيل</button></div>
    </form>
  </div>

  <div class="table-responsive feature-card">
    <table class="table table-dark table-striped align-middle mb-0">
      <thead>
        <tr>
          <th>الوقت</th>
          <th>البوابة</th>
          <th>العنوان</th>
          <th>المستوى</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?php echo htmlspecialchars($r['occurred_at']); ?></td>
            <td><?php echo htmlspecialchars($r['gate_code'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($r['title']); ?></td>
            <td><?php echo htmlspecialchars($r['level']); ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?><tr>
            <td colspan="4" class="text-center text-muted">لا توجد بلاغات</td>
          </tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
