<?php
require_once __DIR__ . '/../backend/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
include __DIR__ . '/../includes/header.php';

$pdo = DB::conn();
$items = [];
try {
    $items = $pdo->query('SELECT * FROM pharmacy_items ORDER BY name_ar')->fetchAll();
} catch (PDOException $e) {
    $items = [];
}

// Get patient ID for current user
$user_phone = $_SESSION['user']['phone'] ?? '';
$pStmt = $pdo->prepare('SELECT id FROM hospital_patients WHERE phone = ? LIMIT 1');
$pStmt->execute([$user_phone]);
$pRow = $pStmt->fetch();
$patient_id = $pRow ? (int)$pRow['id'] : null;

// Get user's pharmacy requests
$requests = [];
if ($patient_id) {
    $requests = $pdo->prepare(
        'SELECT r.*, i.name_ar AS item_name, i.unit
         FROM pharmacy_requests r
         JOIN pharmacy_items i ON i.id = r.item_id
         WHERE r.patient_id = ?
         ORDER BY r.created_at DESC
         LIMIT 10'
    );
    $requests->execute([$patient_id]);
    $requests = $requests->fetchAll();
}

// Handle request for an item (patient request)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $patient_id) {
    $item_id = (int)($_POST['item_id'] ?? 0);
    $qty = (int)($_POST['quantity'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');

    if (!$item_id || $qty <= 0) {
        $err = 'اختر الصنف وحدد الكمية الصحيحة.';
    } else {
        try {
            $stmt = $pdo->prepare('INSERT INTO pharmacy_requests (patient_id, item_id, quantity, notes) VALUES (?, ?, ?, ?)');
            $stmt->execute([$patient_id, $item_id, $qty, $notes]);
            $ok = 'تم إرسال طلب الصرف وسيتم مراجعته من قبل الصيدلية';

            // Refresh requests
            $requests = $pdo->prepare(
                'SELECT r.*, i.name_ar AS item_name, i.unit
                 FROM pharmacy_requests r
                 JOIN pharmacy_items i ON i.id = r.item_id
                 WHERE r.patient_id = ?
                 ORDER BY r.created_at DESC
                 LIMIT 10'
            );
            $requests->execute([$patient_id]);
            $requests = $requests->fetchAll();
        } catch (PDOException $e) {
            $err = 'تعذر معالجة الطلب، يرجى المحاولة مرة أخرى.';
        }
    }
}

?>
<main class="container section-padding">
    <h2 class="mb-4">الصيدلية الداخلية</h2>
    <?php if (!empty($err)): ?><div class="alert alert-danger"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>
    <?php if (!empty($ok)): ?><div class="alert alert-success"><?php echo htmlspecialchars($ok); ?></div><?php endif; ?>

    <?php if (!empty($requests)): ?>
        <h3 class="mb-3">طلباتي السابقة</h3>
        <div class="table-responsive feature-card mb-5">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>الصنف</th>
                        <th>الكمية</th>
                        <th>الحالة</th>
                        <th>ملاحظات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $req): ?>
                        <tr>
                            <td><?php echo date('Y/m/d H:i', strtotime($req['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($req['item_name']); ?></td>
                            <td><?php echo (int)$req['quantity'] . ' ' . htmlspecialchars($req['unit']); ?></td>
                            <td>
                                <span class="badge <?php
                                                    echo match ($req['status']) {
                                                        'approved' => 'bg-success',
                                                        'rejected' => 'bg-danger',
                                                        'dispensed' => 'bg-info',
                                                        default => 'bg-warning'
                                                    };
                                                    ?>">
                                    <?php echo match ($req['status']) {
                                        'approved' => 'تمت الموافقة',
                                        'rejected' => 'مرفوض',
                                        'dispensed' => 'تم الصرف',
                                        default => 'قيد المراجعة'
                                    }; ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($req['notes'])): ?>
                                    <small><?php echo htmlspecialchars($req['notes']); ?></small>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <h3 class="mb-3">طلب صرف دواء</h3>
    <div class="row g-4">
        <?php if (empty($items)): ?>
            <div class="col-12">
                <div class="feature-card">
                    <p class="mb-0">لا توجد أصناف متاحة حالياً</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($items as $it): ?>
                <div class="col-md-6" data-aos="fade-up">
                    <div class="feature-card h-100">
                        <h5><?php echo htmlspecialchars($it['name_ar']); ?></h5>
                        <p class="mb-1">الكمية المتوفرة: <?php echo (int)$it['quantity']; ?></p>
                        <p class="mb-2">وحدة القياس: <?php echo htmlspecialchars($it['unit']); ?></p>
                        <form method="post" action="<?php echo APP_BASE; ?>/hospital/pharmacy.php">
                            <input type="hidden" name="item_id" value="<?php echo (int)$it['id']; ?>">
                            <div class="mb-3">
                                <label class="form-label">الكمية المطلوبة</label>
                                <input type="number" name="quantity" value="1" min="1" class="form-control" style="max-width:120px;" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ملاحظات (اختياري)</label>
                                <textarea name="notes" class="form-control" rows="2"></textarea>
                            </div>
                            <button class="btn btn-gradient" type="submit">طلب صرف</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php';
