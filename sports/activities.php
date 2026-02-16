<?php
$page_title = 'الأنشطة الرياضية';
require_once __DIR__ . '/../includes/auth.php';
require_login();

// current logged-in user
$user = $_SESSION['user'] ?? null;
if (!$user || empty($user['id'])) {
    // session missing user info — clear session and force login
    header('Location: ' . APP_BASE . '/auth/logout.php');
    exit;
}

$pdo = DB::conn();

// Handle registration
if (isset($_GET['register'])) {
    $activity_id = (int)$_GET['register'];

    // Check if activity exists and is active
    $activity = $pdo->prepare('SELECT * FROM sports_activities 
        WHERE id = ? AND status = "active" AND starts_at > NOW()');
    $activity->execute([$activity_id]);
    $activity = $activity->fetch();

    if ($activity) {
        // Check if already registered
        $check = $pdo->prepare('SELECT 1 FROM sports_registrations 
            WHERE activity_id = ? AND user_id = ? AND status = "active"');
        $check->execute([$activity_id, (int)$user['id']]);

        if (!$check->fetch()) {
            // Check capacity
            $count = $pdo->prepare('SELECT COUNT(*) FROM sports_registrations 
                WHERE activity_id = ? AND status = "active"');
            $count->execute([$activity_id]);

            if ($count->fetchColumn() < $activity['capacity']) {
                $stmt = $pdo->prepare('INSERT INTO sports_registrations(activity_id, user_id) 
                    VALUES(?,?)');
                try {
                    $stmt->execute([$activity_id, (int)$user['id']]);
                    redirect('/sports/mybookings.php?success=1');
                } catch (PDOException $e) {
                    $error = 'حدث خطأ في التسجيل';
                }
            } else {
                $error = 'عذراً، تم اكتمال العدد';
            }
        } else {
            $error = 'أنت مسجل مسبقاً في هذا النشاط';
        }
    }

    // If we reach here, redirect back with error
    redirect('/sports/activities.php?error=' . urlencode($error ?? 'حدث خطأ في التسجيل'));
}

// Get all active activities
$activities = $pdo->query('SELECT a.*, f.name_ar as facility_name,
    (SELECT COUNT(*) FROM sports_registrations 
     WHERE activity_id = a.id AND status = "active") as registered_count
    FROM sports_activities a
    JOIN sports_facilities f ON f.id = a.facility_id
    WHERE a.status = "active" 
    AND a.starts_at > NOW()
    ORDER BY a.starts_at')->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<main class="container section-padding">
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($_GET['error']) ?>
            <button type="button" class="btn-close ms-0 me-2" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">الأنشطة الرياضية</h4>
        <a href="mybookings.php" class="btn btn-outline-primary">
            <i class="bi bi-calendar2-check me-2"></i>
            أنشطتي
        </a>
    </div>

    <?php if (empty($activities)): ?>
        <div class="alert alert-info">لا توجد أنشطة متاحة حالياً</div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($activities as $activity): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($activity['name_ar']) ?></h5>
                            <h6 class="text-muted mb-3"><?= htmlspecialchars($activity['facility_name']) ?></h6>

                            <?php if ($activity['instructor_name']): ?>
                                <p class="mb-2">
                                    <i class="bi bi-person me-2"></i>
                                    المدرب: <?= htmlspecialchars($activity['instructor_name']) ?>
                                </p>
                            <?php endif; ?>

                            <p class="mb-2">
                                <i class="bi bi-calendar3 me-2"></i>
                                <?= (new DateTime($activity['starts_at']))->format('Y-m-d H:i') ?>
                            </p>

                            <?php if ($activity['schedule']): ?>
                                <p class="mb-2">
                                    <i class="bi bi-clock me-2"></i>
                                    <?= htmlspecialchars($activity['schedule']) ?>
                                </p>
                            <?php endif; ?>

                            <p class="mb-2">
                                <i class="bi bi-people me-2"></i>
                                المسجلين: <?= $activity['registered_count'] ?>/<?= $activity['capacity'] ?>
                            </p>

                            <?php if ($activity['description']): ?>
                                <p class="mb-3"><?= nl2br(htmlspecialchars($activity['description'])) ?></p>
                            <?php endif; ?>

                            <div class="d-flex align-items-center justify-content-between">
                                <div class="text-gradient">
                                    <?= number_format($activity['price']) ?> جنيه
                                </div>
                                <?php if ($activity['registered_count'] >= $activity['capacity']): ?>
                                    <button class="btn btn-sm btn-secondary" disabled>مكتمل</button>
                                <?php else: ?>
                                    <a href="?register=<?= $activity['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        تسجيل
                                    </a>
                                <?php endif; ?>
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
            عرض المرافق
        </a>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>