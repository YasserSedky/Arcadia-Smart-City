<?php
$page_title = 'عيادات المستشفى';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'hospital_admin'])) {
  redirect('/dashboard/index.php');
}
$pdo = DB::conn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $department_id = (int)($_POST['department_id'] ?? 0);
  $name = trim($_POST['name_ar'] ?? '');
  $room = trim($_POST['room_label'] ?? '');
  if ($department_id > 0 && $name !== '') {
    $stmt = $pdo->prepare('INSERT INTO hospital_clinics(department_id, name_ar, room_label) VALUES(?,?,?)');
    try {
      $stmt->execute([$department_id, $name, $room ?: null]);
    } catch (PDOException $e) {
    }
  }
  redirect('/dashboard/hospital/clinics.php');
}

$deps = $pdo->query('SELECT id, name_ar FROM hospital_departments ORDER BY name_ar')->fetchAll();
$rows = $pdo->query('SELECT c.*, d.name_ar AS dept_name FROM hospital_clinics c JOIN hospital_departments d ON d.id=c.department_id ORDER BY d.name_ar, c.name_ar')->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">العيادات</h3>
    <a href="<?php echo APP_BASE; ?>/dashboard/hospital/index.php" class="btn btn-outline-light">رجوع</a>
  </div>

  <div class="feature-card mb-4">
    <form method="post" class="row g-3">
      <div class="col-md-4">
        <label class="form-label">القسم</label>
        <select name="department_id" class="form-select" required>
          <option value="">اختر القسم</option>
          <?php foreach ($deps as $d): ?>
            <option value="<?php echo (int)$d['id']; ?>"><?php echo htmlspecialchars($d['name_ar']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-5">
        <label class="form-label">اسم العيادة</label>
        <input type="text" name="name_ar" class="form-control" required>
      </div>
      <div class="col-md-2">
        <label class="form-label">الغرفة</label>
        <input type="text" name="room_label" class="form-control" placeholder="مثال: C12">
      </div>
      <div class="col-md-1 d-grid">
        <label class="form-label">&nbsp;</label>
        <button class="btn btn-gradient" type="submit">إضافة</button>
      </div>
    </form>
  </div>

  <div class="table-responsive feature-card">
    <table class="table table-dark table-striped align-middle mb-0">
      <thead>
        <tr>
          <th>القسم</th>
          <th>العيادة</th>
          <th>الغرفة</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?php echo htmlspecialchars($r['dept_name']); ?></td>
            <td><?php echo htmlspecialchars($r['name_ar']); ?></td>
            <td><?php echo htmlspecialchars($r['room_label'] ?? ''); ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?><tr>
            <td colspan="3" class="text-center text-muted">لا توجد عيادات</td>
          </tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
