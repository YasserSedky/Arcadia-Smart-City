<?php
$page_title = 'طلبات الإيجار';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'mall_admin'])) {
    redirect('/dashboard/index.php');
}
$pdo = DB::conn();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = (int)($_POST['request_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    try {
        if ($request_id > 0) {
            if ($action === 'approve') {
                // First, get request details
                $stmt = $pdo->prepare('SELECT * FROM mall_rental_requests WHERE id = ?');
                $stmt->execute([$request_id]);
                $request = $stmt->fetch();

                if ($request) {
                    // Begin transaction
                    $pdo->beginTransaction();

                    try {
                        // Create new tenant
                        $stmt = $pdo->prepare('
                            INSERT INTO mall_tenants (unit_id, name_ar, category_id, phone) 
                            VALUES (?, ?, ?, ?)
                        ');
                        $stmt->execute([
                            $request['unit_id'],
                            $request['business_name'],
                            $request['category_id'],
                            $request['phone']
                        ]);

                        // Update request status
                        $stmt = $pdo->prepare('
                            UPDATE mall_rental_requests 
                            SET status = ?, notes = ? 
                            WHERE id = ?
                        ');
                        $stmt->execute(['approved', $notes, $request_id]);

                        $pdo->commit();
                        $_SESSION['success'] = 'تم قبول طلب الإيجار وإضافة المستأجر بنجاح';
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        throw $e;
                    }
                }
            } elseif ($action === 'reject') {
                $stmt = $pdo->prepare('
                    UPDATE mall_rental_requests 
                    SET status = ?, notes = ? 
                    WHERE id = ?
                ');
                $stmt->execute(['rejected', $notes, $request_id]);
                $_SESSION['success'] = 'تم رفض طلب الإيجار';
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }

    redirect(APP_BASE . '/dashboard/mall/rental_requests.php');
}

// Get all rental requests with unit and category info
$sql = "
    SELECT r.*, u.code as unit_code, u.level, u.area_sqm,
           c.name_ar as category_name
    FROM mall_rental_requests r
    JOIN mall_units u ON u.id = r.unit_id
    LEFT JOIN mall_categories c ON c.id = r.category_id
    ORDER BY r.created_at DESC
";
$requests = $pdo->query($sql)->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">طلبات إيجار المحلات</h2>
        <a href="<?php echo APP_BASE; ?>/dashboard/mall/index.php" class="btn btn-outline-light">رجوع</a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php echo $_SESSION['success'];
            unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php echo $_SESSION['error'];
            unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($requests)): ?>
        <div class="feature-card">
            <p class="mb-0">لا توجد طلبات إيجار حالياً</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($requests as $request): ?>
                <div class="col-md-6">
                    <div class="feature-card h-100">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h5>
                                طلب إيجار وحدة <?php echo htmlspecialchars($request['unit_code']); ?>
                                <small class="d-block text-muted mt-1">
                                    <?php echo date('Y/m/d', strtotime($request['created_at'])); ?>
                                </small>
                            </h5>
                            <span class="badge <?php
                                                echo match ($request['status']) {
                                                    'pending' => 'bg-warning',
                                                    'approved' => 'bg-success',
                                                    'rejected' => 'bg-danger',
                                                    default => 'bg-secondary'
                                                };
                                                ?>">
                                <?php
                                echo match ($request['status']) {
                                    'pending' => 'قيد المراجعة',
                                    'approved' => 'تمت الموافقة',
                                    'rejected' => 'مرفوض',
                                    default => $request['status']
                                };
                                ?>
                            </span>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <strong>اسم مقدم الطلب:</strong>
                                <p class="mb-0"><?php echo htmlspecialchars($request['applicant_name']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <strong>اسم المتجر:</strong>
                                <p class="mb-0"><?php echo htmlspecialchars($request['business_name']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <strong>النشاط التجاري:</strong>
                                <p class="mb-0"><?php echo htmlspecialchars($request['category_name'] ?? 'غير محدد'); ?></p>
                            </div>
                            <div class="col-md-6">
                                <strong>رقم الجوال:</strong>
                                <p class="mb-0"><?php echo htmlspecialchars($request['phone']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <strong>البريد الإلكتروني:</strong>
                                <p class="mb-0"><?php echo htmlspecialchars($request['email']); ?></p>
                            </div>
                            <?php if ($request['commercial_register']): ?>
                                <div class="col-md-6">
                                    <strong>رقم السجل التجاري:</strong>
                                    <p class="mb-0"><?php echo htmlspecialchars($request['commercial_register']); ?></p>
                                </div>
                            <?php endif; ?>
                            <div class="col-12">
                                <strong>تفاصيل النشاط:</strong>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($request['business_description'] ?? '')); ?></p>
                            </div>
                        </div>

                        <?php if ($request['status'] === 'pending'): ?>
                            <hr>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <form method="post" onsubmit="return confirm('هل أنت متأكد من قبول طلب الإيجار؟');">
                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <div class="mb-2">
                                            <label class="form-label">ملاحظات (اختياري)</label>
                                            <textarea class="form-control form-control-sm" name="notes" rows="2"></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-success w-100">قبول الطلب</button>
                                    </form>
                                </div>
                                <div class="col-md-6">
                                    <form method="post" onsubmit="return confirm('هل أنت متأكد من رفض طلب الإيجار؟');">
                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <div class="mb-2">
                                            <label class="form-label">سبب الرفض</label>
                                            <textarea class="form-control form-control-sm" name="notes" rows="2" required></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-danger w-100">رفض الطلب</button>
                                    </form>
                                </div>
                            </div>
                        <?php elseif ($request['notes']): ?>
                            <hr>
                            <div>
                                <strong>ملاحظات:</strong>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($request['notes'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>