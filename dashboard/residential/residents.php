<?php
$page_title = 'إدارة السكان';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'residential_admin'])) {
  redirect('/dashboard/index.php');
}

$pdo = DB::conn();

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    if ($user_id > 0) {
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ? AND role_id = (SELECT id FROM roles WHERE code = "resident")');
        try {
            $stmt->execute([$user_id]);
            $_SESSION['success'] = 'تم حذف المقيم بنجاح';
        } catch (PDOException $e) {
            $_SESSION['error'] = 'حدث خطأ أثناء الحذف';
        }
    }
    redirect('/dashboard/residential/residents.php');
}

// Get filter parameters
$building_filter = $_GET['building'] ?? '';
$unit_filter = $_GET['unit'] ?? '';

// Get all residents with their unit info
$query = "
    SELECT u.id, u.full_name, u.phone, u.email, u.is_active, u.created_at,
           un.unit_code, un.unit_number,
           b.label as building_label, b.type as building_type
    FROM users u
    JOIN roles r ON r.id = u.role_id
    LEFT JOIN units un ON un.id = u.unit_id
    LEFT JOIN buildings b ON b.id = un.building_id
    WHERE r.code = 'resident'
";

$params = [];
if ($building_filter !== '') {
    $query .= " AND b.id = ?";
    $params[] = (int)$building_filter;
}

if ($unit_filter !== '') {
    $query .= " AND un.unit_code LIKE ?";
    $params[] = '%' . $unit_filter . '%';
}

$query .= " ORDER BY b.label, un.unit_number, u.full_name";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$residents = $stmt->fetchAll();

// Get all buildings for filter
$buildings = $pdo->query("
    SELECT b.*, COUNT(DISTINCT u.id) as resident_count
    FROM buildings b
    LEFT JOIN units un ON un.building_id = b.id
    LEFT JOIN users u ON u.unit_id = un.id AND u.is_active = 1
    WHERE b.type IN ('apartment_block', 'villa')
    GROUP BY b.id
    ORDER BY b.type DESC, b.label
")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">إدارة السكان</h2>
    <a href="<?php echo APP_BASE; ?>/dashboard/residential/index.php" class="btn btn-outline-light">رجوع</a>
  </div>

  <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Filters -->
  <div class="feature-card mb-4">
    <form method="get" class="row g-3">
      <div class="col-md-4">
        <label class="form-label">تصفية حسب المبنى</label>
        <select name="building" class="form-select">
          <option value="">جميع المباني</option>
          <?php foreach ($buildings as $building): ?>
            <option value="<?= (int)$building['id'] ?>" <?= $building_filter == $building['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($building['label']) ?> 
              (<?= $building['type'] === 'apartment_block' ? 'عمارة' : 'فيلا' ?>)
              - <?= (int)$building['resident_count'] ?> ساكن
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">تصفية حسب الوحدة</label>
        <input type="text" name="unit" class="form-control" placeholder="رقم الوحدة" value="<?= htmlspecialchars($unit_filter) ?>">
      </div>
      <div class="col-md-4 d-flex align-items-end">
        <button type="submit" class="btn btn-gradient me-2">تصفية</button>
        <a href="<?php echo APP_BASE; ?>/dashboard/residential/residents.php" class="btn btn-outline-secondary">إعادة تعيين</a>
      </div>
    </form>
  </div>

  <!-- Residents Table -->
  <div class="feature-card">
    <h5 class="mb-3">قائمة السكان (<?= count($residents) ?>)</h5>
    <?php if (empty($residents)): ?>
      <div class="alert alert-info">لا يوجد سكان مسجلين</div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-dark table-striped align-middle">
          <thead>
            <tr>
              <th>الاسم</th>
              <th>الهاتف</th>
              <th>البريد الإلكتروني</th>
              <th>الوحدة السكنية</th>
              <th>المبنى</th>
              <th>الحالة</th>
              <th>تاريخ التسجيل</th>
              <th>إجراءات</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($residents as $resident): ?>
              <tr>
                <td><?= htmlspecialchars($resident['full_name']) ?></td>
                <td><?= htmlspecialchars($resident['phone']) ?></td>
                <td><?= htmlspecialchars($resident['email']) ?></td>
                <td>
                  <?php if ($resident['unit_code']): ?>
                    <span class="badge bg-info"><?= htmlspecialchars($resident['unit_code']) ?></span>
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($resident['building_label']): ?>
                    <?= $resident['building_type'] === 'apartment_block' ? 'عمارة ' : 'فيلا ' ?>
                    <?= htmlspecialchars($resident['building_label']) ?>
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($resident['is_active']): ?>
                    <span class="badge bg-success">مفعل</span>
                  <?php else: ?>
                    <span class="badge bg-warning">معلق</span>
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars(date('Y-m-d', strtotime($resident['created_at']))) ?></td>
                <td>
                  <form method="post" style="display: inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذا المقيم؟');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" value="<?= (int)$resident['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger">
                      <i class="bi bi-trash"></i> حذف
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

