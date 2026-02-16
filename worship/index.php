<?php
$page_title = 'دور العبادة';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pdo = DB::conn();

// Get statistics
$stats = $pdo->query('SELECT 
  (SELECT COUNT(*) FROM worship_places WHERE type_id=1) as mosques,
  (SELECT COUNT(*) FROM worship_places WHERE type_id=2) as churches,
  (SELECT COUNT(*) FROM worship_announcements WHERE ends_at > NOW()) as active_announcements
')->fetch();

// Get worship places
$places = $pdo->query('SELECT p.*, t.name_ar as type_name, t.icon 
  FROM worship_places p 
  JOIN worship_types t ON t.id = p.type_id 
  ORDER BY p.type_id, p.name_ar')->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<main class="container section-padding">
    <!-- Stats -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-mosque display-4"></i>
                    <h5 class="mt-3">المساجد</h5>
                    <h3 class="fw-bold text-gradient"><?= $stats['mosques'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-building display-4"></i>
                    <h5 class="mt-3">الكنائس</h5>
                    <h3 class="fw-bold text-gradient"><?= $stats['churches'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-megaphone display-4"></i>
                    <h5 class="mt-3">إعلانات نشطة</h5>
                    <h3 class="fw-bold text-gradient"><?= $stats['active_announcements'] ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Places -->
    <h4 class="mb-4">دور العبادة</h4>
    <?php if (empty($places)): ?>
        <div class="alert alert-info">لا توجد دور عبادة مضافة حالياً</div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($places as $place): ?>
                <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="flex-shrink-0">
                                    <i class="bi bi-<?= $place['icon'] ?? 'building' ?> display-6 text-gradient"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="card-title mb-1"><?= htmlspecialchars($place['name_ar']) ?></h5>
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
                                    <div class="mt-3">
                                        <h6 class="mb-2">مواقيت الصلاة:</h6>
                                        <div class="row g-2">
                                            <?php foreach ($times as $prayer => $time): ?>
                                                <div class="col-6">
                                                    <small class="text-muted"><?= htmlspecialchars($prayer) ?>:</small>
                                                    <br><?= htmlspecialchars($time) ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <div class="mt-3">
                                <a href="services.php?place_id=<?= $place['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-calendar2-week"></i> الأنشطة والخدمات
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Announcements -->
    <h4 class="mb-4 mt-5">آخر الإعلانات</h4>
    <?php
    $announcements = $pdo->query('SELECT a.*, p.name_ar as place_name 
    FROM worship_announcements a
    JOIN worship_places p ON p.id = a.place_id
    WHERE a.ends_at > NOW()
    ORDER BY a.starts_at ASC LIMIT 5')->fetchAll();

    if (empty($announcements)): ?>
        <div class="alert alert-info">لا توجد إعلانات حالية</div>
        <?php else: foreach ($announcements as $ann): ?>
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($ann['title_ar']) ?></h5>
                    <h6 class="text-muted mb-3"><?= htmlspecialchars($ann['place_name']) ?></h6>
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
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>