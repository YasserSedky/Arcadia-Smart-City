<?php
$page_title = 'إدارة الأشعة والتحاليل';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'hospital_admin'])) {
    redirect('/dashboard/index.php');
}
$pdo = DB::conn();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $scheduled_for = $_POST['scheduled_for'] ?? '';
    $results_text = trim($_POST['results_text'] ?? '');

    if ($order_id > 0) {
        try {
            if ($action === 'schedule' && $scheduled_for) {
                $stmt = $pdo->prepare('UPDATE hospital_test_orders SET status = ?, scheduled_for = ? WHERE id = ?');
                $stmt->execute(['scheduled', $scheduled_for, $order_id]);
                $_SESSION['success'] = 'تم جدولة موعد الفحص';
            } elseif ($action === 'complete') {
                // Handle file upload if provided
                $results_file = null;
                if (isset($_FILES['results_file']) && $_FILES['results_file']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = __DIR__ . '/../../uploads/results/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    $file = $_FILES['results_file'];
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $allowed = ['pdf', 'jpg', 'jpeg', 'png'];

                    if (in_array($ext, $allowed)) {
                        $filename = uniqid() . '_' . date('Ymd') . '.' . $ext;
                        if (move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
                            $results_file = $filename;
                        }
                    }
                }

                $stmt = $pdo->prepare(
                    '
                    UPDATE hospital_test_orders 
                    SET status = ?, completed_at = NOW(), results_text = ?, results_file = ?
                    WHERE id = ?'
                );
                $stmt->execute(['completed', $results_text, $results_file, $order_id]);
                $_SESSION['success'] = 'تم تحديث النتائج بنجاح';
            } elseif ($action === 'cancel') {
                $stmt = $pdo->prepare('UPDATE hospital_test_orders SET status = ? WHERE id = ?');
                $stmt->execute(['cancelled', $order_id]);
                $_SESSION['success'] = 'تم إلغاء الطلب';
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
    }
    redirect(APP_BASE . '/dashboard/hospital/radiology.php');
}

// Get all test orders with patient and test details
$orders = $pdo->query(
    'SELECT o.*, t.name_ar AS test_name, t.category, t.preparation_notes,
            p.full_name AS patient_name, p.phone AS patient_phone,
            c.name_ar AS clinic_name, d.name_ar AS dept_name,
            a.starts_at AS appointment_date
     FROM hospital_test_orders o
     JOIN hospital_test_types t ON t.id = o.test_type_id
     JOIN hospital_patients p ON p.id = o.patient_id
     LEFT JOIN hospital_appointments a ON a.id = o.appointment_id
     LEFT JOIN hospital_clinics c ON c.id = a.clinic_id
     LEFT JOIN hospital_departments d ON d.id = c.department_id
     ORDER BY 
        CASE o.status 
            WHEN "ordered" THEN 1
            WHEN "scheduled" THEN 2
            ELSE 3
        END,
        o.created_at DESC'
)->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">إدارة الأشعة والتحاليل</h2>
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
                    <th>الفحص</th>
                    <th>العيادة</th>
                    <th>موعد الفحص</th>
                    <th>الحالة</th>
                    <th>النتائج</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="9" class="text-center">لا توجد طلبات حالياً</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $i => $order): ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td>
                                <?php echo date('Y/m/d', strtotime($order['created_at'])); ?>
                                <div class="small text-white-50">
                                    <?php echo date('H:i', strtotime($order['created_at'])); ?>
                                </div>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($order['patient_name']); ?>
                                <div class="small text-white-50">
                                    <?php echo htmlspecialchars($order['patient_phone']); ?>
                                </div>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($order['test_name']); ?>
                                <div class="small text-white-50">
                                    <?php echo $order['category'] === 'radiology' ? 'أشعة' : 'تحليل مخبري'; ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($order['clinic_name']): ?>
                                    <?php echo htmlspecialchars($order['dept_name'] . ' - ' . $order['clinic_name']); ?>
                                    <?php if ($order['appointment_date']): ?>
                                        <div class="small text-white-50">
                                            <?php echo date('Y/m/d', strtotime($order['appointment_date'])); ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($order['scheduled_for']): ?>
                                    <?php echo date('Y/m/d H:i', strtotime($order['scheduled_for'])); ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?php
                                                    echo match ($order['status']) {
                                                        'completed' => 'bg-success',
                                                        'scheduled' => 'bg-info',
                                                        'cancelled' => 'bg-danger',
                                                        default => 'bg-warning'
                                                    };
                                                    ?>">
                                    <?php echo match ($order['status']) {
                                        'completed' => 'مكتمل',
                                        'scheduled' => 'مجدول',
                                        'cancelled' => 'ملغي',
                                        default => 'قيد الانتظار'
                                    }; ?>
                                </span>
                            </td>
                            <td style="max-width: 200px;">
                                <?php if ($order['status'] === 'completed'): ?>
                                    <?php if ($order['results_text']): ?>
                                        <small><?php echo nl2br(htmlspecialchars($order['results_text'])); ?></small>
                                    <?php endif; ?>
                                    <?php if ($order['results_file']): ?>
                                        <div class="mt-1">
                                            <a href="<?php echo APP_BASE; ?>/uploads/results/<?php echo htmlspecialchars($order['results_file']); ?>"
                                                class="btn btn-sm btn-outline-light" target="_blank">
                                                <i class="bi bi-download me-1"></i>
                                                عرض المرفق
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($order['status'] === 'ordered'): ?>
                                    <button type="button" class="btn btn-sm btn-success mb-1" data-bs-toggle="modal" data-bs-target="#scheduleModal<?php echo $order['id']; ?>">
                                        جدولة
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger mb-1" data-bs-toggle="modal" data-bs-target="#cancelModal<?php echo $order['id']; ?>">
                                        إلغاء
                                    </button>
                                <?php elseif ($order['status'] === 'scheduled'): ?>
                                    <button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#completeModal<?php echo $order['id']; ?>">
                                        إدخال النتائج
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- Schedule Modal -->
                        <div class="modal fade" id="scheduleModal<?php echo $order['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content bg-dark text-white">
                                    <div class="modal-header">
                                        <h5 class="modal-title">جدولة موعد الفحص</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="post">
                                        <div class="modal-body">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <input type="hidden" name="action" value="schedule">
                                            <p>
                                                جدولة موعد <?php echo $order['category'] === 'radiology' ? 'أشعة' : 'تحليل'; ?>:
                                                <?php echo htmlspecialchars($order['test_name']); ?>
                                            </p>
                                            <?php if ($order['preparation_notes']): ?>
                                                <div class="alert alert-info">
                                                    <strong>تعليمات التحضير:</strong><br>
                                                    <?php echo nl2br(htmlspecialchars($order['preparation_notes'])); ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="mb-3">
                                                <label class="form-label">موعد الفحص</label>
                                                <input type="datetime-local" name="scheduled_for" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                            <button type="submit" class="btn btn-success">تحديد الموعد</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Complete Modal -->
                        <div class="modal fade" id="completeModal<?php echo $order['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content bg-dark text-white">
                                    <div class="modal-header">
                                        <h5 class="modal-title">إدخال نتائج الفحص</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="post" enctype="multipart/form-data">
                                        <div class="modal-body">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <input type="hidden" name="action" value="complete">
                                            <div class="mb-3">
                                                <label class="form-label">نص النتيجة</label>
                                                <textarea name="results_text" class="form-control" rows="3"></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">ملف النتيجة (اختياري)</label>
                                                <input type="file" name="results_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                                <div class="form-text">يمكن رفع ملف PDF أو صورة</div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                            <button type="submit" class="btn btn-primary">حفظ النتائج</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Cancel Modal -->
                        <div class="modal fade" id="cancelModal<?php echo $order['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content bg-dark text-white">
                                    <div class="modal-header">
                                        <h5 class="modal-title">إلغاء الفحص</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="post">
                                        <div class="modal-body">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <input type="hidden" name="action" value="cancel">
                                            <p>هل أنت متأكد من إلغاء هذا الفحص؟</p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">تراجع</button>
                                            <button type="submit" class="btn btn-danger">تأكيد الإلغاء</button>
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