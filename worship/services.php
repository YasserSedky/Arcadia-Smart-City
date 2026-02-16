<?php
$page_title = 'الأنشطة والخدمات';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pdo = DB::conn();

$place_id = (int)($_GET['place_id'] ?? 0);
if ($place_id < 1) {
    redirect('/worship/index.php');
}

// Get place details
$place = $pdo->prepare('SELECT p.*, t.name_ar as type_name, t.icon 
    FROM worship_places p 
    JOIN worship_types t ON t.id = p.type_id 
    WHERE p.id = ?');
$place->execute([$place_id]);
$place = $place->fetch();

if (!$place) {
    redirect('/worship/index.php');
}

// Get services
$services = $pdo->prepare('SELECT * FROM worship_services WHERE place_id = ? ORDER BY name_ar');
$services->execute([$place_id]);
$services = $services->fetchAll();

// Get announcements
$announcements = $pdo->prepare('SELECT * FROM worship_announcements 
    WHERE place_id = ? AND ends_at > NOW() 
    ORDER BY starts_at ASC');
$announcements->execute([$place_id]);
$announcements = $announcements->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<main class="container section-padding">
    <!-- Place Details -->
    <div class="card shadow-sm mb-5">
        <div class="card-body">
            <div class="d-flex align-items-center mb-3">
                <div class="flex-shrink-0">
                    <i class="bi bi-<?= $place['icon'] ?? 'building' ?> display-5 text-gradient"></i>
                </div>
                <div class="flex-grow-1 ms-3">
                    <h3 class="card-title mb-1"><?= htmlspecialchars($place['name_ar']) ?></h3>
                    <div class="text-muted"><?= htmlspecialchars($place['type_name']) ?></div>
                </div>
            </div>

            <?php if ($place['capacity']): ?>
                <p class="mb-2">
                    <i class="bi bi-people me-2"></i>
                    السعة: <?= number_format($place['capacity']) ?> شخص
                </p>
            <?php endif; ?>

            <?php if ($place['location']): ?>
                <p class="mb-2">
                    <i class="bi bi-geo-alt me-2"></i>
                    <?= htmlspecialchars($place['location']) ?>
                </p>
            <?php endif; ?>

            <?php if ($place['description']): ?>
                <p class="mb-2"><?= nl2br(htmlspecialchars($place['description'])) ?></p>
            <?php endif; ?>

            <?php if ($place['prayer_times']):
                $times = json_decode($place['prayer_times'], true);
                if ($times): ?>
                    <div class="mt-4">
                        <h5 class="mb-3">مواقيت الصلاة:</h5>
                        <div class="row g-3">
                            <?php foreach ($times as $prayer => $time): ?>
                                <div class="col-md-4 col-6">
                                    <div class="card bg-light">
                                        <div class="card-body py-2">
                                            <div class="text-muted"><?= htmlspecialchars($prayer) ?></div>
                                            <div class="fw-bold"><?= htmlspecialchars($time) ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Services -->
    <h4 class="mb-4">الأنشطة والخدمات</h4>
    <?php if (empty($services)): ?>
        <div class="alert alert-info">لا توجد أنشطة مضافة حالياً</div>
    <?php else: ?>
        <div class="row g-4 mb-5">
            <?php foreach ($services as $service): ?>
                <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($service['name_ar']) ?></h5>
                            <?php if ($service['schedule']): ?>
                                <p class="mb-2">
                                    <i class="bi bi-calendar3 me-2"></i>
                                    <?= htmlspecialchars($service['schedule']) ?>
                                </p>
                            <?php endif; ?>
                            <?php if ($service['description']): ?>
                                <p class="mb-0"><?= nl2br(htmlspecialchars($service['description'])) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Announcements -->
    <h4 class="mb-4">الإعلانات الحالية</h4>
    <?php if (empty($announcements)): ?>
        <div class="alert alert-info">لا توجد إعلانات حالية</div>
        <?php else: foreach ($announcements as $ann): ?>
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($ann['title_ar']) ?></h5>
                    <p class="card-text"><?= nl2br(htmlspecialchars($ann['content'])) ?></p>
                    <small class="text-muted">
                        من <?= (new DateTime($ann['starts_at']))->format('Y-m-d H:i') ?>
                        <?php if ($ann['ends_at']): ?>
                            إلى <?= (new DateTime($ann['ends_at']))->format('Y-m-d H:i') ?>
                        <?php endif; ?>
                    </small>
                </div>
            </div>
    <?php endforeach;
    endif; ?>

    <div class="mt-4">
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-right me-1"></i>
            عودة لقائمة دور العبادة
        </a>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>