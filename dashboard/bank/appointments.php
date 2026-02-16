<?php
$page_title = 'مواعيد البنك';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'bank_admin'])) redirect('/dashboard/index.php');

$pdo = DB::conn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // action: change status
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if ($id > 0 && in_array($status, ['requested', 'confirmed', 'completed', 'cancelled'], true)) {
        $pdo->prepare('UPDATE bank_appointments SET status = ? WHERE id = ?')->execute([$status, $id]);
    }
    redirect('/dashboard/bank/appointments.php');
}

$rows = $pdo->query('SELECT a.*, u.full_name FROM bank_appointments a JOIN users u ON u.id=a.user_id ORDER BY a.starts_at DESC LIMIT 500')->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">مواعيد العملاء</h3>
        <a href="<?php echo APP_BASE; ?>/dashboard/bank/index.php" class="btn btn-outline-light">رجوع</a>
    </div>

    <div class="table-responsive feature-card">
        <table class="table table-dark table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th>العميل</th>
                    <th>نوع</th>
                    <th>بداية</th>
                    <th>نهاية</th>
                    <th>الحالة</th>
                    <th>الاجراء</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['full_name']) ?></td>
                        <td><?= htmlspecialchars($r['type']) ?></td>
                        <td><?= htmlspecialchars($r['starts_at']) ?></td>
                        <td><?= htmlspecialchars($r['ends_at'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['status']) ?></td>
                        <td>
                            <form method="post" class="d-flex gap-2">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <select name="status" class="form-select form-select-sm me-2">
                                    <option value="requested" <?= $r['status'] === 'requested' ? 'selected' : '' ?>>مطلوب</option>
                                    <option value="confirmed" <?= $r['status'] === 'confirmed' ? 'selected' : '' ?>>مؤكد</option>
                                    <option value="completed" <?= $r['status'] === 'completed' ? 'selected' : '' ?>>مكتمل</option>
                                    <option value="cancelled" <?= $r['status'] === 'cancelled' ? 'selected' : '' ?>>ملغي</option>
                                </select>
                                <button class="btn btn-sm btn-gradient" type="submit">حفظ</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($rows)): ?><tr>
                        <td colspan="6" class="text-center text-muted">لا توجد مواعيد</td>
                    </tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>