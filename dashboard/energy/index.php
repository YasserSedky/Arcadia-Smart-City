<?php
$page_title = 'إدارة الطاقة';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
$u = $_SESSION['user'];
if (!user_can(['super_admin', 'energy_admin'])) {
  redirect('/dashboard/index.php');
}
$pdo = DB::conn();

// Determine active tab
$tab = $_GET['tab'] ?? 'solar';

// Fetch data based on tab
if ($tab === 'solar') {
  // Fetch arrays with building labels and latest reading
  $arrays = $pdo->query("SELECT sa.id, sa.name, sa.capacity_kw, b.label AS building_label,
    (SELECT r.power_kw FROM solar_readings r WHERE r.array_id = sa.id ORDER BY r.ts DESC LIMIT 1) AS latest_power_kw,
    (SELECT r.ts FROM solar_readings r WHERE r.array_id = sa.id ORDER BY r.ts DESC LIMIT 1) AS latest_ts
    FROM solar_arrays sa JOIN buildings b ON b.id = sa.building_id ORDER BY b.label, sa.name")->fetchAll();
} elseif ($tab === 'stations') {
  // Fetch power stations
  $stations = $pdo->query("SELECT * FROM power_stations ORDER BY name")->fetchAll();
}

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2>إدارة الطاقة</h2>
      <p class="text-muted">إدارة الطاقة الشمسية ومحطات الكهرباء في المدينة.</p>
    </div>
    <div>
      <?php if ($tab === 'solar'): ?>
        <a href="<?php echo APP_BASE; ?>/dashboard/energy/new_array.php" class="btn btn-gradient"><i class="bi bi-plus-lg"></i> إضافة منظومة</a>
      <?php elseif ($tab === 'stations'): ?>
        <a href="<?php echo APP_BASE; ?>/dashboard/energy/new_station.php" class="btn btn-gradient"><i class="bi bi-plus-lg"></i> إضافة محطة</a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Tabs -->
  <ul class="nav nav-tabs mb-4">
    <li class="nav-item">
      <a class="nav-link <?php echo $tab === 'solar' ? 'active' : ''; ?>" href="?tab=solar">الطاقة الشمسية</a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?php echo $tab === 'stations' ? 'active' : ''; ?>" href="?tab=stations">محطات الكهرباء</a>
    </li>
  </ul>

  <?php if ($tab === 'solar'): ?>
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
  <?php elseif ($tab === 'stations'): ?>
    <div class="row g-4">
      <?php
      // Debug: Check if stations are loaded
      if (!isset($stations)) {
        $stations = $pdo->query("SELECT * FROM power_stations ORDER BY name")->fetchAll();
      }
      ?>
      <?php foreach ($stations as $s): ?>
        <div class="col-md-6" data-aos="fade-up">
          <div class="feature-card text-start">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h5 class="mb-1"><?php echo htmlspecialchars($s['name']); ?></h5>
                <div class="text-muted">الموقع: <?php echo htmlspecialchars($s['location'] ?: 'غير محدد'); ?></div>
              </div>
              <div>
                <a href="<?php echo APP_BASE; ?>/dashboard/energy/edit_station.php?id=<?php echo (int)$s['id']; ?>" class="btn btn-outline-light btn-sm">تعديل</a>
                <button class="btn btn-danger btn-sm ms-1" onclick="confirmDelete(<?php echo (int)$s['id']; ?>)">حذف</button>
              </div>
            </div>
            <hr>
            <div>
              <strong>الوحدات المغذاة:</strong>
              <?php
              $units = json_decode($s['responsible_units'], true);
              if ($units) {
                echo '<ul>';
                foreach ($units as $unit_id) {
                  // Fetch unit code or label
                  $stmt = $pdo->prepare("SELECT unit_code FROM units WHERE id = ?");
                  $stmt->execute([$unit_id]);
                  $unit = $stmt->fetch();
                  echo '<li>' . htmlspecialchars($unit['unit_code'] ?? 'وحدة ' . $unit_id) . '</li>';
                }
                echo '</ul>';
              } else {
                echo 'لا توجد وحدات محددة';
              }
              ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (empty($stations)): ?>
        <div class="col-12">
          <div class="alert alert-secondary">لا توجد محطات بعد، قم بإضافة أول محطة.</div>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</main>

<form id="deleteForm" method="post" action="<?php echo APP_BASE; ?>/dashboard/energy/delete_station.php" style="display: none;">
  <input type="hidden" name="id" id="deleteId">
</form>

<script>
  function confirmDelete(id) {
    if (confirm('هل أنت متأكد من حذف هذه المحطة؟ هذا الإجراء لا يمكن التراجع عنه.')) {
      document.getElementById('deleteId').value = id;
      document.getElementById('deleteForm').submit();
    }
  }
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>