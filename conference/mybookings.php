<?php
$page_title = 'حجوزاتي - قاعات';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$user = $_SESSION['user'] ?? null;
if (!$user || empty($user['id'])) {
    header('Location: ' . APP_BASE . '/auth/logout.php');
    exit;
}

$pdo = DB::conn();
$rows = $pdo->prepare('SELECT b.*, v.name_ar as venue_name FROM conf_bookings b JOIN conf_venues v ON v.id=b.venue_id WHERE b.user_id = ? ORDER BY b.starts_at DESC');
$rows->execute([(int)$user['id']]);
$rows = $rows->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<main class="container section-padding">
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">تم ارسال طلب الحجز بنجاح</div>
    <?php endif; ?>

    <h4 class="mb-4">حجوزاتي</h4>
    <?php if (empty($rows)): ?>
        <div class="alert alert-info">لم تقم بأي طلبات حجز</div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($rows as $r): ?>
                <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($r['title']) ?></h5>
                            <div class="text-muted"><?= htmlspecialchars($r['venue_name']) ?></div>
                            <p class="mb-1">من <?= htmlspecialchars($r['starts_at']) ?> <?= $r['ends_at'] ? 'إلى ' . htmlspecialchars($r['ends_at']) : '' ?></p>
                            <div class="small text-muted">الحالة: <?= htmlspecialchars($r['status']) ?></div>
                            <?php if ($r['notes']): ?><p class="mt-2 small"><?= nl2br(htmlspecialchars($r['notes'])) ?></p><?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="text-center mt-5">
        <a href="index.php" class="btn btn-gradient">عرض القاعات</a>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>