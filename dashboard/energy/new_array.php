<?php
$page_title = 'إضافة منظومة شمسية';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'energy_admin'])) {
  redirect('/dashboard/index.php');
}
$pdo = DB::conn();

$buildings = $pdo->query("SELECT id, label FROM buildings ORDER BY label")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding" style="max-width:840px;">
  <div class="feature-card">
    <h3 class="mb-3">إضافة منظومة ألواح شمسية</h3>
    <form method="post" action="<?php echo APP_BASE; ?>/dashboard/energy/save_array.php" class="row g-3">
      <div class="col-md-6">
        <label class="form-label">المبنى</label>
        <select name="building_id" class="form-select" required>
          <option value="">اختر المبنى</option>
          <?php foreach ($buildings as $b): ?>
            <option value="<?php echo (int)$b['id']; ?>"><?php echo htmlspecialchars($b['label']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">اسم المنظومة</label>
        <input type="text" name="name" class="form-control" required placeholder="مثال: Roof-A">
      </div>
      <div class="col-md-4">
        <label class="form-label">السعة (kW)</label>
        <input type="number" step="0.01" name="capacity_kw" class="form-control" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">تاريخ التركيب</label>
        <input type="date" name="install_date" class="form-control">
      </div>
      <div class="col-12">
        <label class="form-label">ملاحظات</label>
        <input type="text" name="notes" class="form-control">
      </div>
      <div class="col-12 d-grid d-md-flex gap-2">
        <button class="btn btn-gradient" type="submit">حفظ</button>
        <a class="btn btn-outline-light" href="<?php echo APP_BASE; ?>/dashboard/energy/index.php">رجوع</a>
      </div>
    </form>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
