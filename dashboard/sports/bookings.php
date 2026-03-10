<?php
$page_title = 'حجوزات النادي';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'sports_admin'])) {
  redirect('/dashboard/index.php');
}
$pdo = DB::conn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $facility_id = (int)($_POST['facility_id'] ?? 0);
  $title = trim($_POST['title'] ?? '');
  $starts_at = $_POST['starts_at'] ?? '';
  $ends_at = $_POST['ends_at'] ?? null;
  $notes = trim($_POST['notes'] ?? '');
  if ($facility_id > 0 && $title !== '' && $starts_at !== '') {
    $stmt = $pdo->prepare('INSERT INTO sports_bookings(facility_id, title, starts_at, ends_at, notes) VALUES(?,?,?,?,?)');
    try {
      $stmt->execute([$facility_id, $title, $starts_at, $ends_at ?: null, $notes ?: null]);
    } catch (PDOException $e) {
    }
  }
  redirect('/dashboard/sports/bookings.php');
}

$facilities = $pdo->query('SELECT id, name_ar FROM sports_facilities ORDER BY name_ar')->fetchAll();
$rows = $pdo->query('SELECT b.*, f.name_ar AS facility_name FROM sports_bookings b JOIN sports_facilities f ON f.id=b.facility_id ORDER BY b.starts_at DESC LIMIT 200')->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">حجوزات المرافق</h3>
    <a href="<?php echo APP_BASE; ?>/dashboard/sports/index.php" class="btn btn-outline-light">رجوع</a>
  </div>

  <div class="feature-card mb-4">
    <form method="post" class="row g-3">
      <div class="col-md-4"><label class="form-label">المرفق</label>
        <select name="facility_id" class="form-select" required>
          <option value="">اختر</option>
          <?php foreach ($facilities as $f): ?><option value="<?php echo (int)$f['id']; ?>"><?php echo htmlspecialchars($f['name_ar']); ?></option><?php endforeach; ?>
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
          <th>المرفق</th>
          <th>العنوان</th>
          <th>البداية</th>
          <th>النهاية</th>
          <th>الحالة</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?php echo htmlspecialchars($r['facility_name']); ?></td>
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
