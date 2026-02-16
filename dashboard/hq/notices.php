<?php
$page_title = 'إعلانات HQ';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'hq_admin'])) {
  redirect('/dashboard/index.php');
}
$pdo = DB::conn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['title'] ?? '');
  $body = trim($_POST['body'] ?? '');
  if ($title !== '' && $body !== '') {
    $stmt = $pdo->prepare('INSERT INTO hq_notices(title, body, created_by_user_id) VALUES(?,?,?)');
    try {
      $stmt->execute([$title, $body, $_SESSION['user']['id']]);
    } catch (PDOException $e) {
    }
  }
  redirect('/dashboard/hq/notices.php');
}

$rows = $pdo->query('SELECT n.*, u.full_name FROM hq_notices n JOIN users u ON u.id=n.created_by_user_id ORDER BY n.created_at DESC LIMIT 200')->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">الإعلانات</h3>
    <a href="<?php echo APP_BASE; ?>/dashboard/hq/index.php" class="btn btn-outline-light">رجوع</a>
  </div>

  <div class="feature-card mb-4">
    <form method="post" class="row g-3">
      <div class="col-md-4"><label class="form-label">العنوان</label><input class="form-control" name="title" required></div>
      <div class="col-md-12"><label class="form-label">المحتوى</label><textarea name="body" class="form-control" rows="4" required></textarea></div>
      <div class="col-12 d-grid d-md-flex gap-2"><button class="btn btn-gradient" type="submit">نشر</button></div>
    </form>
  </div>

  <div class="feature-card">
    <?php foreach ($rows as $r): ?>
      <div class="mb-4">
        <h5 class="mb-1"><?php echo htmlspecialchars($r['title']); ?></h5>
        <small class="text-muted"><?php echo htmlspecialchars($r['created_at'] . ' - ' . $r['full_name']); ?></small>
        <p class="mt-2 mb-0"><?php echo nl2br(htmlspecialchars($r['body'])); ?></p>
      </div>
      <hr>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?>
      <div class="text-muted">لا توجد إعلانات بعد</div>
    <?php endif; ?>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
