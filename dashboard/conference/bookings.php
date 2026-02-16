<?php
$page_title = 'حجوزات المؤتمرات';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'conference_admin'])) {
  redirect('/dashboard/index.php');
}
$pdo = DB::conn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $venue_id = (int)($_POST['venue_id'] ?? 0);
  $title = trim($_POST['title'] ?? '');
  $starts_at = $_POST['starts_at'] ?? '';
  $ends_at = $_POST['ends_at'] ?? null;
  $notes = trim($_POST['notes'] ?? '');
  if ($venue_id > 0 && $title !== '' && $starts_at !== '') {
    $stmt = $pdo->prepare('INSERT INTO conf_bookings(venue_id, title, starts_at, ends_at, notes) VALUES(?,?,?,?,?)');
    try {
      $stmt->execute([$venue_id, $title, $starts_at, $ends_at ?: null, $notes ?: null]);
    } catch (PDOException $e) {
    }
  }
  redirect('/dashboard/conference/bookings.php');
}

$venues = $pdo->query('SELECT id, name_ar FROM conf_venues ORDER BY name_ar')->fetchAll();
$rows = $pdo->query('SELECT b.*, v.name_ar AS venue_name FROM conf_bookings b JOIN conf_venues v ON v.id=b.venue_id ORDER BY b.starts_at DESC LIMIT 300')->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">حجوزات المؤتمرات</h3>
    <a href="<?php echo APP_BASE; ?>/dashboard/conference/index.php" class="btn btn-outline-light">رجوع</a>
  </div>

  <div class="feature-card mb-4">
    <form method="post" class="row g-3">
      <div class="col-md-4"><label class="form-label">القاعـة</label>
        <select name="venue_id" class="form-select" required>
          <option value="">اختر</option>
          <?php foreach ($venues as $v): ?><option value="<?php echo (int)$v['id']; ?>"><?php echo htmlspecialchars($v['name_ar']); ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4"><label class="form-label">العنوان</label><input class="form-control" name="title" required></div>
      <div class="col-md-2"><label class="form-label">بداية</label><input type="datetime-local" class="form-control" name="starts_at" required></div>
      <div class="col-md-2"><label class="form-label">نهاية</label><input type="datetime-local" class="form-control" name="ends_at"></div>
      <div class="col-12"><label class="form-label">ملاحظات</label><input class="form-control" name="notes"></div>
      <div class="col-12 d-grid d-md-flex gap-2"><button class="btn btn-gradient" type="submit">إضافة</button></div>
    </form>
  </div>

  <div class="table-responsive feature-card">
    <table class="table table-dark table-striped align-middle mb-0">
      <thead>
        <tr>
          <th>القاعـة</th>
          <th>العنوان</th>
          <th>البداية</th>
          <th>النهاية</th>
          <th>الحالة</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?php echo htmlspecialchars($r['venue_name']); ?></td>
            <td><?php echo htmlspecialchars($r['title']); ?></td>
            <td><?php echo htmlspecialchars($r['starts_at']); ?></td>
            <td><?php echo htmlspecialchars($r['ends_at'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($r['status']); ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?><tr>
            <td colspan="5" class="text-center text-muted">لا توجد حجوزات</td>
          </tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
