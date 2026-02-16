<?php
$page_title = 'حجز قاعة/فعالية';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$user = $_SESSION['user'] ?? null;
if (!$user || empty($user['id'])) {
    header('Location: ' . APP_BASE . '/auth/logout.php');
    exit;
}

$pdo = DB::conn();
$venue_id = (int)($_GET['venue_id'] ?? 0);
if ($venue_id < 1) redirect('/conference/index.php');

// fetch venue
$stmt = $pdo->prepare('SELECT * FROM conf_venues WHERE id = ?');
$stmt->execute([$venue_id]);
$venue = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$venue) redirect('/conference/index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $starts_at = trim($_POST['starts_at'] ?? '');
    $ends_at = trim($_POST['ends_at'] ?? '') ?: null;
    $title = trim($_POST['title'] ?? 'طلب حجز');
    $notes = trim($_POST['notes'] ?? '');

    // basic validation
    $error = null;
    if ($starts_at === '') $error = 'يرجى اختيار وقت البداية';
    if (!$error && strtotime($starts_at) === false) $error = 'صيغة وقت غير صحيحة';
    if (!$error && $ends_at && strtotime($ends_at) !== false && strtotime($ends_at) <= strtotime($starts_at)) $error = 'يجب أن يكون وقت النهاية بعد البداية';

    if (!$error) {
        $ins = $pdo->prepare('INSERT INTO conf_bookings(venue_id, title, starts_at, ends_at, notes, user_id) VALUES(?,?,?,?,?,?)');
        try {
            $ins->execute([(int)$venue_id, $title, $starts_at, $ends_at ?: null, $notes ?: null, (int)$user['id']]);
            redirect('/conference/mybookings.php?success=1');
        } catch (PDOException $e) {
            $error = 'حدث خطأ اثناء ارسال الطلب';
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<main class="container section-padding">
    <div class="row">
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($venue['name_ar']) ?></h5>
                    <div class="text-muted"><?= htmlspecialchars($venue['type']) ?> — <?= $venue['capacity'] ? intval($venue['capacity']) . ' شخص' : '—' ?></div>
                </div>
            </div>
            <a href="index.php" class="btn btn-outline-secondary w-100">عودة إلى القاعات</a>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-4">طلب حجز القاعة</h5>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="post" class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">بداية</label>
                            <input type="datetime-local" name="starts_at" class="form-control" required value="<?= htmlspecialchars($_POST['starts_at'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">نهاية (اختياري)</label>
                            <input type="datetime-local" name="ends_at" class="form-control" value="<?= htmlspecialchars($_POST['ends_at'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">عنوان الطلب</label>
                            <input class="form-control" name="title" value="<?= htmlspecialchars($_POST['title'] ?? 'طلب حجز') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">ملاحظات</label>
                            <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12 d-grid">
                            <button class="btn btn-gradient" type="submit">إرسال طلب الحجز</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>