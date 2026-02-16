<?php
$page_title = 'طلبات الصيدلية';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'hospital_admin'])) {
    redirect('/dashboard/index.php');
}
$pdo = DB::conn();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = (int)($_POST['request_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    if ($request_id > 0) {
        try {
            if ($action === 'approve') {
                $stmt = $pdo->prepare('UPDATE pharmacy_requests SET status = ?, notes = ?, processed_at = NOW() WHERE id = ?');
                $stmt->execute(['approved', $notes, $request_id]);
                $_SESSION['success'] = 'تمت الموافقة على الطلب';
            } elseif ($action === 'reject') {
                $stmt = $pdo->prepare('UPDATE pharmacy_requests SET status = ?, notes = ?, processed_at = NOW() WHERE id = ?');
                $stmt->execute(['rejected', $notes, $request_id]);
                $_SESSION['success'] = 'تم رفض الطلب';
            } elseif ($action === 'dispense') {
                // Start transaction to update both request and inventory
                $pdo->beginTransaction();

                // Get request details
                $stmt = $pdo->prepare('SELECT item_id, quantity FROM pharmacy_requests WHERE id = ? AND status = ?');
                $stmt->execute([$request_id, 'approved']);
                $request = $stmt->fetch();

                if ($request) {
                    // Check inventory
                    $stmt = $pdo->prepare('SELECT quantity FROM pharmacy_items WHERE id = ?');
                    $stmt->execute([$request['item_id']]);
                    $item = $stmt->fetch();

                    if ($item && $item['quantity'] >= $request['quantity']) {
                        // Update inventory
                        $stmt = $pdo->prepare('UPDATE pharmacy_items SET quantity = quantity - ? WHERE id = ?');
                        $stmt->execute([$request['quantity'], $request['item_id']]);

                        // Record transaction
                        $stmt = $pdo->prepare('INSERT INTO pharmacy_transactions(item_id, ts, type, amount, note) VALUES(?,NOW(),?,?,?)');
                        $stmt->execute([$request['item_id'], 'out', $request['quantity'], 'Dispensed for request #' . $request_id]);

                        // Update request status
                        $stmt = $pdo->prepare('UPDATE pharmacy_requests SET status = ?, notes = ?, processed_at = NOW() WHERE id = ?');
                        $stmt->execute(['dispensed', $notes, $request_id]);

                        $pdo->commit();
                        $_SESSION['success'] = 'تم صرف الدواء وتحديث المخزون';
                    } else {
                        throw new Exception('الكمية المطلوبة غير متوفرة في المخزون');
                    }
                }
            }
        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $_SESSION['error'] = $e->getMessage();
        }
    }
    redirect(APP_BASE . '/dashboard/hospital/pharmacy_requests.php');
}

