<?php
$page_title = 'الخدمات المصرفية';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pdo = DB::conn();
$user = $_SESSION['user'];

// fetch user's approved (not frozen) bank accounts
$stmt = $pdo->prepare('SELECT * FROM bank_accounts WHERE owner_user_id = ? AND status = ? ORDER BY account_no');
$stmt->execute([(int)$user['id'], 'approved']);
$userAccounts = $stmt->fetchAll();

// Get pending accounts count
$pendingStmt = $pdo->prepare('SELECT COUNT(*) as count FROM bank_accounts WHERE owner_user_id = ? AND status = ?');
$pendingStmt->execute([(int)$user['id'], 'pending']);
$pendingCount = $pendingStmt->fetch()['count'] ?? 0;

include __DIR__ . '/../includes/header.php';
?>

<main class="container section-padding">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">الخدمات المصرفية</h3>
        <div>
            <a href="accounts.php" class="btn btn-outline-primary me-2">حساباتي</a>
            <?php if (!empty($userAccounts)): ?>
                <a href="transfer.php" class="btn btn-outline-success me-2">تحويل</a>
                <a href="certificates.php" class="btn btn-outline-info me-2">الشهادات الاستثمارية</a>
            <?php endif; ?>
            <a href="appointments.php" class="btn btn-outline-secondary">حجوزات البنك</a>
        </div>
    </div>

    <div class="section-head mb-3">
        <p>نقدم خدمات مصرفية لإدارة الحسابات، الدفع والتحويلات داخل النظام. يمكنك فتح حساب وإدارة معاملاته وحجز مواعيد مع موظفي البنك.</p>
    </div>

    <h5 class="mt-4">مرحبًا، <?= htmlspecialchars($user['name'] ?? 'المستخدم') ?></h5>

    <?php if ($pendingCount > 0): ?>
        <div class="alert alert-warning">
            <i class="bi bi-clock-history"></i> لديك <?= $pendingCount ?> حساب(ات) قيد الانتظار للموافقة من الإدارة.
        </div>
    <?php endif; ?>

    <?php if (empty($userAccounts)): ?>
        <div class="alert alert-info">لم يتم ربط أي حساب باسمك بعد أو لا توجد حسابات معتمدة.</div>
        <div class="d-flex gap-2 mb-4">
            <a href="request_account.php" class="btn btn-primary">طلب فتح حساب جديد</a>
            <a href="appointments.php" class="btn btn-outline-secondary">حجز موعد في البنك</a>
        </div>
    <?php else: ?>
        <div class="row g-4 mb-3">
            <?php foreach ($userAccounts as $acc): ?>
                <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($acc['account_name']) ?></h5>
                            <div class="text-muted mb-2"><?= htmlspecialchars($acc['account_no']) ?></div>
                            <div class="fw-semibold mb-2">الرصيد: <?= number_format($acc['balance'], 2) ?> جنيه</div>
                            <div class="d-flex gap-2 flex-wrap">
                                <a href="transactions.php?account_id=<?= (int)$acc['id'] ?>" class="btn btn-sm btn-outline-primary">المعاملات</a>
                                <a href="transfer.php?from_account=<?= (int)$acc['id'] ?>" class="btn btn-sm btn-outline-success">تحويل</a>
                                <a href="appointments.php" class="btn btn-sm btn-outline-secondary">حجز موعد</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <h6 class="mt-3">العمليات الأخيرة على حساباتك</h6>
        <?php
        // show latest transactions across user's accounts (limit 50)
        $ids = array_column($userAccounts, 'id');
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = "SELECT t.*, a.account_no FROM bank_transactions t JOIN bank_accounts a ON a.id = t.account_id WHERE t.account_id IN ($placeholders) ORDER BY t.ts DESC LIMIT 50";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($ids);
            $txs = $stmt->fetchAll();
        } else {
            $txs = [];
        }
        ?>

        <?php if (empty($txs)): ?>
            <div class="alert alert-info">لا توجد معاملات لعرضها.</div>
        <?php else: ?>
            <div class="table-responsive feature-card">
                <table class="table table-dark table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>الوقت</th>
                            <th>الحساب</th>
                            <th>النوع</th>
                            <th>المبلغ</th>
                            <th>الوصف</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($txs as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars($r['ts']) ?></td>
                                <td><?= htmlspecialchars($r['account_no']) ?></td>
                                <td><?= htmlspecialchars($r['kind']) ?></td>
                                <td><?= number_format((float)$r['amount'], 2) ?> جنيه</td>
                                <td><?= htmlspecialchars($r['description'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>