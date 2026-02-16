<?php
$page_title = 'وحدات المول';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'mall_admin'])) {
  redirect('/dashboard/index.php');
}
$pdo = DB::conn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? 'add';
  $code = trim($_POST['code'] ?? '');
  $level = trim($_POST['level'] ?? '');
  $area = $_POST['area_sqm'] !== '' ? (float)$_POST['area_sqm'] : null;
  $type = $_POST['type'] ?? 'shop';
  $unit_id = $_POST['unit_id'] ?? null;

  try {
    if ($action === 'add' && $code !== '') {
      $stmt = $pdo->prepare('INSERT INTO mall_units(code, level, area_sqm, type) VALUES(?,?,?,?)');
      $stmt->execute([$code, $level ?: null, $area, $type]);
      $_SESSION['success'] = 'تم إضافة الوحدة بنجاح';
    } elseif ($action === 'edit' && $unit_id) {
      $stmt = $pdo->prepare('UPDATE mall_units SET code=?, level=?, area_sqm=?, type=? WHERE id=?');
      $stmt->execute([$code, $level ?: null, $area, $type, $unit_id]);
      $_SESSION['success'] = 'تم تحديث الوحدة بنجاح';
    } elseif ($action === 'delete' && $unit_id) {
      // Check if unit has tenants
      $stmt = $pdo->prepare('SELECT COUNT(*) FROM mall_tenants WHERE unit_id = ?');
      $stmt->execute([$unit_id]);
      if ($stmt->fetchColumn() > 0) {
        throw new Exception('لا يمكن حذف الوحدة لوجود مستأجر مرتبط بها');
      }
      $stmt = $pdo->prepare('DELETE FROM mall_units WHERE id = ?');
      $stmt->execute([$unit_id]);
      $_SESSION['success'] = 'تم حذف الوحدة بنجاح';
    }
  } catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
  }

  redirect(APP_BASE . '/dashboard/mall/units.php');
}

// Get units with tenant information
$rows = $pdo->query('
  SELECT u.*, t.name_ar as tenant_name 
  FROM mall_units u 
  LEFT JOIN mall_tenants t ON u.id = t.unit_id 
  ORDER BY u.code
')->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">الوحدات</h3>
    <a href="<?php echo APP_BASE; ?>/dashboard/mall/index.php" class="btn btn-outline-light">رجوع</a>
  </div>

  <div class="feature-card mb-4">
    <form method="post" class="row g-3">
      <div class="col-md-3"><label class="form-label">الكود</label><input class="form-control" name="code" required placeholder="S-101"></div>
      <div class="col-md-2"><label class="form-label">الدور</label><input class="form-control" name="level" placeholder="L1"></div>
      <div class="col-md-2"><label class="form-label">المساحة (م2)</label><input type="number" step="0.01" class="form-control" name="area_sqm"></div>
      <div class="col-md-3"><label class="form-label">النوع</label>
        <select name="type" class="form-select">
          <option value="shop">محل</option>
          <option value="barber_male">حلاق رجالي</option>
          <option value="barber_female">كوافير نسائي</option>
          <option value="restaurant">مطعم</option>
          <option value="cafe">كافيه</option>
          <option value="kiosk">كشك</option>
          <option value="cinema">سينما</option>
          <option value="gaming">صالة ألعاب</option>
          <option value="furniture">معرض أثاث</option>
          <option value="electronics">أجهزة كهربائية</option>
        </select>
      </div>
      <div class="col-md-2 d-grid"><label class="form-label">&nbsp;</label><button class="btn btn-gradient" type="submit">إضافة</button></div>
    </form>
  </div>

  <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?php echo $_SESSION['success'];
                                      unset($_SESSION['success']); ?></div>
  <?php endif; ?>

  <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?php echo $_SESSION['error'];
                                    unset($_SESSION['error']); ?></div>
  <?php endif; ?>

  <div class="table-responsive feature-card">
    <table class="table table-dark table-striped align-middle mb-0">
      <thead>
        <tr>
          <th>الكود</th>
          <th>الدور</th>
          <th>المساحة</th>
          <th>النوع</th>
          <th>الحالة</th>
          <th>الإجراءات</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?php echo htmlspecialchars($r['code']); ?></td>
            <td><?php echo htmlspecialchars($r['level'] ?? ''); ?></td>
            <td><?php echo $r['area_sqm'] !== null ? (float)$r['area_sqm'] : '—'; ?></td>
            <td>
              <?php
              $types = [
                'shop' => 'محل تجاري',
                'barber_male' => 'حلاق رجالي',
                'barber_female' => 'كوافير نسائي',
                'restaurant' => 'مطعم',
                'cafe' => 'كافيه',
                'kiosk' => 'كشك',
                'cinema' => 'سينما',
                'gaming' => 'صالة ألعاب',
                'furniture' => 'معرض أثاث',
                'electronics' => 'أجهزة كهربائية'
              ];
              echo $types[$r['type']] ?? $r['type'];
              ?>
            </td>
            <td>
              <?php if ($r['tenant_name']): ?>
                <span class="badge bg-danger">مؤجرة</span>
              <?php else: ?>
                <span class="badge bg-success">متاحة</span>
              <?php endif; ?>
            </td>
            <td>
              <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#editUnit<?php echo $r['id']; ?>">
                تعديل
              </button>
              <?php if (!$r['tenant_name']): ?>
                <form method="post" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذه الوحدة؟')">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="unit_id" value="<?php echo $r['id']; ?>">
                  <button type="submit" class="btn btn-sm btn-danger">حذف</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>

          <!-- Edit Modal -->
          <div class="modal fade" id="editUnit<?php echo $r['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
              <div class="modal-content bg-dark">
                <div class="modal-header">
                  <h5 class="modal-title">تعديل الوحدة</h5>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <form method="post">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="unit_id" value="<?php echo $r['id']; ?>">
                    <div class="mb-3">
                      <label class="form-label">الكود</label>
                      <input type="text" class="form-control" name="code" value="<?php echo htmlspecialchars($r['code']); ?>" required>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">الدور</label>
                      <input type="text" class="form-control" name="level" value="<?php echo htmlspecialchars($r['level'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">المساحة (م2)</label>
                      <input type="number" step="0.01" class="form-control" name="area_sqm" value="<?php echo $r['area_sqm']; ?>">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">النوع</label>
                      <select name="type" class="form-select">
                        <?php foreach ($types as $value => $label): ?>
                          <option value="<?php echo $value; ?>" <?php echo $r['type'] === $value ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="text-end">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                      <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?>
          <tr>
            <td colspan="6" class="text-center text-muted">لا توجد وحدات</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>