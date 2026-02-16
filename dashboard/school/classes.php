<?php
$page_title = 'فصول المدرسة';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'school_admin'])) {
  redirect('/dashboard/index.php');
}
$pdo = DB::conn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $stage_id = (int)($_POST['stage_id'] ?? 0);
  $name = trim($_POST['name_ar'] ?? '');
  $room = trim($_POST['room_label'] ?? '');
  if ($stage_id > 0 && $name !== '') {
    $stmt = $pdo->prepare('INSERT INTO school_classes(stage_id, name_ar, room_label) VALUES(?,?,?)');
    try {
      $stmt->execute([$stage_id, $name, $room ?: null]);
    } catch (PDOException $e) {
    }
  }
  redirect('/dashboard/school/classes.php');
}

$stages = $pdo->query('SELECT id, name_ar FROM school_stages ORDER BY id')->fetchAll();
$rows = $pdo->query('SELECT c.*, s.name_ar AS stage_name FROM school_classes c JOIN school_stages s ON s.id=c.stage_id ORDER BY s.id, c.name_ar')->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">الفصول</h3>
    <a href="<?php echo APP_BASE; ?>/dashboard/school/index.php" class="btn btn-outline-light">رجوع</a>
  </div>

  <div class="feature-card mb-4">
    <form method="post" class="row g-3">
      <div class="col-md-4"><label class="form-label">المرحلة</label>
        <select name="stage_id" class="form-select" required>
          <option value="">اختر</option>
          <?php foreach ($stages as $s): ?><option value="<?php echo (int)$s['id']; ?>"><?php echo htmlspecialchars($s['name_ar']); ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-5"><label class="form-label">اسم الفصل</label><input class="form-control" name="name_ar" required></div>
      <div class="col-md-2"><label class="form-label">الغرفة</label><input class="form-control" name="room_label"></div>
      <div class="col-md-1 d-grid"><label class="form-label">&nbsp;</label><button class="btn btn-gradient" type="submit">إضافة</button></div>
    </form>
  </div>

  <div class="table-responsive feature-card">
    <table class="table table-dark table-striped align-middle mb-0">
      <thead>
        <tr>
          <th>المرحلة</th>
          <th>الفصل</th>
          <th>الغرفة</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?php echo htmlspecialchars($r['stage_name']); ?></td>
            <td><?php echo htmlspecialchars($r['name_ar']); ?></td>
            <td><?php echo htmlspecialchars($r['room_label'] ?? ''); ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?><tr>
            <td colspan="3" class="text-center text-muted">لا توجد فصول</td>
          </tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
