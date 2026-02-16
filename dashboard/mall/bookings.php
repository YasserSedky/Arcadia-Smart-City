<?php
$page_title = 'حجوزات المول';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'mall_admin'])) {
  redirect('/dashboard/index.php');
}
$pdo = DB::conn();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !headers_sent()) {
  $action = $_POST['action'] ?? 'add';
  $booking_id = (int)($_POST['booking_id'] ?? 0);
  $venue_id = (int)($_POST['venue_id'] ?? 0);
  $title = trim($_POST['title'] ?? '');
  $starts_at = $_POST['starts_at'] ?? '';
  $ends_at = $_POST['ends_at'] ?? null;
  $notes = trim($_POST['notes'] ?? '');
  $status = $_POST['status'] ?? 'scheduled';

  try {
    if ($action === 'add' && $venue_id > 0 && $title !== '' && $starts_at !== '') {
      $stmt = $pdo->prepare('INSERT INTO mall_bookings(venue_id, title, starts_at, ends_at, notes, status) VALUES(?,?,?,?,?,?)');
      $stmt->execute([$venue_id, $title, $starts_at, $ends_at ?: null, $notes ?: null, $status]);
      $_SESSION['success'] = 'تم إضافة الحجز بنجاح';
    } elseif ($action === 'edit' && $booking_id > 0) {
      $stmt = $pdo->prepare('UPDATE mall_bookings SET venue_id=?, title=?, starts_at=?, ends_at=?, notes=?, status=? WHERE id=?');
      $stmt->execute([$venue_id, $title, $starts_at, $ends_at ?: null, $notes ?: null, $status, $booking_id]);
      $_SESSION['success'] = 'تم تحديث الحجز بنجاح';
    } elseif ($action === 'delete' && $booking_id > 0) {
      // Instead of deleting a booking, mark it as cancelled so the record
      // remains for auditing and reporting (per UX request).
      $stmt = $pdo->prepare('UPDATE mall_bookings SET status = ? WHERE id = ?');
      $stmt->execute(['cancelled', $booking_id]);
      $_SESSION['success'] = 'تم إلغاء الحجز بنجاح';
    }
  } catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
  }

  redirect(APP_BASE . '/dashboard/mall/bookings.php');
}

