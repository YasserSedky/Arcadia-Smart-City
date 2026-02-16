<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../backend/config.php';
require_once __DIR__ . '/../backend/database.php';

$page_title = 'حجوزاتي - المول';
$pdo = Database::getInstance();

// Handle user actions (cancel their own booking)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel') {
    $booking_id = (int)($_POST['booking_id'] ?? 0);
    if ($booking_id > 0) {
        try {
            $stmt = $pdo->prepare('UPDATE mall_bookings SET status = ? WHERE id = ? AND user_id = ?');
            $stmt->execute(['cancelled', $booking_id, $_SESSION['user']['id']]);
            $_SESSION['mall_booking_success'] = 'تم إلغاء الحجز بنجاح.';
        } catch (PDOException $e) {
            $_SESSION['mall_booking_error'] = 'حدث خطأ أثناء إلغاء الحجز: ' . $e->getMessage();
        }
    }
    // Do redirect before including header
    redirect(APP_BASE . '/mall/bookings.php');
}

// Now include header after any redirects
include __DIR__ . '/../includes/header.php';

$bookings = [];
try {
    $stmt = $pdo->prepare(
        'SELECT b.*, v.name_ar as venue_name, v.type as venue_type
         FROM mall_bookings b
         JOIN mall_venues v ON v.id = b.venue_id
         WHERE b.user_id = ?
         ORDER BY b.starts_at DESC'
    );
    $stmt->execute([$_SESSION['user']['id']]);
    $bookings = $stmt->fetchAll();
} catch (PDOException $e) {
    // If user_id column is missing or table not present, show friendly message
    $err = $e->getMessage();
}

// Split into upcoming and past
$upcoming = [];
$past = [];
$now = new DateTime();
foreach ($bookings as $b) {
    $start = new DateTime($b['starts_at']);
    if ($start > $now) $upcoming[] = $b;
    else $past[] = $b;
}
?>
<main class="container section-padding">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">حجوزاتي</h2>
        <a href="<?php echo APP_BASE; ?>/mall/venues.php" class="btn btn-outline-light">رجوع</a>
    </div>

    <?php if (!empty($_SESSION['mall_booking_success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['mall_booking_success'];
                                            unset($_SESSION['mall_booking_success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['mall_booking_error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['mall_booking_error'];
                                        unset($_SESSION['mall_booking_error']); ?></div>
    <?php endif; ?>

    <?php if (!empty($err)): ?>
        <div class="alert alert-warning">
            يوجد خطأ في قاعدة البيانات عند استرجاع الحجوزات: <?php echo htmlspecialchars($err); ?>
            <br>الحل: تأكد من وجود عمود <code>user_id</code> في جدول <code>mall_bookings</code> وتشغيل ملف الترحيل الموجود في <code>backend/migrations</code>.
        </div>
    <?php endif; ?>

    <?php if (empty($bookings)): ?>
        <div class="feature-card">
            <p class="mb-0">لا توجد لديك حجوزات حتى الآن.</p>
        </div>
    <?php else: ?>
        <?php if (!empty($upcoming)): ?>
            <h3 class="mb-4">الحجوزات القادمة</h3>
            <div class="row g-4 mb-5">
                <?php foreach ($upcoming as $booking): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card h-100">
                            <h5 class="mb-3"><?php echo htmlspecialchars($booking['title']); ?></h5>
                            <p class="mb-2">
                                <strong>القاعة:</strong>
                                <?php echo htmlspecialchars($booking['venue_name']); ?>
                            </p>
                            <p class="mb-2">
                                <strong>التاريخ:</strong>
                                <?php echo date('Y/m/d', strtotime($booking['starts_at'])); ?>
                            </p>
                            <p class="mb-2">
                                <strong>الوقت:</strong>
                                <?php echo date('H:i', strtotime($booking['starts_at'])) . ' - ' . date('H:i', strtotime($booking['ends_at'] ?? $booking['starts_at'])); ?>
                            </p>
                            <p class="mb-2">
                                <strong>عدد الحضور:</strong>
                                <?php echo htmlspecialchars($booking['attendees'] ?? '—'); ?> شخص
                            </p>
                            <?php if (!empty($booking['notes'])): ?>
                                <p class="mb-0"><strong>ملاحظات:</strong> <?php echo nl2br(htmlspecialchars($booking['notes'])); ?></p>
                            <?php endif; ?>

                            <?php // Show cancel button only if DB query worked and booking is not already cancelled 
                            ?>
                            <?php if (empty($err) && (!isset($booking['status']) || $booking['status'] !== 'cancelled')): ?>
                                <div class="mt-3">
                                    <form method="post" onsubmit="return confirm('هل أنت متأكد من إلغاء هذا الحجز؟');">
                                        <input type="hidden" name="action" value="cancel">
                                        <input type="hidden" name="booking_id" value="<?php echo (int)$booking['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">إلغاء الحجز</button>
                                    </form>
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($past)): ?>
            <h3 class="mb-4">الحجوزات السابقة</h3>
            <div class="row g-4">
                <?php foreach ($past as $booking): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card h-100 opacity-75">
                            <h5 class="mb-3"><?php echo htmlspecialchars($booking['title']); ?></h5>
                            <p class="mb-2">
                                <strong>القاعة:</strong>
                                <?php echo htmlspecialchars($booking['venue_name']); ?>
                            </p>
                            <p class="mb-2">
                                <strong>التاريخ:</strong>
                                <?php echo date('Y/m/d', strtotime($booking['starts_at'])); ?>
                            </p>
                            <p class="mb-2">
                                <strong>الوقت:</strong>
                                <?php echo date('H:i', strtotime($booking['starts_at'])) . ' - ' . date('H:i', strtotime($booking['ends_at'] ?? $booking['starts_at'])); ?>
                            </p>
                            <p class="mb-0">
                                <strong>عدد الحضور:</strong>
                                <?php echo htmlspecialchars($booking['attendees'] ?? '—'); ?> شخص
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php';
