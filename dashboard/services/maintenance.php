<?php
$page_title = 'طلبات الصيانة';
require_once __DIR__ . '/../includes/auth.php';

$pdo = DB::conn();

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : '';
$priority = isset($_GET['priority']) ? $_GET['priority'] : '';

// Build query based on filters
$query = "
    SELECT 
        mt.*,
        u.first_name,
        u.last_name,
        un.unit_code,
        GROUP_CONCAT(CONCAT(w.first_name, ' ', w.last_name) SEPARATOR ', ') as workers
    FROM maintenance_tickets mt
    LEFT JOIN users u ON u.id = mt.created_by_user_id
    LEFT JOIN units un ON un.id = mt.unit_id
    LEFT JOIN maintenance_assignments ma ON ma.ticket_id = mt.id
    LEFT JOIN users w ON w.id = ma.worker_user_id
    WHERE 1=1
";

$params = [];

if ($status) {
    $query .= " AND mt.status = ?";
    $params[] = $status;
}

if ($priority) {
    $query .= " AND mt.priority = ?";
    $params[] = $priority;
}

$query .= " GROUP BY mt.id ORDER BY mt.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<main class="container section-padding">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">طلبات الصيانة</h3>
        <div>
            <?php if (hasRole('maintenance_worker')): ?>
                <button type="button" class="btn btn-gradient" data-bs-toggle="modal" data-bs-target="#newTicketModal">
                    <i class="bi bi-plus-lg"></i>
                    طلب صيانة جديد
                </button>
            <?php endif; ?>
            <a href="index.php" class="btn btn-outline-primary">
                <i class="bi bi-arrow-right"></i>
                عودة للخدمات
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form class="row g-3" method="GET">
                <div class="col-md-4">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">الكل</option>
                        <option value="open" <?= $status === 'open' ? 'selected' : '' ?>>جديد</option>
                        <option value="assigned" <?= $status === 'assigned' ? 'selected' : '' ?>>تم التعيين</option>
                        <option value="in_progress" <?= $status === 'in_progress' ? 'selected' : '' ?>>قيد التنفيذ</option>
                        <option value="resolved" <?= $status === 'resolved' ? 'selected' : '' ?>>تم الحل</option>
                        <option value="closed" <?= $status === 'closed' ? 'selected' : '' ?>>مغلق</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">الأولوية</label>
                    <select name="priority" class="form-select" onchange="this.form.submit()">
                        <option value="">الكل</option>
                        <option value="low" <?= $priority === 'low' ? 'selected' : '' ?>>منخفض</option>
                        <option value="medium" <?= $priority === 'medium' ? 'selected' : '' ?>>متوسط</option>
                        <option value="high" <?= $priority === 'high' ? 'selected' : '' ?>>عالي</option>
                        <option value="urgent" <?= $priority === 'urgent' ? 'selected' : '' ?>>طارئ</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <a href="maintenance.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg"></i>
                        مسح التصفية
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tickets List -->
    <?php if (empty($tickets)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox display-1"></i>
            <p class="mt-3">لا توجد طلبات صيانة <?= $status || $priority ? 'تطابق معايير البحث' : '' ?></p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($tickets as $ticket): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="ticket-card">
                        <div class="ticket-status <?= $ticket['status'] ?>">
                            <?php
                            echo match ($ticket['status']) {
                                'open' => 'جديد',
                                'assigned' => 'تم التعيين',
                                'in_progress' => 'قيد التنفيذ',
                                'resolved' => 'تم الحل',
                                'closed' => 'مغلق'
                            };
                            ?>
                        </div>

                        <div class="ticket-priority <?= $ticket['priority'] ?>">
                            <?php
                            echo match ($ticket['priority']) {
                                'low' => 'منخفض',
                                'medium' => 'متوسط',
                                'high' => 'عالي',
                                'urgent' => 'طارئ'
                            };
                            ?>
                        </div>

                        <h5 class="ticket-title"><?= htmlspecialchars($ticket['title']) ?></h5>

                        <?php if ($ticket['unit_code']): ?>
                            <div class="ticket-unit">
                                <i class="bi bi-house"></i>
                                <?= htmlspecialchars($ticket['unit_code']) ?>
                            </div>
                        <?php endif; ?>

                        <div class="ticket-details">
                            <?= nl2br(htmlspecialchars($ticket['details'])) ?>
                        </div>

                        <div class="ticket-meta">
                            <div>
                                <i class="bi bi-person"></i>
                                <?= htmlspecialchars($ticket['first_name'] . ' ' . $ticket['last_name']) ?>
                            </div>
                            <div>
                                <i class="bi bi-calendar3"></i>
                                <?= (new DateTime($ticket['created_at']))->format('Y-m-d') ?>
                            </div>
                        </div>

                        <?php if ($ticket['workers']): ?>
                            <div class="ticket-workers">
                                <i class="bi bi-people"></i>
                                <?= htmlspecialchars($ticket['workers']) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (hasRole('maintenance_worker')): ?>
                            <div class="ticket-actions">
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                    onclick="updateTicket(<?= $ticket['id'] ?>)">
                                    تحديث الحالة
                                </button>
                                <?php if ($ticket['status'] === 'open'): ?>
                                    <button type="button" class="btn btn-sm btn-gradient"
                                        onclick="assignWorkers(<?= $ticket['id'] ?>)">
                                        تعيين عمال
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<!-- New Ticket Modal -->
<?php if (hasRole('maintenance_worker')): ?>
    <div class="modal fade" id="newTicketModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="save_maintenance.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">طلب صيانة جديد</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">العنوان</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">التفاصيل</label>
                            <textarea name="details" class="form-control" rows="4" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الأولوية</label>
                            <select name="priority" class="form-select" required>
                                <option value="low">منخفض</option>
                                <option value="medium" selected>متوسط</option>
                                <option value="high">عالي</option>
                                <option value="urgent">طارئ</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الوحدة السكنية (اختياري)</label>
                            <select name="unit_id" class="form-select">
                                <option value="">بدون وحدة</option>
                                <?php
                                $units = $pdo->query("SELECT u.*, b.label as building FROM units u JOIN buildings b ON b.id = u.building_id ORDER BY b.label, u.unit_number")->fetchAll();
                                foreach ($units as $unit):
                                ?>
                                    <option value="<?= $unit['id'] ?>">
                                        <?= htmlspecialchars($unit['building'] . ' - ' . $unit['unit_code']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-gradient">إنشاء الطلب</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="update_maintenance.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">تحديث حالة الطلب</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">الحالة الجديدة</label>
                        <select name="status" class="form-select" required>
                            <option value="in_progress">قيد التنفيذ</option>
                            <option value="resolved">تم الحل</option>
                            <option value="closed">إغلاق الطلب</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                    <input type="hidden" name="ticket_id" id="updateTicketId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-gradient">تحديث الحالة</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Workers Modal -->
<div class="modal fade" id="assignWorkersModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="assign_workers.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">تعيين عمال للطلب</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">اختر العمال</label>
                        <select name="worker_ids[]" class="form-select" multiple required>
                            <?php
                            $workers = $pdo->query("
                                SELECT u.* 
                                FROM users u 
                                JOIN user_roles ur ON ur.user_id = u.id 
                                JOIN roles r ON r.id = ur.role_id
                                WHERE r.name = 'maintenance_worker'
                                ORDER BY u.first_name, u.last_name
                            ")->fetchAll();
                            foreach ($workers as $worker):
                            ?>
                                <option value="<?= $worker['id'] ?>">
                                    <?= htmlspecialchars($worker['first_name'] . ' ' . $worker['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">اضغط Ctrl للاختيار المتعدد</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                    <input type="hidden" name="ticket_id" id="assignTicketId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-gradient">تعيين العمال</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function updateTicket(ticketId) {
        document.getElementById('updateTicketId').value = ticketId;
        new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
    }

    function assignWorkers(ticketId) {
        document.getElementById('assignTicketId').value = ticketId;
        new bootstrap.Modal(document.getElementById('assignWorkersModal')).show();
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>