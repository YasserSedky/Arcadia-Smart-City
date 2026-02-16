<?php
$page_title = 'حجز المرافق';
require_once __DIR__ . '/../includes/auth.php';
require_login();

// Get current logged in user from session (require_login ensures session exists)
$user = $_SESSION['user'] ?? null;
if (!$user || empty($user['id'])) {
    // If user info is missing, force logout to clear session and redirect to login
    header('Location: ' . APP_BASE . '/auth/logout.php');
    exit;
}

$pdo = DB::conn();

$facility_id = (int)($_GET['facility_id'] ?? 0);
if ($facility_id < 1) {
    redirect('/sports/index.php');
}

// Get facility details
$facility = $pdo->prepare('SELECT f.*, t.name_ar as type_name, t.icon 
    FROM sports_facilities f 
    JOIN sports_facility_types t ON t.id = f.type_id 
    WHERE f.id = ? AND f.status = "available"');
$facility->execute([$facility_id]);
$facility = $facility->fetch();

if (!$facility) {
    redirect('/sports/index.php');
}

// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $starts_at = $_POST['date'] . ' ' . $_POST['time'];
    $duration = (int)($_POST['duration'] ?? 1);
    $notes = trim($_POST['notes'] ?? '');

    if (strtotime($starts_at) > time()) {
        $ends_at = date('Y-m-d H:i:s', strtotime($starts_at) + ($duration * 3600));

        // Check for conflicts
        $check = $pdo->prepare('SELECT 1 FROM sports_bookings 
            WHERE facility_id = ? 
            AND status = "confirmed"
            AND (
                (starts_at BETWEEN ? AND ?) 
                OR (ends_at BETWEEN ? AND ?)
                OR (starts_at <= ? AND ends_at >= ?)
            )');
        $check->execute([$facility_id, $starts_at, $ends_at, $starts_at, $ends_at, $starts_at, $ends_at]);

        if (!$check->fetch()) {
            $stmt = $pdo->prepare('INSERT INTO sports_bookings(facility_id, user_id, starts_at, ends_at, notes) 
                VALUES(?,?,?,?,?)');
            try {
                $stmt->execute([$facility_id, $user['id'], $starts_at, $ends_at, $notes ?: null]);
                redirect('/sports/mybookings.php?success=1');
            } catch (PDOException $e) {
                $error = 'حدث خطأ في حفظ الحجز';
            }
        } else {
            $error = 'عذراً، هذا الوقت محجوز مسبقاً';
        }
    } else {
        $error = 'يجب اختيار وقت مستقبلي للحجز';
    }
}

include __DIR__ . '/../includes/header.php';
?>

<main class="container section-padding">
    <div class="row">
        <div class="col-lg-4">
            <!-- Facility Details -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            <i class="bi bi-<?= $facility['icon'] ?? 'grid' ?> display-6 text-gradient"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h5 class="card-title mb-1"><?= htmlspecialchars($facility['name_ar']) ?></h5>
                            <div class="text-muted"><?= htmlspecialchars($facility['type_name']) ?></div>
                        </div>
                    </div>

                    <p class="mb-2">
                        <i class="bi bi-people me-2"></i>
                        السعة: <?= $facility['capacity'] ?> شخص
                    </p>

                    <p class="mb-2">
                        <i class="bi bi-cash me-2"></i>
                        السعر: <?= number_format($facility['price_per_hour']) ?> جنيه/ساعة
                    </p>

                    <?php if ($facility['description']): ?>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($facility['description'])) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <a href="index.php" class="btn btn-outline-secondary w-100">
                <i class="bi bi-arrow-right me-2"></i>
                عودة للمرافق
            </a>
        </div>

        <div class="col-lg-8">
            <!-- Booking Form -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-4">حجز المرفق</h5>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <form method="post" class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">التاريخ</label>
                            <input type="date" name="date" class="form-control" required
                                min="<?= date('Y-m-d') ?>"
                                max="<?= date('Y-m-d', strtotime('+2 months')) ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">وقت البداية</label>
                            <input type="time" name="time" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">مدة الحجز (بالساعات)</label>
                            <select name="duration" class="form-select" required>
                                <?php for ($i = 1; $i <= 3; $i++): ?>
                                    <option value="<?= $i ?>"><?= $i ?> ساعة</option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">التكلفة المتوقعة</label>
                            <div class="form-control-plaintext fw-bold text-gradient expected-cost">
                                <?= number_format($facility['price_per_hour']) ?> جنيه
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">ملاحظات</label>
                            <textarea name="notes" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-gradient">
                                <i class="bi bi-check-lg me-2"></i>
                                تأكيد الحجز
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    (function() {
        const durationEl = document.querySelector('select[name="duration"]');
        const expectedEl = document.querySelector('.expected-cost');
        const pricePerHour = <?= json_encode((float)$facility['price_per_hour']) ?>;

        function updateCost() {
            const duration = parseInt(durationEl.value || 1, 10);
            const totalCost = Math.max(0, duration * pricePerHour);
            expectedEl.textContent = totalCost.toLocaleString() + ' جنيه';
        }

        durationEl.addEventListener('change', updateCost);
        // initialize
        updateCost();
    })();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>