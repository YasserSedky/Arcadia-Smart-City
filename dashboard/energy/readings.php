<?php
$page_title = 'قراءات المنظومة';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'energy_admin'])) {
  redirect('/dashboard/index.php');
}
$pdo = DB::conn();

$array_id = (int)($_GET['array_id'] ?? 0);
$arr = $pdo->prepare('SELECT sa.*, b.label AS building_label FROM solar_arrays sa JOIN buildings b ON b.id=sa.building_id WHERE sa.id=?');
$arr->execute([$array_id]);
$array = $arr->fetch();
if (!$array) {
  redirect('/dashboard/energy/index.php');
}

$readings = $pdo->prepare('SELECT * FROM solar_readings WHERE array_id = ? ORDER BY ts DESC LIMIT 50');
$readings->execute([$array_id]);
$rows = $readings->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h3>قراءات: <?php echo htmlspecialchars($array['building_label'] . ' - ' . $array['name']); ?></h3>
      <div class="text-muted">سعة: <?php echo (float)$array['capacity_kw']; ?> kW</div>
    </div>
    <a href="<?php echo APP_BASE; ?>/dashboard/energy/index.php" class="btn btn-outline-light">رجوع</a>
  </div>

  <div class="feature-card mb-4">
    <form method="post" action="<?php echo APP_BASE; ?>/dashboard/energy/save_reading.php" class="row g-3">
      <input type="hidden" name="array_id" value="<?php echo (int)$array_id; ?>">
      <div class="col-md-3">
        <label class="form-label">التاريخ والوقت</label>
        <input type="datetime-local" name="ts" class="form-control" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">القدرة (kW)</label>
        <input type="number" step="0.001" name="power_kw" class="form-control">
      </div>
      <div class="col-md-3">
        <label class="form-label">الطاقة (kWh)</label>
        <input type="number" step="0.001" name="energy_kwh" class="form-control">
      </div>
      <div class="col-md-2">
        <label class="form-label">الحرارة (°C)</label>
        <input type="number" step="0.01" name="temperature_c" class="form-control">
      </div>
      <div class="col-md-1">
        <label class="form-label">الحالة</label>
        <select name="status" class="form-select">
          <option value="ok">سليم</option>
          <option value="warning">تحذير</option>
          <option value="fault">عطل</option>
        </select>
      </div>
      <div class="col-12 d-grid d-md-flex gap-2">
        <button class="btn btn-gradient" type="submit">إضافة قراءة</button>
      </div>
    </form>
  </div>

  <div class="table-responsive feature-card">
    <table class="table table-dark table-striped align-middle mb-0">
      <thead>
        <tr>
          <th>الوقت</th>
          <th>kW</th>
          <th>kWh</th>
          <th>°C</th>
          <th>الحالة</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?php echo htmlspecialchars($r['ts']); ?></td>
            <td><?php echo $r['power_kw'] !== null ? (float)$r['power_kw'] : '—'; ?></td>
            <td><?php echo $r['energy_kwh'] !== null ? (float)$r['energy_kwh'] : '—'; ?></td>
            <td><?php echo $r['temperature_c'] !== null ? (float)$r['temperature_c'] : '—'; ?></td>
            <td><?php echo htmlspecialchars($r['status']); ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?>
          <tr>
            <td colspan="5" class="text-center text-muted">لا توجد قراءات بعد</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
