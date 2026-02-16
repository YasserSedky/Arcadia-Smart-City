<?php
$page_title = 'الطاقة الشمسية';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
$u = $_SESSION['user'];
if (!user_can(['super_admin', 'energy_admin'])) {
  redirect('/dashboard/index.php');
}
$pdo = DB::conn();

// Fetch arrays with building labels and latest reading
$arrays = $pdo->query("SELECT sa.id, sa.name, sa.capacity_kw, b.label AS building_label,
  (SELECT r.power_kw FROM solar_readings r WHERE r.array_id = sa.id ORDER BY r.ts DESC LIMIT 1) AS latest_power_kw,
  (SELECT r.ts FROM solar_readings r WHERE r.array_id = sa.id ORDER BY r.ts DESC LIMIT 1) AS latest_ts
  FROM solar_arrays sa JOIN buildings b ON b.id = sa.building_id ORDER BY b.label, sa.name")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2>إدارة الطاقة الشمسية</h2>
      <p class="text-muted">كل مبنى مزود بمنظومة ألواح خاصة. يمكنك إضافة منظومات جديدة وتسجيل القراءات.</p>
    </div>
    <div>
      <a href="<?php echo APP_BASE; ?>/dashboard/energy/new_array.php" class="btn btn-gradient"><i class="bi bi-plus-lg"></i> إضافة منظومة</a>
    </div>
  </div>

  <div class="row g-4">
    <?php foreach ($arrays as $a): ?>
      <div class="col-md-6" data-aos="fade-up">
        <div class="feature-card text-start">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h5 class="mb-1"><?php echo htmlspecialchars($a['building_label'] . ' - ' . $a['name']); ?></h5>
              <div class="text-muted">سعة: <?php echo (float)$a['capacity_kw']; ?> kW</div>
            </div>
            <a href="<?php echo APP_BASE; ?>/dashboard/energy/readings.php?array_id=<?php echo (int)$a['id']; ?>" class="btn btn-outline-light">القراءات</a>
          </div>
          <hr>
          <div class="d-flex gap-3 align-items-center">
            <i class="bi bi-sun" style="font-size:28px;color:var(--sand-500);"></i>
            <div>
              <div>الإنتاج الحالي: <strong><?php echo $a['latest_power_kw'] !== null ? (float)$a['latest_power_kw'] . ' kW' : '—'; ?></strong></div>
              <small class="text-muted">آخر تحديث: <?php echo $a['latest_ts'] ? htmlspecialchars($a['latest_ts']) : '—'; ?></small>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (empty($arrays)): ?>
      <div class="col-12">
        <div class="alert alert-secondary">لا توجد منظومات بعد، قم بإضافة أول منظومة.</div>
      </div>
    <?php endif; ?>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
