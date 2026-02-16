<?php
$page_title = 'الطلبات المعلقة';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'residential_admin'])) {
  redirect('/dashboard/index.php');
}

$pdo = DB::conn();

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $user_id = (int)($_POST['user_id'] ?? 0);
    if ($user_id > 0) {
        if ($_POST['action'] === 'approve') {
            $stmt = $pdo->prepare('UPDATE users SET is_active = 1 WHERE id = ? AND role_id = (SELECT id FROM roles WHERE code = "resident")');
            try {
                $stmt->execute([$user_id]);
                $_SESSION['success'] = 'تم تفعيل حساب المقيم بنجاح';
            } catch (PDOException $e) {
                $_SESSION['error'] = 'حدث خطأ أثناء التفعيل';
            }
        } elseif ($_POST['action'] === 'reject') {
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = ? AND role_id = (SELECT id FROM roles WHERE code = "resident")');
            try {
                $stmt->execute([$user_id]);
                $_SESSION['success'] = 'تم رفض طلب التسجيل';
            } catch (PDOException $e) {
                $_SESSION['error'] = 'حدث خطأ أثناء الرفض';
            }
        }
    }
    redirect('/dashboard/residential/pending.php');
}

// Get pending residents (is_active = 0)
$pending = $pdo->query("
    SELECT u.id, u.full_name, u.phone, u.email, u.created_at,
           un.unit_code, un.unit_number,
           b.label as building_label, b.type as building_type
    FROM users u
    JOIN roles r ON r.id = u.role_id
    LEFT JOIN units un ON un.id = u.unit_id
    LEFT JOIN buildings b ON b.id = un.building_id
    WHERE r.code = 'resident' AND u.is_active = 0
    ORDER BY u.created_at ASC
")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">الطلبات المعلقة</h2>
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

  <div class="feature-card">
    <h5 class="mb-3 text-white">طلبات التسجيل المعلقة (<?= count($pending) ?>)</h5>
    <?php if (empty($pending)): ?>
      <div class="alert alert-info">لا توجد طلبات معلقة</div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-dark table-striped align-middle">
          <thead>
            <tr>
              <th class="text-white">الاسم</th>
              <th class="text-white">الهاتف</th>
              <th class="text-white">البريد الإلكتروني</th>
              <th class="text-white">الوحدة السكنية</th>
              <th class="text-white">تاريخ الطلب</th>
              <th class="text-white">إجراءات</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pending as $resident): ?>
              <tr>
                <td class="text-white"><?= htmlspecialchars($resident['full_name']) ?></td>
                <td class="text-white"><?= htmlspecialchars($resident['phone']) ?></td>
                <td class="text-white"><?= htmlspecialchars($resident['email']) ?></td>
                <td class="text-white">
                  <?php if ($resident['unit_code']): ?>
                    <?= htmlspecialchars($resident['unit_code']) ?>
                    <?php if ($resident['building_label']): ?>
                      (<?= $resident['building_type'] === 'apartment_block' ? 'عمارة ' : 'فيلا ' ?><?= htmlspecialchars($resident['building_label']) ?>)
                    <?php endif; ?>
                  <?php else: ?>
                    —
                  <?php endif; ?>
                </td>
                <td class="text-white"><?= htmlspecialchars(date('Y-m-d H:i', strtotime($resident['created_at']))) ?></td>
                <td>
                  <form method="post" style="display: inline;">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="user_id" value="<?= (int)$resident['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-success">
                      <i class="bi bi-check-circle"></i> الموافقة
                    </button>
                  </form>
                  <form method="post" style="display: inline;" onsubmit="return confirm('هل أنت متأكد من رفض هذا الطلب؟');">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="user_id" value="<?= (int)$resident['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger">
                      <i class="bi bi-x-circle"></i> الرفض
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

