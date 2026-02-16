<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../backend/config.php';
require_once __DIR__ . '/../backend/database.php';

$page_title = 'حجز قاعة';
$pdo = Database::getInstance();

// Get venue information
$venue_id = isset($_GET['venue_id']) ? (int)$_GET['venue_id'] : 0;
$venue = null;

if ($venue_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM mall_venues WHERE id = ?");
    $stmt->execute([$venue_id]);
    $venue = $stmt->fetch();
}

if (!$venue) {
    $_SESSION['error'] = 'القاعة غير موجودة';
    redirect(APP_BASE . '/mall/venues.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $title = trim($_POST['title'] ?? '');
        $date = $_POST['date'] ?? '';
        $start_time = $_POST['start_time'] ?? '';
        $end_time = $_POST['end_time'] ?? '';
        $attendees = (int)($_POST['attendees'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        // Validate required fields
        if (!$title || !$date || !$start_time || !$end_time || $attendees <= 0) {
            throw new Exception('جميع الحقول المطلوبة يجب تعبئتها');
        }

        // Validate date is in future
        if (strtotime($date) < strtotime('today')) {
            throw new Exception('يجب اختيار تاريخ مستقبلي');
        }

        // Validate capacity
        if ($attendees > $venue['capacity']) {
            throw new Exception('عدد الحضور يتجاوز سعة القاعة');
        }

        // Create datetime strings for database
        $starts_at = date('Y-m-d H:i:s', strtotime("$date $start_time"));
        $ends_at = date('Y-m-d H:i:s', strtotime("$date $end_time"));

        // Validate end time is after start time
        if ($ends_at <= $starts_at) {
            throw new Exception('وقت النهاية يجب أن يكون بعد وقت البداية');
        }

        // Check for booking conflicts
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM mall_bookings 
            WHERE venue_id = ? 
            AND status = 'scheduled'
            AND (
                (starts_at BETWEEN ? AND ?) OR
                (ends_at BETWEEN ? AND ?) OR
                (starts_at <= ? AND ends_at >= ?)
            )
        ");
        $stmt->execute([$venue_id, $starts_at, $ends_at, $starts_at, $ends_at, $starts_at, $ends_at]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('هذا الوقت محجوز مسبقاً، الرجاء اختيار وقت آخر');
        }

        // Insert booking
        $stmt = $pdo->prepare('
            INSERT INTO mall_bookings 
            (venue_id, user_id, title, starts_at, ends_at, attendees, notes, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $venue_id,
            $_SESSION['user']['id'],
            $title,
            $starts_at,
            $ends_at,
            $attendees,
            $notes,
            'scheduled'
        ]);

        $_SESSION['success'] = 'تم تسجيل الحجز بنجاح';
        redirect(APP_BASE . '/mall/bookings.php');
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

include __DIR__ . '/../includes/header.php';
?>

<main class="container section-padding">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">حجز قاعة</h2>
        <a href="<?php echo APP_BASE; ?>/mall/venues.php" class="btn btn-outline-light">رجوع</a>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php echo $_SESSION['error'];
            unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="feature-card">
                <h5 class="mb-4">تفاصيل الحجز</h5>

                <form method="post" class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label">عنوان الحجز <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="title" required
                            placeholder="مثال: حفل زفاف، اجتماع شركة، مؤتمر">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">التاريخ <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="date" required
                            min="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">وقت البداية <span class="text-danger">*</span></label>
                        <input type="time" class="form-control" name="start_time" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">وقت النهاية <span class="text-danger">*</span></label>
                        <input type="time" class="form-control" name="end_time" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">عدد الحضور <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="attendees" required
                            min="1" max="<?php echo $venue['capacity']; ?>"
                            placeholder="أقصى عدد: <?php echo $venue['capacity']; ?> شخص">
                    </div>

                    <div class="col-12">
                        <label class="form-label">ملاحظات إضافية</label>
                        <textarea class="form-control" name="notes" rows="3"
                            placeholder="أي متطلبات خاصة أو تفاصيل إضافية"></textarea>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-outline-light">تأكيد الحجز</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-md-4">
            <div class="feature-card">
                <h5 class="mb-3">معلومات القاعة</h5>
                <p class="mb-2">
                    <strong>اسم القاعة:</strong>
                    <?php echo htmlspecialchars($venue['name_ar']); ?>
                </p>
                <p class="mb-2">
                    <strong>النوع:</strong>
                    <?php
                    $types = [
                        'cinema' => 'سينما',
                        'games' => 'صالة ألعاب',
                        'events' => 'قاعة فعاليات'
                    ];
                    echo $types[$venue['type']] ?? $venue['type'];
                    ?>
                </p>
                <p class="mb-0">
                    <strong>السعة:</strong>
                    <?php echo htmlspecialchars($venue['capacity']); ?> شخص
                </p>
            </div>

            <div class="feature-card mt-3">
                <h5 class="mb-3">الحجوزات الحالية</h5>
                <div id="bookingsList">
                    <!-- سيتم تحديث هذا القسم عند اختيار التاريخ -->
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    document.querySelector('input[name="date"]').addEventListener('change', function(e) {
        const date = e.target.value;
        const venueId = <?php echo $venue_id; ?>;

        // استرجاع الحجوزات للتاريخ المحدد
        fetch(`<?php echo APP_BASE; ?>/mall/get_bookings.php?venue_id=${venueId}&date=${date}`)
            .then(response => response.json())
            .then(bookings => {
                const bookingsList = document.getElementById('bookingsList');
                if (bookings.length === 0) {
                    bookingsList.innerHTML = '<p class="text-muted">لا توجد حجوزات في هذا اليوم</p>';
                    return;
                }

                let html = '<ul class="list-unstyled mb-0">';
                bookings.forEach(booking => {
                    html += `<li class="mb-2">${booking.start_time} - ${booking.end_time}</li>`;
                });
                html += '</ul>';
                bookingsList.innerHTML = html;
            });
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>