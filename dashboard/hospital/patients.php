<?php
$page_title = 'سجل المرضى';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'hospital_admin'])) {
  redirect('/dashboard/index.php');
}
$pdo = DB::conn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['full_name'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $nid = trim($_POST['national_id'] ?? '');
  $dob = $_POST['date_of_birth'] ?? null;
  $gender = $_POST['gender'] ?? null;
  if ($name !== '' && $phone !== '') {
    $stmt = $pdo->prepare('INSERT INTO hospital_patients(full_name, phone, national_id, date_of_birth, gender) VALUES(?,?,?,?,?)');
    try {
      $stmt->execute([$name, $phone, $nid ?: null, $dob ?: null, $gender ?: null]);
    } catch (PDOException $e) {
    }
  }
  redirect('/dashboard/hospital/patients.php');
}

$rows = $pdo->query('SELECT * FROM hospital_patients ORDER BY full_name')->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">المرضى</h3>
    <a href="<?php echo APP_BASE; ?>/dashboard/hospital/index.php" class="btn btn-outline-light">رجوع</a>
  </div>

  <div class="feature-card mb-4">
    <form method="post" class="row g-3">
      <div class="col-md-4">
        <label class="form-label">الاسم الكامل</label>
        <input type="text" name="full_name" class="form-control" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">الهاتف</label>
        <input type="tel" name="phone" class="form-control" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">الرقم القومي</label>
        <input type="text" name="national_id" class="form-control">
      </div>
      <div class="col-md-1">
        <label class="form-label">النوع</label>
        <select name="gender" class="form-select">
          <option value="">—</option>
          <option value="male">ذكر</option>
          <option value="female">أنثى</option>
        </select>
      </div>
      <div class="col-md-1">
        <label class="form-label">الميلاد</label>
        <input type="date" name="date_of_birth" class="form-control">
      </div>
      <div class="col-12 d-grid d-md-flex gap-2">
        <button class="btn btn-gradient" type="submit">إضافة</button>
      </div>
    </form>
  </div>

  <div class="table-responsive feature-card">
    <table class="table table-dark table-striped align-middle mb-0">
      <thead>
        <tr>
          <th>الاسم</th>
          <th>الهاتف</th>
          <th>الرقم القومي</th>
          <th>النوع</th>
          <th>الميلاد</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?php echo htmlspecialchars($r['full_name']); ?></td>
            <td><?php echo htmlspecialchars($r['phone']); ?></td>
            <td><?php echo htmlspecialchars($r['national_id'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($r['gender'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($r['date_of_birth'] ?? ''); ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?><tr>
            <td colspan="5" class="text-center text-muted">لا يوجد مرضى</td>
          </tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
