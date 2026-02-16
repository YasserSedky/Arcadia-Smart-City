<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
include __DIR__ . '/../includes/header.php';
$page_title = 'الخدمات';
$pdo = DB::conn();
$user = $_SESSION['user'];

// Create maintenance ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['__action']) && $_POST['__action'] === 'create') {
    $title = trim($_POST['title'] ?? '');
    $details = trim($_POST['details'] ?? '');
    $priority = $_POST['priority'] ?? 'medium';
    $unit_code = trim($_POST['unit_code'] ?? '');
    $unit_id = null;
    
    // If user has unit_id, use it, otherwise try to find by unit_code
    if (!empty($user['unit_id'])) {
        $unit_id = (int)$user['unit_id'];
    } elseif ($unit_code !== '') {
        $u = $pdo->prepare('SELECT id FROM units WHERE unit_code=?');
        $u->execute([$unit_code]);
        $ur = $u->fetch();
        if ($ur) $unit_id = (int)$ur['id'];
    }
    
    if ($title !== '') {
        $stmt = $pdo->prepare('INSERT INTO maintenance_tickets(created_by_user_id, unit_id, title, details, priority) VALUES(?,?,?,?,?)');
        try {
            $stmt->execute([(int)$user['id'], $unit_id, $title, $details ?: null, $priority]);
            $_SESSION['success'] = 'تم إرسال بلاغ الصيانة بنجاح';
        } catch (PDOException $e) {
            $_SESSION['error'] = 'حدث خطأ أثناء إرسال البلاغ';
        }
    } else {
        $_SESSION['error'] = 'يرجى إدخال عنوان البلاغ';
    }
    redirect('/services/index.php');
}

// Get user's maintenance tickets
$userTickets = [];
if (!empty($user['id'])) {
    $stmt = $pdo->prepare("
        SELECT mt.*, 
               u.unit_code,
               CASE 
                   WHEN mt.status = 'new' THEN 'جديد'
                   WHEN mt.status = 'in_progress' THEN 'قيد التنفيذ'
                   WHEN mt.status = 'assigned' THEN 'مُسند'
                   WHEN mt.status = 'completed' THEN 'مكتمل'
                   WHEN mt.status = 'closed' THEN 'مغلق'
                   ELSE mt.status
               END as status_ar,
               CASE
                   WHEN mt.priority = 'low' THEN 'منخفض'
                   WHEN mt.priority = 'medium' THEN 'متوسط'
                   WHEN mt.priority = 'high' THEN 'عالي'
                   WHEN mt.priority = 'urgent' THEN 'طارئ'
                   ELSE mt.priority
               END as priority_ar
        FROM maintenance_tickets mt
        LEFT JOIN units u ON u.id = mt.unit_id
        WHERE mt.created_by_user_id = ?
        ORDER BY mt.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([(int)$user['id']]);
    $userTickets = $stmt->fetchAll();
}

// Get user's unit if exists
$userUnit = null;
if (!empty($user['unit_id'])) {
    $stmt = $pdo->prepare("SELECT unit_code FROM units WHERE id = ?");
    $stmt->execute([(int)$user['unit_id']]);
    $userUnit = $stmt->fetch();
}
?>

<main class="container section-padding">
    <h2 class="mb-4">قسم الخدمات</h2>
    <p class="text-white">مرحباً، <?php echo htmlspecialchars($user['name'] ?? ''); ?> — يمكنك إرسال بلاغات صيانة ومتابعة حالة بلاغاتك.</p>

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

    <div class="row g-4 mt-3">
        <div class="col-md-6" data-aos="fade-up">
            <div class="feature-card h-100">
                <i class="bi bi-tools"></i>
                <h5 class="mt-3">بلاغات الصيانة</h5>
                <p class="mb-0">إرسال بلاغات صيانة ومتابعة حالتها</p>
            </div>
        </div>
        <div class="col-md-6" data-aos="fade-up">
            <div class="feature-card h-100">
                <i class="bi bi-flower1"></i>
                <h5 class="mt-3">الخدمات العامة</h5>
                <p class="mb-0">صيانة، حدائق، ونظافة</p>
            </div>
        </div>
    </div>

    <div class="feature-card mt-4">
        <h5 class="mb-3">إرسال بلاغ صيانة جديد</h5>
        <form method="post" class="row g-3">
            <input type="hidden" name="__action" value="create">
            <div class="col-md-6">
                <label class="form-label">عنوان البلاغ</label>
                <input type="text" class="form-control" name="title" required placeholder="مثال: تسريب مياه في الحمام">
            </div>
            <div class="col-md-3">
                <label class="form-label">الأولوية</label>
                <select name="priority" class="form-select" required>
                    <option value="low">منخفضة</option>
                    <option value="medium" selected>متوسطة</option>
                    <option value="high">مرتفعة</option>
                    <option value="urgent">عاجلة</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">رقم الوحدة</label>
                <input type="text" class="form-control" name="unit_code" 
                       value="<?php echo htmlspecialchars($userUnit['unit_code'] ?? ''); ?>" 
                       placeholder="<?php echo $userUnit ? 'مربوطة تلقائياً' : 'B01-A3 أو V012'; ?>"
                       <?php echo $userUnit ? 'readonly' : ''; ?>>
            </div>
            <div class="col-12">
                <label class="form-label">تفاصيل البلاغ</label>
                <textarea class="form-control" name="details" rows="3" placeholder="اكتب تفاصيل المشكلة..."></textarea>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-gradient">إرسال البلاغ</button>
            </div>
        </form>
    </div>

    <div class="feature-card mt-4">
        <h5 class="mb-3">بلاغاتي</h5>
        <?php if (empty($userTickets)): ?>
            <div class="alert alert-info">لم تقم بإرسال أي بلاغات صيانة بعد</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-dark table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الوحدة</th>
                            <th>العنوان</th>
                            <th>الأولوية</th>
                            <th>الحالة</th>
                            <th>التاريخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($userTickets as $ticket): ?>
                            <tr>
                                <td><?php echo (int)$ticket['id']; ?></td>
                                <td><?php echo htmlspecialchars($ticket['unit_code'] ?? '—'); ?></td>
                                <td><?php echo htmlspecialchars($ticket['title']); ?></td>
                                <td>
                                    <span class="badge 
                                        <?php 
                                        echo $ticket['priority'] === 'urgent' ? 'bg-danger' : 
                                            ($ticket['priority'] === 'high' ? 'bg-warning' : 
                                            ($ticket['priority'] === 'medium' ? 'bg-info' : 'bg-secondary')); 
                                        ?>">
                                        <?php echo htmlspecialchars($ticket['priority_ar']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge 
                                        <?php 
                                        echo $ticket['status'] === 'completed' ? 'bg-success' : 
                                            ($ticket['status'] === 'in_progress' ? 'bg-primary' : 
                                            ($ticket['status'] === 'closed' ? 'bg-secondary' : 'bg-warning')); 
                                        ?>">
                                        <?php echo htmlspecialchars($ticket['status_ar']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($ticket['created_at']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

