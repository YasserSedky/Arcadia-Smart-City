<?php
$page_title = 'مستأجرو المول';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'mall_admin'])) {
  redirect('/dashboard/index.php');
}
$pdo = DB::conn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? 'add';
  $tenant_id = (int)($_POST['tenant_id'] ?? 0);
  $unit_id = (int)($_POST['unit_id'] ?? 0);
  $name = trim($_POST['name_ar'] ?? '');
  $category_id = (int)($_POST['category_id'] ?? 0);
  $phone = trim($_POST['phone'] ?? '');
  $start = $_POST['start_date'] ?? null;
  $end = $_POST['end_date'] ?? null;

  try {
    if ($action === 'add' && $unit_id > 0 && $name !== '') {
      $stmt = $pdo->prepare('INSERT INTO mall_tenants(unit_id, name_ar, category_id, phone, start_date, end_date) VALUES(?,?,?,?,?,?)');
      $stmt->execute([$unit_id, $name, $category_id ?: null, $phone ?: null, $start ?: null, $end ?: null]);
      $_SESSION['success'] = 'تم إضافة المستأجر بنجاح';
    } elseif ($action === 'edit' && $tenant_id > 0) {
      $stmt = $pdo->prepare('UPDATE mall_tenants SET name_ar=?, category_id=?, phone=?, start_date=?, end_date=? WHERE id=?');
      $stmt->execute([$name, $category_id ?: null, $phone ?: null, $start ?: null, $end ?: null, $tenant_id]);
      $_SESSION['success'] = 'تم تحديث بيانات المستأجر بنجاح';
    } elseif ($action === 'delete' && $tenant_id > 0) {
      $stmt = $pdo->prepare('DELETE FROM mall_tenants WHERE id = ?');
      $stmt->execute([$tenant_id]);
      $_SESSION['success'] = 'تم إنهاء عقد الإيجار بنجاح';
    }
  } catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
  }

  redirect(APP_BASE . '/dashboard/mall/tenants.php');
}

$units = $pdo->query('SELECT id, code FROM mall_units WHERE id NOT IN (SELECT unit_id FROM mall_tenants) ORDER BY code')->fetchAll();
$cats = $pdo->query('SELECT id, name_ar FROM mall_categories ORDER BY name_ar')->fetchAll();
$rows = $pdo->query('SELECT t.*, u.code AS unit_code, c.name_ar AS category_name FROM mall_tenants t JOIN mall_units u ON u.id=t.unit_id LEFT JOIN mall_categories c ON c.id=t.category_id ORDER BY u.code')->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">المستأجرون</h3>
    <a href="<?php echo APP_BASE; ?>/dashboard/mall/index.php" class="btn btn-outline-light">رجوع</a>
  </div>

  <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?php echo $_SESSION['success'];
                                      unset($_SESSION['success']); ?></div>
  <?php endif; ?>

  <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?php echo $_SESSION['error'];
                                    unset($_SESSION['error']); ?></div>
  <?php endif; ?>

  <div class="feature-card mb-4">
    <h5 class="mb-3">إضافة مستأجر جديد</h5>
    <form method="post" class="row g-3">
      <input type="hidden" name="action" value="add">
      <div class="col-md-3">
        <label class="form-label">الوحدة</label>
        <select name="unit_id" class="form-select" required>
          <option value="">اختر</option>
          <?php foreach ($units as $u): ?>
            <option value="<?php echo (int)$u['id']; ?>"><?php echo htmlspecialchars($u['code']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">اسم المستأجر</label>
        <input class="form-control" name="name_ar" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">الفئة</label>
        <select name="category_id" class="form-select">
          <option value="">—</option>
          <?php foreach ($cats as $c): ?><option value="<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars($c['name_ar']); ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2"><label class="form-label">الهاتف</label><input class="form-control" name="phone"></div>
      <div class="col-md-3"><label class="form-label">بداية العقد</label><input type="date" class="form-control" name="start_date"></div>
      <div class="col-md-3"><label class="form-label">نهاية العقد</label><input type="date" class="form-control" name="end_date"></div>
      <div class="col-12 d-grid d-md-flex gap-2"><button class="btn btn-gradient" type="submit">إضافة</button></div>
    </form>
  </div>

  <div class="table-responsive feature-card">
    <table class="table table-dark table-striped align-middle mb-0">
      <thead>
        <tr>
          <th>الوحدة</th>
          <th>المستأجر</th>
          <th>الفئة</th>
          <th>الهاتف</th>
          <th>الفترة</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?php echo htmlspecialchars($r['unit_code']); ?></td>
            <td><?php echo htmlspecialchars($r['name_ar']); ?></td>
            <td><?php echo htmlspecialchars($r['category_name'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($r['phone'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars(($r['start_date'] ?? '') . ' - ' . ($r['end_date'] ?? '')); ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?><tr>
            <td colspan="5" class="text-center text-muted">لا توجد بيانات</td>
          </tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>