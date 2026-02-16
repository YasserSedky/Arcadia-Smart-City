<?php
$page_title = 'حجوزاتي';
require_once __DIR__ . '/../includes/auth.php';
require_login();

// Get current logged-in user from session
$user = $_SESSION['user'] ?? null;
$pdo = DB::conn();
if (!$user || empty($user['id'])) {
    // If session does not contain user info, redirect to logout to clear session
    header('Location: ' . APP_BASE . '/auth/logout.php');
    exit;
}

// Fetch fresh user data from DB (optional, keeps session lightweight)
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([(int)$user['id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: ' . APP_BASE . '/auth/logout.php');
    exit;
}

// Get user's bookings
$bookings = $pdo->prepare('SELECT b.*, f.name_ar as facility_name, f.price_per_hour,
    TIMESTAMPDIFF(HOUR, b.starts_at, b.ends_at) as duration
    FROM sports_bookings b
    JOIN sports_facilities f ON f.id = b.facility_id
    WHERE b.user_id = ?
    ORDER BY b.starts_at DESC');
$bookings->execute([$user['id']]);
$bookings = $bookings->fetchAll();

// Get user's activity registrations
$registrations = $pdo->prepare('SELECT r.*, a.name_ar, a.instructor_name, 
    a.starts_at, a.ends_at, a.price,
    f.name_ar as facility_name
    FROM sports_registrations r
    JOIN sports_activities a ON a.id = r.activity_id
    JOIN sports_facilities f ON f.id = a.facility_id
    WHERE r.user_id = ?
    ORDER BY a.starts_at DESC');
$registrations->execute([$user['id']]);
$registrations = $registrations->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<main class="container section-padding">
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            تم حفظ الحجز بنجاح
            <button type="button" class="btn-close ms-0 me-2" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Facility Bookings -->
    <h4 class="mb-4">حجوزات المرافق</h4>
    <?php if (empty($bookings)): ?>
        <div class="alert alert-info">لا توجد حجوزات</div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($bookings as $booking): ?>
                <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="card-title"><?= htmlspecialchars($booking['facility_name']) ?></h5>
                                    <div class="text-muted">
                                        <?= (new DateTime($booking['starts_at']))->format('Y-m-d') ?>
                                    </div>
                                </div>
                                <span class="badge bg-<?=
                                                        $booking['status'] === 'confirmed' ? 'success' : ($booking['status'] === 'cancelled' ? 'danger' : 'warning')
                                                        ?>">
                                    <?= $booking['status'] === 'confirmed' ? 'مؤكد' : ($booking['status'] === 'cancelled' ? 'ملغي' : 'قيد المراجعة') ?>
                                </span>
                            </div>

                            <p class="mb-2">
                                <i class="bi bi-clock me-2"></i>
                                من <?= (new DateTime($booking['starts_at']))->format('H:i') ?>
                                إلى <?= (new DateTime($booking['ends_at']))->format('H:i') ?>
                                (<?= $booking['duration'] ?> ساعة)
                            </p>

                            <div class="text-gradient mb-2">
                                <?= number_format($booking['duration'] * $booking['price_per_hour']) ?> جنيه
                            </div>

                            <?php if ($booking['notes']): ?>
                                <p class="mb-0 small">
                                    <i class="bi bi-chat me-2"></i>
                                    <?= nl2br(htmlspecialchars($booking['notes'])) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Activity Registrations -->
    <h4 class="mb-4 mt-5">الأنشطة المسجل فيها</h4>
    <?php if (empty($registrations)): ?>
        <div class="alert alert-info">لم تسجل في أي نشاط بعد</div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($registrations as $reg): ?>
                <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="card-title"><?= htmlspecialchars($reg['name_ar']) ?></h5>
                                    <div class="text-muted"><?= htmlspecialchars($reg['facility_name']) ?></div>
                                </div>
                                <span class="badge bg-<?=
                                                        $reg['status'] === 'active' ? 'success' : ($reg['status'] === 'cancelled' ? 'danger' : 'info')
                                                        ?>">
                                    <?= $reg['status'] === 'active' ? 'نشط' : ($reg['status'] === 'cancelled' ? 'ملغي' : 'مكتمل') ?>
                                </span>
                            </div>

                            <?php if ($reg['instructor_name']): ?>
                                <p class="mb-2">
                                    <i class="bi bi-person me-2"></i>
                                    المدرب: <?= htmlspecialchars($reg['instructor_name']) ?>
                                </p>
                            <?php endif; ?>

                            <p class="mb-2">
                                <i class="bi bi-calendar3 me-2"></i>
                                <?= (new DateTime($reg['starts_at']))->format('Y-m-d') ?>
                            </p>

                            <div class="text-gradient">
                                <?= number_format($reg['price']) ?> جنيه
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="text-center mt-5">
        <a href="index.php" class="btn btn-gradient">
            <i class="bi bi-grid me-2"></i>
            عرض المرافق والأنشطة
        </a>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>