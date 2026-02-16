<?php
$page_title = 'الأمن والطوارئ';
require_once __DIR__ . '/../includes/auth.php';
$pdo = DB::conn();

// Fetch all gates with their locations
$gates = $pdo->query('SELECT * FROM gates ORDER BY code')->fetchAll();

// Fetch emergency contacts
$contacts = $pdo->query('SELECT * FROM emergency_contacts WHERE is_active = 1')->fetchAll();

// If logged in, fetch recent critical/warning incidents
$user = $_SESSION['user'] ?? null;
$incidents = [];
if ($user) {
    $incidents = $pdo->query("
        SELECT i.*, g.code as gate_code, g.name_ar as gate_name
        FROM security_incidents i 
        LEFT JOIN gates g ON g.id = i.gate_id 
        WHERE i.level IN ('warning', 'critical')
        AND i.occurred_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY i.occurred_at DESC
        LIMIT 10
    ")->fetchAll();
}

include __DIR__ . '/../includes/header.php';
?>

<main class="container section-padding">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">الأمن والطوارئ</h3>
        <?php if (!empty($user)): ?>
            <a href="report.php" class="btn btn-gradient">
                <i class="bi bi-exclamation-triangle-fill"></i>
                الإبلاغ عن حادث
            </a>
        <?php else: ?>
            <a href="/auth/login.php" class="btn btn-outline-primary">تسجيل الدخول للإبلاغ</a>
        <?php endif; ?>
    </div>

    <?php if (!empty($incidents)): ?>
        <div class="mb-4">
            <h5 class="mb-3">أحدث البلاغات الهامة (24 ساعة)</h5>
            <?php foreach ($incidents as $incident): ?>
                <div class="security-incident level-<?= htmlspecialchars($incident['level']) ?>">
                    <span class="incident-level level-<?= htmlspecialchars($incident['level']) ?>">
                        <?= $incident['level'] === 'warning' ? 'تحذير' : 'خطير' ?>
                    </span>
                    <div class="incident-time"><?= htmlspecialchars($incident['occurred_at']) ?></div>
                    <div class="incident-title">
                        <?= htmlspecialchars($incident['title']) ?>
                        <?php if ($incident['gate_code']): ?>
                            <span class="text-muted">- <?= htmlspecialchars($incident['gate_name']) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($incident['details']): ?>
                        <p class="mb-0 small"><?= nl2br(htmlspecialchars($incident['details'])) ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <h5 class="mb-3">البوابات ونقاط الأمن</h5>
    <div class="row g-4">
        <?php foreach ($gates as $gate): ?>
            <div class="col-md-6 col-lg-4" data-aos="fade-up">
                <div class="security-gate">
                    <i class="bi bi-door-closed"></i>
                    <h5><?= htmlspecialchars($gate['name_ar']) ?></h5>
                    <?php if ($gate['location_label']): ?>
                        <p class="text-muted mb-0"><?= htmlspecialchars($gate['location_label']) ?></p>
                    <?php endif; ?>
                    <div class="small mt-2">
                        رمز البوابة: <?= htmlspecialchars($gate['code']) ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($user)): ?>
        <a href="report.php" class="btn btn-gradient report-btn">
            <i class="bi bi-exclamation-triangle-fill"></i>
            الإبلاغ عن حادث
        </a>
    <?php endif; ?>

    <h5 class="mb-3 mt-5">خدمات الطوارئ</h5>
    <div class="row g-4">
        <?php
        $icons = [
            'police' => 'shield-fill-check',
            'ambulance' => 'heart-pulse-fill',
            'fire' => 'fire'
        ];
        $types_ar = [
            'police' => 'الشرطة',
            'ambulance' => 'الإسعاف',
            'fire' => 'الإطفاء'
        ];
        ?>
        <?php foreach ($contacts as $contact): ?>
            <div class="col-md-6 col-lg-4" data-aos="fade-up">
                <div class="emergency-contact <?= htmlspecialchars($contact['type']) ?>">
                    <i class="bi bi-<?= $icons[$contact['type']] ?> emergency-icon"></i>
                    <h5>نقطة <?= $types_ar[$contact['type']] ?></h5>
                    <div class="phone"><?= htmlspecialchars($contact['phone']) ?></div>
                    <?php if ($contact['location_label']): ?>
                        <div class="text-muted mb-2"><?= htmlspecialchars($contact['location_label']) ?></div>
                    <?php endif; ?>
                    <?php if ($contact['working_hours']): ?>
                        <div class="small text-muted">ساعات العمل: <?= htmlspecialchars($contact['working_hours']) ?></div>
                    <?php endif; ?>
                    <?php if ($contact['notes']): ?>
                        <div class="small mt-2"><?= nl2br(htmlspecialchars($contact['notes'])) ?></div>
                    <?php endif; ?>
                    <a href="tel:<?= htmlspecialchars($contact['phone']) ?>" class="btn call-btn">
                        <i class="bi bi-telephone-fill"></i>
                        اتصل الآن
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>