// Get all pending requests with patient and item details
$requests = $pdo->query(
    'SELECT r.*, i.name_ar AS item_name, i.unit, i.quantity AS stock_qty,
            p.full_name AS patient_name, p.phone AS patient_phone
     FROM pharmacy_requests r
     JOIN pharmacy_items i ON i.id = r.item_id
     JOIN hospital_patients p ON p.id = r.patient_id
     ORDER BY 
        CASE r.status 
            WHEN "pending" THEN 1
            WHEN "approved" THEN 2
            ELSE 3
        END,
        r.created_at DESC'
)->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">إدارة طلبات الصيدلية</h2>
        <a href="<?php echo APP_BASE; ?>/dashboard/hospital/index.php" class="btn btn-outline-light">رجوع</a>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success'];
                                            unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error'];
                                        unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="table-responsive feature-card">
        <table class="table table-dark table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>التاريخ</th>
                    <th>المريض</th>
                    <th>الصنف</th>
                    <th>الكمية</th>
                    <th>المخزون</th>
                    <th>الحالة</th>
                    <th>ملاحظات</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="9" class="text-center">لا توجد طلبات حالياً</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($requests as $i => $req): ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td>
                                <?php echo date('Y/m/d', strtotime($req['created_at'])); ?>
                                <div class="small text-white-50">
                                    <?php echo date('H:i', strtotime($req['created_at'])); ?>
                                </div>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($req['patient_name']); ?>
                                <div class="small text-white-50">
                                    <?php echo htmlspecialchars($req['patient_phone']); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($req['item_name']); ?></td>
                            <td><?php echo (int)$req['quantity'] . ' ' . htmlspecialchars($req['unit']); ?></td>
                            <td>
                                <?php if ((int)$req['stock_qty'] < (int)$req['quantity']): ?>
                                    <span class="text-danger">
                                        <?php echo (int)$req['stock_qty']; ?> <?php echo htmlspecialchars($req['unit']); ?>
                                    </span>
                                <?php else: ?>
                                    <?php echo (int)$req['stock_qty']; ?> <?php echo htmlspecialchars($req['unit']); ?>
                                <?php endif; ?>
                            </td>
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
                            <td style="max-width: 200px;">
                                <?php if (!empty($req['notes'])): ?>
                                    <small><?php echo nl2br(htmlspecialchars($req['notes'])); ?></small>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($req['status'] === 'pending'): ?>
                                    <button type="button" class="btn btn-sm btn-success mb-1" data-bs-toggle="modal" data-bs-target="#approveModal<?php echo $req['id']; ?>">
                                        موافقة
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger mb-1" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $req['id']; ?>">
                                        رفض
                                    </button>
                                <?php elseif ($req['status'] === 'approved'): ?>
                                    <button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#dispenseModal<?php echo $req['id']; ?>">
                                        صرف
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- Approve Modal -->
                        <div class="modal fade" id="approveModal<?php echo $req['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content bg-dark text-white">
                                    <div class="modal-header">
                                        <h5 class="modal-title">موافقة على طلب صرف دواء</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="post">
                                        <div class="modal-body">
                                            <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <p>
                                                هل تريد الموافقة على صرف
                                                <?php echo (int)$req['quantity']; ?> <?php echo htmlspecialchars($req['unit']); ?>
                                                من <?php echo htmlspecialchars($req['item_name']); ?>؟
                                            </p>
                                            <div class="mb-3">
                                                <label class="form-label">ملاحظات (اختياري)</label>
                                                <textarea name="notes" class="form-control" rows="2"></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                            <button type="submit" class="btn btn-success">موافقة</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Reject Modal -->
                        <div class="modal fade" id="rejectModal<?php echo $req['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content bg-dark text-white">
                                    <div class="modal-header">
                                        <h5 class="modal-title">رفض طلب صرف دواء</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="post">
                                        <div class="modal-body">
                                            <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <p>هل أنت متأكد من رفض هذا الطلب؟</p>
                                            <div class="mb-3">
                                                <label class="form-label">سبب الرفض</label>
                                                <textarea name="notes" class="form-control" rows="2" required></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                            <button type="submit" class="btn btn-danger">رفض</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Dispense Modal -->
                        <div class="modal fade" id="dispenseModal<?php echo $req['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content bg-dark text-white">
                                    <div class="modal-header">
                                        <h5 class="modal-title">صرف الدواء</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="post">
                                        <div class="modal-body">
                                            <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                            <input type="hidden" name="action" value="dispense">
                                            <p>
                                                هل تريد تأكيد صرف
                                                <?php echo (int)$req['quantity']; ?> <?php echo htmlspecialchars($req['unit']); ?>
                                                من <?php echo htmlspecialchars($req['item_name']); ?>؟
                                            </p>
                                            <?php if ((int)$req['stock_qty'] < (int)$req['quantity']): ?>
                                                <div class="alert alert-danger">
                                                    تنبيه: الكمية المطلوبة (<?php echo (int)$req['quantity']; ?>)
                                                    أكبر من المخزون المتاح (<?php echo (int)$req['stock_qty']; ?>)
                                                </div>
                                            <?php endif; ?>
                                            <div class="mb-3">
                                                <label class="form-label">ملاحظات الصرف (اختياري)</label>
                                                <textarea name="notes" class="form-control" rows="2"></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                            <button type="submit" class="btn btn-primary" <?php echo ((int)$req['stock_qty'] < (int)$req['quantity']) ? 'disabled' : ''; ?>>
                                                تأكيد الصرف
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>