$venues = $pdo->query('SELECT id, name_ar FROM mall_venues ORDER BY name_ar')->fetchAll();
// Try the full query (joins users and expects attendees/user_id). If the schema
// hasn't been migrated yet this may fail (unknown column). Fall back to a
// safer query so the admin page still renders and we can apply migration later.
$schema_missing = false;
try {
  $rows = $pdo->query(
    'SELECT b.*, 
        v.name_ar AS venue_name, 
        v.type AS venue_type,
        u.full_name AS user_name,
        u.phone AS user_phone
     FROM mall_bookings b 
     JOIN mall_venues v ON v.id = b.venue_id 
     JOIN users u ON u.id = b.user_id
     ORDER BY b.starts_at DESC 
     LIMIT 200'
  )->fetchAll();
} catch (Exception $e) {
  // Most likely the mall_bookings table doesn't have user_id/attendees yet.
  // Fall back to a query that doesn't reference those columns and show
  // a warning so the admin can run the migration.
  $schema_missing = true;
  $rows = $pdo->query(
    'SELECT b.*, v.name_ar AS venue_name, v.type AS venue_type
     FROM mall_bookings b
     JOIN mall_venues v ON v.id = b.venue_id
     ORDER BY b.starts_at DESC
     LIMIT 200'
  )->fetchAll();
  // store the exception message in session so it's visible to admin if needed
  $_SESSION['error'] = 'ملف قاعدة البيانات يحتاج تحديثًا (user_id/attendees). الرجاء تشغيل ترحيل القاعدة.';
}

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">حجوزات المول</h3>
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
    <h5 class="mb-3">إضافة حجز جديد</h5>
    <form method="post" class="row g-3">
      <input type="hidden" name="action" value="add">
      <div class="col-md-4">
        <label class="form-label">المرفق</label>
        <select name="venue_id" class="form-select" required>
          <option value="">اختر</option>
          <?php foreach ($venues as $v): ?>
            <option value="<?php echo (int)$v['id']; ?>"><?php echo htmlspecialchars($v['name_ar']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">العنوان</label>
        <input class="form-control" name="title" required>
      </div>
      <div class="col-md-2">
        <label class="form-label">بداية</label>
        <input type="datetime-local" class="form-control" name="starts_at" required>
      </div>
      <div class="col-md-2">
        <label class="form-label">نهاية</label>
        <input type="datetime-local" class="form-control" name="ends_at">
      </div>
      <div class="col-md-8">
        <label class="form-label">ملاحظات</label>
        <input class="form-control" name="notes">
      </div>
      <div class="col-md-4">
        <label class="form-label">الحالة</label>
        <select name="status" class="form-select" required>
          <option value="scheduled">مجدول</option>
          <option value="completed">مكتمل</option>
          <option value="cancelled">ملغي</option>
        </select>
      </div>
      <div class="col-12">
        <button class="btn btn-gradient" type="submit">إضافة حجز</button>
      </div>
    </form>
  </div>

  <div class="table-responsive feature-card">
    <?php
    // Prepare labels and badges
    $statusBadges = [
      'scheduled' => 'bg-info',
      'completed' => 'bg-success',
      'cancelled' => 'bg-danger'
    ];
    $statusLabels = [
      'scheduled' => 'مجدول',
      'completed' => 'مكتمل',
      'cancelled' => 'ملغي'
    ];
    $types = [
      'cinema' => 'سينما',
      'games' => 'صالة ألعاب',
      'events' => 'قاعة فعاليات'
    ];
    ?>

    <table class="table table-dark table-striped align-middle mb-0 text-white">
      <thead>
        <tr>
          <th>#</th>
          <th>المرفق</th>
          <th>العنصر / العنوان</th>
          <th>مقدم الطلب</th>
          <th>بداية</th>
          <th>نهاية</th>
          <th>الحضور</th>
          <th>الحالة</th>
          <th>ملاحظات</th>
          <th>إجراءات</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr>
            <td colspan="10" class="text-center text-white">لا توجد حجوزات</td>
          </tr>
        <?php else: ?>
          <?php foreach ($rows as $i => $r): ?>
            <tr>
              <td><?php echo $i + 1; ?></td>
              <td>
                <?php echo htmlspecialchars($r['venue_name']); ?>
                <div class="small text-white"><?php echo $types[$r['venue_type']] ?? $r['venue_type']; ?></div>
              </td>
              <td><?php echo htmlspecialchars($r['title']); ?></td>
              <td>
                <?php echo htmlspecialchars($r['user_name'] ?? '—'); ?><br>
                <small class="text-white"><?php echo htmlspecialchars($r['user_phone'] ?? ''); ?></small>
              </td>
              <td><?php echo date('Y/m/d', strtotime($r['starts_at'])); ?><br><small class="text-white"><?php echo date('H:i', strtotime($r['starts_at'])); ?></small></td>
              <td><?php echo $r['ends_at'] ? date('Y/m/d', strtotime($r['ends_at'])) . '<br><small class="text-white">' . date('H:i', strtotime($r['ends_at'])) . '</small>' : '—'; ?></td>
              <td><?php echo htmlspecialchars($r['attendees'] ?? '—'); ?></td>
              <td><span class="badge <?php echo $statusBadges[$r['status']] ?? 'bg-secondary'; ?>"><?php echo $statusLabels[$r['status']] ?? htmlspecialchars($r['status']); ?></span></td>
              <td style="max-width:240px;white-space:normal"><?php echo nl2br(htmlspecialchars($r['notes'] ?? '—')); ?></td>
              <td>
                <button type="button" class="btn btn-sm btn-outline-light mb-1" data-bs-toggle="modal" data-bs-target="#editBooking<?php echo $r['id']; ?>">تعديل</button>
                <form method="post" class="d-inline" onsubmit="return confirm('هل أنت متأكد من إلغاء هذا الحجز؟');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="booking_id" value="<?php echo $r['id']; ?>">
                  <button type="submit" class="btn btn-sm btn-danger">إلغاء</button>
                </form>
              </td>
            </tr>

            <!-- Edit Modal -->
            <div class="modal fade" id="editBooking<?php echo $r['id']; ?>" tabindex="-1">
              <div class="modal-dialog modal-lg">
                <div class="modal-content bg-dark text-white">
                  <div class="modal-header">
                    <h5 class="modal-title">تعديل الحجز #<?php echo $r['id']; ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <form method="post">
                      <input type="hidden" name="action" value="edit">
                      <input type="hidden" name="booking_id" value="<?php echo $r['id']; ?>">
                      <div class="row g-3">
                        <div class="col-md-6">
                          <label class="form-label">المرفق</label>
                          <select name="venue_id" class="form-select" required>
                            <?php foreach ($venues as $v): ?>
                              <option value="<?php echo $v['id']; ?>" <?php echo $r['venue_id'] == $v['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($v['name_ar']); ?></option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                        <div class="col-md-6">
                          <label class="form-label">العنوان</label>
                          <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($r['title']); ?>" required>
                        </div>
                        <div class="col-md-6">
                          <label class="form-label">بداية</label>
                          <input type="datetime-local" class="form-control" name="starts_at" value="<?php echo date('Y-m-d\TH:i', strtotime($r['starts_at'])); ?>" required>
                        </div>
                        <div class="col-md-6">
                          <label class="form-label">نهاية</label>
                          <input type="datetime-local" class="form-control" name="ends_at" value="<?php echo $r['ends_at'] ? date('Y-m-d\TH:i', strtotime($r['ends_at'])) : ''; ?>">
                        </div>
                        <div class="col-md-6">
                          <label class="form-label">عدد الحضور</label>
                          <input type="number" class="form-control" name="attendees" value="<?php echo htmlspecialchars($r['attendees'] ?? 1); ?>" min="1">
                        </div>
                        <div class="col-md-6">
                          <label class="form-label">الحالة</label>
                          <select name="status" class="form-select" required>
                            <?php foreach ($statusLabels as $value => $label): ?>
                              <option value="<?php echo $value; ?>" <?php echo $r['status'] === $value ? 'selected' : ''; ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                        <div class="col-12">
                          <label class="form-label">ملاحظات</label>
                          <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($r['notes'] ?? ''); ?></textarea>
                        </div>
                      </div>
                      <div class="mt-3 text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">حفظ</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            </div>

          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>