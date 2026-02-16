<?php
$page_title = 'إدارة الصيدلية';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'hospital_admin'])) {
  redirect('/dashboard/index.php');
}
$pdo = DB::conn();

// Add item
if (isset($_POST['__action']) && $_POST['__action'] === 'add_item') {
  $name = trim($_POST['name_ar'] ?? '');
  $sku = trim($_POST['sku'] ?? '');
  $unit = trim($_POST['unit'] ?? 'pcs');
  $qty = (int)($_POST['quantity'] ?? 0);
  $minq = (int)($_POST['min_quantity'] ?? 0);
  $exp = $_POST['expiry_date'] ?? null;
  if ($name !== '') {
    $stmt = $pdo->prepare('INSERT INTO pharmacy_items(name_ar, sku, unit, quantity, min_quantity, expiry_date) VALUES(?,?,?,?,?,?)');
    try {
      $stmt->execute([$name, $sku ?: null, $unit, $qty, $minq, $exp ?: null]);
    } catch (PDOException $e) {
    }
  }
  redirect('/dashboard/hospital/pharmacy.php');
}

// Transaction (dispense or receive)
if (isset($_POST['__action']) && $_POST['__action'] === 'txn') {
  $item_id = (int)($_POST['item_id'] ?? 0);
  $type = $_POST['type'] === 'out' ? 'out' : 'in';
  $amount = max(1, (int)($_POST['amount'] ?? 1));
  $note = trim($_POST['note'] ?? '');
  if ($item_id > 0) {
    $pdo->beginTransaction();
    try {
      $pdo->prepare('INSERT INTO pharmacy_transactions(item_id, ts, type, amount, note) VALUES(?,NOW(),?,?,?)')
        ->execute([$item_id, $type, $amount, $note ?: null]);
      $delta = ($type === 'in') ? $amount : -$amount;
      $pdo->prepare('UPDATE pharmacy_items SET quantity = GREATEST(0, quantity + ?) WHERE id=?')->execute([$delta, $item_id]);
      $pdo->commit();
    } catch (PDOException $e) {
      $pdo->rollBack();
    }
  }
  redirect('/dashboard/hospital/pharmacy.php');
}

$items = $pdo->query('SELECT * FROM pharmacy_items ORDER BY name_ar')->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">الصيدلية</h3>
    <a href="<?php echo APP_BASE; ?>/dashboard/hospital/index.php" class="btn btn-outline-light">رجوع</a>
  </div>

  <div class="feature-card mb-4">
    <h5 class="mb-3">إضافة صنف</h5>
    <form method="post" class="row g-3">
      <input type="hidden" name="__action" value="add_item">
      <div class="col-md-4"><label class="form-label">الاسم</label><input class="form-control" name="name_ar" required></div>
      <div class="col-md-2"><label class="form-label">SKU</label><input class="form-control" name="sku"></div>
      <div class="col-md-2"><label class="form-label">الوحدة</label><input class="form-control" name="unit" value="pcs"></div>
      <div class="col-md-2"><label class="form-label">الكمية</label><input type="number" class="form-control" name="quantity" value="0"></div>
      <div class="col-md-2"><label class="form-label">حد أدنى</label><input type="number" class="form-control" name="min_quantity" value="0"></div>
      <div class="col-md-3"><label class="form-label">الصلاحية</label><input type="date" class="form-control" name="expiry_date"></div>
      <div class="col-12 d-grid d-md-flex gap-2"><button class="btn btn-gradient" type="submit">إضافة</button></div>
    </form>
  </div>

  <div class="table-responsive feature-card mb-4">
    <table class="table table-dark table-striped align-middle mb-0">
      <thead>
        <tr>
          <th>الصنف</th>
          <th>SKU</th>
          <th>الوحدة</th>
          <th>الكمية</th>
          <th>حد أدنى</th>
          <th>الصلاحية</th>
          <th>صرف/توريد</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $it): ?>
          <tr>
            <td><?php echo htmlspecialchars($it['name_ar']); ?></td>
            <td><?php echo htmlspecialchars($it['sku'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($it['unit']); ?></td>
            <td><?php echo (int)$it['quantity']; ?></td>
            <td><?php echo (int)$it['min_quantity']; ?></td>
            <td><?php echo htmlspecialchars($it['expiry_date'] ?? ''); ?></td>
            <td>
              <form method="post" class="d-flex gap-2 align-items-center">
                <input type="hidden" name="__action" value="txn">
                <input type="hidden" name="item_id" value="<?php echo (int)$it['id']; ?>">
                <select name="type" class="form-select form-select-sm" style="width:110px">
                  <option value="out">صرف</option>
                  <option value="in">توريد</option>
                </select>
                <input type="number" name="amount" class="form-control form-control-sm" value="1" style="width:90px">
                <input type="text" name="note" class="form-control form-control-sm" placeholder="ملاحظة">
                <button class="btn btn-sm btn-gradient" type="submit">تنفيذ</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($items)): ?><tr>
            <td colspan="7" class="text-center text-muted">لا توجد أصناف</td>
          </tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
