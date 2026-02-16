<?php
$page_title = 'وحدتي السكنية';
require_once __DIR__ . '/../includes/auth.php';

// Check if user is logged in and has a unit assigned
if (empty($_SESSION['user']) || empty($_SESSION['user']['unit_id'])) {
    header('Location: index.php');
    exit;
}

$pdo = DB::conn();

// Get unit details with building info
$stmt = $pdo->prepare("
    SELECT u.*, b.type as building_type, b.label as building_label,
           (SELECT COUNT(*) FROM maintenance_tickets mt WHERE mt.unit_id = u.id AND mt.status != 'closed') as open_tickets
    FROM units u 
    JOIN buildings b ON b.id = u.building_id
    WHERE u.id = ?
");
$stmt->execute([(int)$_SESSION['user']['unit_id']]);
$unit = $stmt->fetch();

if (!$unit) {
    header('Location: index.php');
    exit;
}

// Get maintenance history
$tickets = $pdo->prepare("
    SELECT mt.*, 
           CASE 
               WHEN mt.status = 'new' THEN 'جديد'
               WHEN mt.status = 'in_progress' THEN 'قيد التنفيذ'
               WHEN mt.status = 'completed' THEN 'مكتمل'
               WHEN mt.status = 'closed' THEN 'مغلق'
           END as status_ar,
           CASE
               WHEN mt.priority = 'low' THEN 'منخفض'
               WHEN mt.priority = 'medium' THEN 'متوسط'
               WHEN mt.priority = 'high' THEN 'عالي'
               WHEN mt.priority = 'urgent' THEN 'طارئ'
           END as priority_ar
    FROM maintenance_tickets mt
    WHERE mt.unit_id = ?
    ORDER BY mt.created_at DESC
");
$tickets->execute([(int)$unit['id']]);
$maintenance_history = $tickets->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<main class="container section-padding">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">وحدتي السكنية</h3>
        <a href="index.php" class="btn btn-outline-primary">
            <i class="bi bi-arrow-right"></i>
            عودة للمنطقة السكنية
        </a>
    </div>

    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="residential-details">
                <div class="text-center mb-4">
                    <?php if ($unit['building_type'] === 'apartment_block'): ?>
                        <i class="bi bi-building display-1"></i>
                    <?php else: ?>
                        <i class="bi bi-house display-1"></i>
                    <?php endif; ?>
                </div>

                <div class="info-group">
                    <label>نوع الوحدة</label>
                    <div>
                        <?= $unit['building_type'] === 'apartment_block' ? 'شقة' : 'فيلا' ?>
                    </div>
                </div>

                <div class="info-group">
                    <label>رقم المبنى</label>
                    <div><?= htmlspecialchars($unit['building_label']) ?></div>
                </div>

                <div class="info-group">
                    <label>رقم الوحدة</label>
                    <div><?= htmlspecialchars($unit['unit_code']) ?></div>
                </div>

                <div class="info-group">
                    <label>المساحة</label>
                    <div><?= htmlspecialchars($unit['area']) ?> متر مربع</div>
                </div>

                <?php if ($unit['building_type'] === 'apartment_block'): ?>
                    <div class="info-group">
                        <label>الطابق</label>
                        <div><?= htmlspecialchars($unit['floor']) ?></div>
                    </div>
                <?php endif; ?>

                <div class="mt-4">
                    <button type="button" class="btn btn-gradient w-100" data-bs-toggle="modal" data-bs-target="#newTicketModal">
                        <i class="bi bi-tools"></i>
                        طلب صيانة جديد
                    </button>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        طلبات الصيانة
                        <?php if ($unit['open_tickets'] > 0): ?>
                            <span class="badge bg-warning ms-2">
                                <?= $unit['open_tickets'] ?> طلب مفتوح
                            </span>
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($maintenance_history)): ?>
                        <div class="text-center p-4 text-muted">
                            <i class="bi bi-tools display-4"></i>
                            <p class="mt-3">لا توجد طلبات صيانة سابقة</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>التاريخ</th>
                                        <th>الوصف</th>
                                        <th>الأولوية</th>
                                        <th>الحالة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($maintenance_history as $ticket): ?>
                                        <tr>
                                            <td>
                                                <?= (new DateTime($ticket['created_at']))->format('Y-m-d') ?>
                                            </td>
                                            <td><?= htmlspecialchars($ticket['description']) ?></td>
                                            <td>
                                                <span class="badge bg-<?php
                                                                        echo match ($ticket['priority']) {
                                                                            'low' => 'success',
                                                                            'medium' => 'info',
                                                                            'high' => 'warning',
                                                                            'urgent' => 'danger'
                                                                        };
                                                                        ?>">
                                                    <?= $ticket['priority_ar'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php
                                                                        echo match ($ticket['status']) {
                                                                            'new' => 'secondary',
                                                                            'in_progress' => 'info',
                                                                            'completed' => 'success',
                                                                            'closed' => 'dark'
                                                                        };
                                                                        ?>">
                                                    <?= $ticket['status_ar'] ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- New Maintenance Ticket Modal -->
<div class="modal fade" id="newTicketModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="save_ticket.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">طلب صيانة جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">الوصف</label>
                        <textarea name="description" class="form-control" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الأولوية</label>
                        <select name="priority" class="form-select" required>
                            <option value="low">منخفض</option>
                            <option value="medium">متوسط</option>
                            <option value="high">عالي</option>
                            <option value="urgent">طارئ</option>
                        </select>
                    </div>
                    <input type="hidden" name="unit_id" value="<?= $unit['id'] ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-gradient">إرسال الطلب</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>