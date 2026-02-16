<?php
$page_title = 'حجوزات البنك';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$user = $_SESSION['user'] ?? null;
if (!$user || empty($user['id'])) {
    header('Location: ' . APP_BASE . '/auth/logout.php');
    exit;
}

$pdo = DB::conn();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $starts_at = trim($_POST['starts_at'] ?? '');
    $ends_at = trim($_POST['ends_at'] ?? '') ?: null;
    $type = $_POST['type'] ?? 'general';
    $notes = trim($_POST['notes'] ?? '');

    if ($starts_at === '' || strtotime($starts_at) === false) {
        $error = 'يرجى اختيار وقت صحيح للحجز';
    } else {
        $ins = $pdo->prepare('INSERT INTO bank_appointments(user_id, starts_at, ends_at, type, notes) VALUES(?,?,?,?,?)');
        try {
            $ins->execute([(int)$user['id'], $starts_at, $ends_at ?: null, $type, $notes ?: null]);
            redirect('/bank/appointments.php?success=1');
        } catch (PDOException $e) {
            $error = 'حدث خطأ أثناء طلب الحجز';
        }
    }
}

// fetch user's appointments
$rows = $pdo->prepare('SELECT * FROM bank_appointments WHERE user_id = ? ORDER BY starts_at DESC');
$rows->execute([(int)$user['id']]);
$rows = $rows->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<main class="container section-padding">
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">تم طلب الحجز بنجاح</div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">حجوزات البنك</h3>
        <a href="index.php" class="btn btn-outline-primary">رجوع</a>
    </div>

    <?php if (!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="feature-card mb-4">
        <form method="post" class="row g-3">
            <div class="col-md-6"><label class="form-label">بداية</label><input type="datetime-local" name="starts_at" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">نهاية (اختياري)</label><input type="datetime-local" name="ends_at" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">نوع</label>
                <select name="type" class="form-select">
                    <option value="general">استفسار عام</option>
                    <option value="account_opening">فتح حساب</option>
                    <option value="loan">قرض</option>
                </select>
            </div>
            <div class="col-12"><label class="form-label">ملاحظات</label><textarea name="notes" class="form-control" rows="3"></textarea></div>
            <div class="col-12 d-grid"><button class="btn btn-gradient" type="submit">طلب موعد</button></div>
        </form>
    </div>

    <h5 class="mb-3">حجوزاتي</h5>
    <?php if (empty($rows)): ?><div class="alert alert-info">لم تقم بأي حجوزات</div><?php else: ?>
        <div class="row g-3">
            <?php foreach ($rows as $r): ?>
                <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h6 class="mb-1"><?= htmlspecialchars($r['type']) ?></h6>
                            <div class="small text-muted">من <?= htmlspecialchars($r['starts_at']) ?> <?= $r['ends_at'] ? 'إلى ' . htmlspecialchars($r['ends_at']) : '' ?></div>
                            <div class="mt-2 small">الحالة: <?= htmlspecialchars($r['status']) ?></div>
                            <?php if ($r['notes']): ?><p class="mt-2 small"><?= nl2br(htmlspecialchars($r['notes'])) ?></p><?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>