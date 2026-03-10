<?php
$page_title = 'دور العبادة';
require_once __DIR__ . '/../../includes/auth.php';
require_login();

$pdo = DB::conn();

// Get worship places count
$stats = $pdo->query('SELECT 
  (SELECT COUNT(*) FROM worship_places WHERE type_id=1) as mosques,
  (SELECT COUNT(*) FROM worship_places WHERE type_id=2) as churches,
  (SELECT COUNT(*) FROM worship_services) as services,
  (SELECT COUNT(*) FROM worship_announcements WHERE ends_at > NOW()) as announcements
')->fetch();

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
    <div class="row g-4">
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-mosque display-4"></i>
                    <h5 class="mt-3">المساجد</h5>
                    <h3 class="fw-bold text-gradient"><?= $stats['mosques'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-building display-4"></i>
                    <h5 class="mt-3">الكنائس</h5>
                    <h3 class="fw-bold text-gradient"><?= $stats['churches'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-calendar-event display-4"></i>
                    <h5 class="mt-3">الأنشطة</h5>
                    <h3 class="fw-bold text-gradient"><?= $stats['services'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-megaphone display-4"></i>
                    <h5 class="mt-3">الإعلانات</h5>
                    <h3 class="fw-bold text-gradient"><?= $stats['announcements'] ?></h3>
                </div>
            </div>
        </div>
    </div>

    <?php if (user_can(['super_admin', 'worship_admin'])): ?>
        <div class="mt-5 text-center">
            <h4 class="mb-4">إدارة دور العبادة</h4>
            <div class="row justify-content-center g-4">
                <div class="col-md-4">
                    <a href="places.php" class="card shadow-sm h-100 text-decoration-none">
                        <div class="card-body text-center">
                            <i class="bi bi-buildings display-4"></i>
                            <h5 class="mt-3 text-dark">دور العبادة</h5>
                            <p class="text-muted">إدارة المساجد والكنائس</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="services.php" class="card shadow-sm h-100 text-decoration-none">
                        <div class="card-body text-center">
                            <i class="bi bi-calendar2-week display-4"></i>
                            <h5 class="mt-3 text-dark">الأنشطة</h5>
                            <p class="text-muted">جدولة وإدارة الأنشطة</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="announcements.php" class="card shadow-sm h-100 text-decoration-none">
                        <div class="card-body text-center">
                            <i class="bi bi-broadcast display-4"></i>
                            <h5 class="mt-3 text-dark">الإعلانات</h5>
                            <p class="text-muted">إدارة إعلانات دور العبادة</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="mt-5">
        <h4 class="mb-4">آخر الإعلانات</h4>
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
    </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>