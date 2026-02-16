<?php
$page_title = 'الإبلاغ عن حادث';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$user = $_SESSION['user'];
$pdo = DB::conn();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gate_id = (int)($_POST['gate_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $details = trim($_POST['details'] ?? '');
    $level = $_POST['level'] ?? 'info';

    if ($title === '') {
        $error = 'يرجى إدخال عنوان البلاغ';
    } else {
        $stmt = $pdo->prepare('INSERT INTO security_incidents(gate_id, reported_by_user_id, title, details, level, occurred_at) VALUES(?,?,?,?,?,NOW())');
        try {
            $stmt->execute([$gate_id ?: null, (int)$user['id'], $title, $details ?: null, $level]);
            redirect('/security/index.php?reported=1');
        } catch (PDOException $e) {
            $error = 'حدث خطأ أثناء حفظ البلاغ';
        }
    }
}

// Fetch gates for dropdown
$gates = $pdo->query('SELECT id, code, name_ar FROM gates ORDER BY code')->fetchAll();

// Fetch user's recent reports
$stmt = $pdo->prepare('
    SELECT i.*, g.code as gate_code, g.name_ar as gate_name 
    FROM security_incidents i 
    LEFT JOIN gates g ON g.id = i.gate_id 
    WHERE i.reported_by_user_id = ? 
    ORDER BY i.occurred_at DESC 
    LIMIT 10
');
$stmt->execute([(int)$user['id']]);
$myReports = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<main class="container section-padding">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">الإبلاغ عن حادث</h3>
        <a href="index.php" class="btn btn-outline-primary">رجوع</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="feature-card mb-4">
        <form method="post" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">البوابة (اختياري)</label>
                <select name="gate_id" class="form-select">
                    <option value="">—</option>
                    <?php foreach ($gates as $gate): ?>
                        <option value="<?= (int)$gate['id'] ?>"><?= htmlspecialchars($gate['name_ar']) ?> (<?= htmlspecialchars($gate['code']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">عنوان البلاغ</label>
                <input type="text" name="title" class="form-control" required value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label">المستوى</label>
                <select name="level" class="form-select">
                    <option value="info">معلومة</option>
                    <option value="warning">تحذير</option>
                    <option value="critical">خطير</option>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label">التفاصيل</label>
                <textarea name="details" class="form-control" rows="3"><?= htmlspecialchars($_POST['details'] ?? '') ?></textarea>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-gradient">
                    <i class="bi bi-send-fill"></i>
                    إرسال البلاغ
                </button>
            </div>
        </form>
    </div>

    <?php if (!empty($myReports)): ?>
        <h5 class="mb-3">بلاغاتي السابقة</h5>
        <?php foreach ($myReports as $report): ?>
            <div class="security-incident level-<?= htmlspecialchars($report['level']) ?>">
                <span class="incident-level level-<?= htmlspecialchars($report['level']) ?>">
                    <?php
                    switch ($report['level']) {
                        case 'info':
                            echo 'معلومة';
                            break;
                        case 'warning':
                            echo 'تحذير';
                            break;
                        case 'critical':
                            echo 'خطير';
                            break;
                    }
                    ?>
                </span>
                <div class="incident-time"><?= htmlspecialchars($report['occurred_at']) ?></div>
                <div class="incident-title">
                    <?= htmlspecialchars($report['title']) ?>
                    <?php if ($report['gate_code']): ?>
                        <span class="text-muted">- <?= htmlspecialchars($report['gate_name']) ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($report['details']): ?>
                    <p class="mb-0 small"><?= nl2br(htmlspecialchars($report['details'])) ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>