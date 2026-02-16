<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../backend/config.php';
require_once __DIR__ . '/../backend/database.php';
include __DIR__ . '/../includes/header.php';

$page_title = 'قاعات وفعاليات المول';
$pdo = Database::getInstance();

// Get all venues
$sql = "SELECT v.*, 
        (SELECT COUNT(*) FROM mall_bookings b WHERE b.venue_id = v.id AND b.status = 'scheduled' AND DATE(b.starts_at) = CURDATE()) as today_bookings
        FROM mall_venues v 
        ORDER BY v.type, v.name_ar";
$stmt = $pdo->query($sql);
$venues = $stmt->fetchAll();

// Group venues by type
$types = [];
foreach ($venues as $venue) {
    $types[$venue['type']][] = $venue;
}

// Type translations
$typeNames = [
    'cinema' => 'دور السينما',
    'games' => 'صالات الألعاب',
    'events' => 'قاعات الفعاليات'
];
?>
<main class="container section-padding">
    <h2 class="mb-4">المرافق الترفيهية والفعاليات</h2>
    <p class="text-white mb-4">استمتع بأوقاتك في صالات السينما وقاعات الألعاب أو احجز قاعة لفعاليتك الخاصة.</p>

    <?php foreach ($types as $type => $venues): ?>
        <div class="mb-5">
            <h3 class="text-white mb-4"><?php echo $typeNames[$type]; ?></h3>
            <div class="row g-4">
                <?php foreach ($venues as $venue): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card h-100">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5><?php echo htmlspecialchars($venue['name_ar']); ?></h5>
                                <?php if ($venue['today_bookings'] > 0): ?>
                                    <span class="badge bg-success"><?php echo $venue['today_bookings']; ?> حجوزات اليوم</span>
                                <?php endif; ?>
                            </div>
                            <p class="mb-3">السعة: <?php echo htmlspecialchars($venue['capacity']); ?> شخص</p>
                            <div class="mt-3 mb-3">
                                <a href="<?php echo APP_BASE; ?>/mall/book_venue.php?venue_id=<?php echo $venue['id']; ?>"
                                    class="btn btn-outline-light w-100">
                                    احجز الآن
                                </a>
                            </div>
                            <?php if ($type == 'cinema'): ?>
                                <p class="mb-3">تجهيزات عرض 4K وصوت Dolby Atmos</p>
                            <?php elseif ($type == 'games' && strpos(strtolower($venue['name_ar']), 'بولينج') !== false): ?>
                                <p class="mb-3">8 مسارات بولينج احترافية</p>
                            <?php elseif ($type == 'games' && strpos(strtolower($venue['name_ar']), 'بلياردو') !== false): ?>
                                <p class="mb-3">10 طاولات بلياردو احترافية</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php';
