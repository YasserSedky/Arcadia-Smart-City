<?php
$page_title = 'قاعة المؤتمرات والفعاليات';
require_once __DIR__ . '/../includes/auth.php';
// public listing does not require login to view venues
$pdo = DB::conn();
$venues = $pdo->query('SELECT id, name_ar, type, capacity FROM conf_venues ORDER BY name_ar')->fetchAll();
include __DIR__ . '/../includes/header.php';
?>

<main class="container section-padding">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">القاعات والفعاليات</h3>
        <?php if (!empty($_SESSION['user'])): ?>
            <a href="mybookings.php" class="btn btn-outline-primary">حجوزاتي</a>
        <?php else: ?>
            <a href="/auth/login.php" class="btn btn-outline-primary">تسجيل الدخول للحجز</a>
        <?php endif; ?>
    </div>

    <?php if (empty($venues)): ?>
        <div class="alert alert-info">لا توجد قاعات حالياً</div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($venues as $v): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($v['name_ar']) ?></h5>
                            <div class="text-muted mb-2"><?= htmlspecialchars($v['type']) ?> — <?= $v['capacity'] ? intval($v['capacity']) . ' شخص' : 'السعة غير محددة' ?></div>
                            <a href="bookings.php?venue_id=<?= (int)$v['id'] ?>" class="btn btn-sm btn-outline-primary">حجز القاعة / طلب حجز</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>