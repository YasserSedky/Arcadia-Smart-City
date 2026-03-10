<?php
$page_title = 'بلاغات الصيانة';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'services_admin', 'residential_admin', 'maintenance_worker', 'resident'])) {
  redirect('/dashboard/index.php');
}
$pdo = DB::conn();

// Create ticket (residents or admins)
if (isset($_POST['__action']) && $_POST['__action'] === 'create') {
  $title = trim($_POST['title'] ?? '');
  $details = trim($_POST['details'] ?? '');
  $priority = $_POST['priority'] ?? 'medium';
  $unit_code = trim($_POST['unit_code'] ?? '');
  $unit_id = null;
  if ($unit_code !== '') {
    $u = $pdo->prepare('SELECT id FROM units WHERE unit_code=?');
    $u->execute([$unit_code]);
    $ur = $u->fetch();
    if ($ur) $unit_id = (int)$ur['id'];
  }
  if ($title !== '') {
    $stmt = $pdo->prepare('INSERT INTO maintenance_tickets(created_by_user_id, unit_id, title, details, priority) VALUES(?,?,?,?,?)');
    try {
      $stmt->execute([$_SESSION['user']['id'], $unit_id, $title, $details ?: null, $priority]);
    } catch (PDOException $e) {
    }
  }
  redirect('/dashboard/services/tickets.php');
}

// Assign ticket (admin/services) to worker
if (isset($_POST['__action']) && $_POST['__action'] === 'assign') {
  $ticket_id = (int)($_POST['ticket_id'] ?? 0);
  $worker_id = (int)($_POST['worker_user_id'] ?? 0);
  if ($ticket_id > 0 && $worker_id > 0) {
    $pdo->beginTransaction();
    try {
      $pdo->prepare('INSERT INTO maintenance_assignments(ticket_id, worker_user_id) VALUES(?,?)')->execute([$ticket_id, $worker_id]);
      $pdo->prepare("UPDATE maintenance_tickets SET status='assigned' WHERE id=?")->execute([$ticket_id]);
      $pdo->commit();
    } catch (PDOException $e) {
      $pdo->rollBack();
    }
  }
  redirect('/dashboard/services/tickets.php');
}

// Update status (worker or admin)
if (isset($_POST['__action']) && $_POST['__action'] === 'status') {
  $ticket_id = (int)($_POST['ticket_id'] ?? 0);
  $status = $_POST['status'] ?? 'in_progress';
  if ($ticket_id > 0) {
    $pdo->prepare('UPDATE maintenance_tickets SET status=? WHERE id=?')->execute([$status, $ticket_id]);
  }
  redirect('/dashboard/services/tickets.php');
}

$workers = $pdo->query("SELECT u.id, u.full_name FROM users u JOIN roles r ON r.id=u.role_id WHERE r.code='maintenance_worker' ORDER BY u.full_name")->fetchAll();

$rows = $pdo->query('SELECT t.*, u.full_name AS creator, un.unit_code
  FROM maintenance_tickets t
  JOIN users u ON u.id=t.created_by_user_id
  LEFT JOIN units un ON un.id=t.unit_id
  ORDER BY t.created_at DESC LIMIT 200')->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">بلاغات الصيانة</h3>
    <a href="<?php echo APP_BASE; ?>/dashboard/services/index.php" class="btn btn-outline-light">رجوع</a>
  </div>

  <div class="feature-card mb-4">
    <h5 class="mb-3">فتح بلاغ</h5>
    <form method="post" class="row g-3">
      <input type="hidden" name="__action" value="create">
      <div class="col-md-4"><label class="form-label">العنوان</label><input class="form-control" name="title" required></div>
      <div class="col-md-3"><label class="form-label">أولوية</label>
        <select name="priority" class="form-select">
          <option value="low">منخفضة</option>
          <option value="medium" selected>متوسطة</option>
          <option value="high">مرتفعة</option>
          <option value="urgent">عاجلة</option>
        </select>
      </div>
      <div class="col-md-3"><label class="form-label">رقم الوحدة (اختياري)</label><input class="form-control" name="unit_code" placeholder="B01-A3 أو V012"></div>
      <div class="col-md-12"><label class="form-label">التفاصيل</label><textarea name="details" class="form-control" rows="2"></textarea></div>
      <div class="col-12 d-grid d-md-flex gap-2"><button class="btn btn-gradient" type="submit">إرسال</button></div>
    </form>
  </div>

  <div class="table-responsive feature-card">
    <table class="table table-dark table-striped align-middle mb-0">
      <thead>
        <tr>
          <th>#</th>
          <th>الوحدة</th>
          <th>العنوان</th>
          <th>الأولوية</th>
          <th>الحالة</th>
          <th>أنشئ بواسطة</th>
          <th>إجراءات</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?php echo (int)$r['id']; ?></td>
            <td><?php echo htmlspecialchars($r['unit_code'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($r['title']); ?></td>
            <td><?php echo htmlspecialchars($r['priority']); ?></td>
            <td><?php echo htmlspecialchars($r['status']); ?></td>
            <td><?php echo htmlspecialchars($r['creator']); ?></td>
            <td>
              <form method="post" class="d-flex gap-2 align-items-center">
                <input type="hidden" name="ticket_id" value="<?php echo (int)$r['id']; ?>">
                <input type="hidden" name="__action" value="assign">
                <select name="worker_user_id" class="form-select form-select-sm" style="width:160px">
                  <option value="">إسناد لعامل</option>
                  <?php foreach ($workers as $w): ?><option value="<?php echo (int)$w['id']; ?>"><?php echo htmlspecialchars($w['full_name']); ?></option><?php endforeach; ?>
                </select>
                <button class="btn btn-sm btn-gradient" type="submit">إسناد</button>
              </form>
              <form method="post" class="d-flex gap-2 align-items-center mt-2">
                <input type="hidden" name="ticket_id" value="<?php echo (int)$r['id']; ?>">
                <input type="hidden" name="__action" value="status">
                <select name="status" class="form-select form-select-sm" style="width:160px">
                  <option value="open">Open</option>
                  <option value="assigned">Assigned</option>
                  <option value="in_progress">In Progress</option>
                  <option value="resolved">Resolved</option>
                  <option value="closed">Closed</option>
                </select>
                <button class="btn btn-sm btn-outline-light" type="submit">تحديث</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?><tr>
            <td colspan="7" class="text-center text-muted">لا توجد بلاغات</td>
          </tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
