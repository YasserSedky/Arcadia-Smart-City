<?php
$page_title = 'حساباتي';
require_once __DIR__ . '/../includes/auth.php';
$user = require_login();

$pdo = DB::conn();
// fetch user's approved accounts only
$rows = $pdo->prepare('SELECT * FROM bank_accounts WHERE owner_user_id = ? AND status = ? ORDER BY account_no');
$rows->execute([(int)$_SESSION['user']['id'], 'approved']);
$accounts = $rows->fetchAll();

// Get pending accounts count
$pendingStmt = $pdo->prepare('SELECT COUNT(*) as count FROM bank_accounts WHERE owner_user_id = ? AND status = ?');
$pendingStmt->execute([(int)$_SESSION['user']['id'], 'pending']);
$pendingCount = $pendingStmt->fetch()['count'] ?? 0;

include __DIR__ . '/../includes/header.php';
?>

<main class="container section-padding">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">حساباتي</h3>
        <a href="index.php" class="btn btn-outline-primary">رجوع</a>
    </div>

    <?php if (isset($_GET['pending'])): ?>
        <div class="alert alert-warning">
            <i class="bi bi-clock-history"></i> تم إرسال طلب فتح الحساب بنجاح. سيتم مراجعته والموافقة عليه من قبل الإدارة قريباً.
        </div>
    <?php endif; ?>

    <?php if ($pendingCount > 0): ?>
        <div class="alert alert-warning">
            <i class="bi bi-clock-history"></i> لديك <?= $pendingCount ?> حساب(ات) قيد الانتظار للموافقة من الإدارة.
        </div>
    <?php endif; ?>

    <?php if (empty($accounts)): ?>
        <div class="alert alert-info">لم يتم ربط أي حساب باسمك بعد أو لا توجد حسابات معتمدة.</div>
        <div class="mt-3">
            <a href="request_account.php" class="btn btn-gradient">طلب فتح حساب جديد</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($accounts as $acc): ?>
                <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($acc['account_name']) ?></h5>
                            <div class="text-muted mb-2"><?= htmlspecialchars($acc['account_no']) ?></div>
                            <div class="fw-semibold mb-2">الرصيد: <?= number_format($acc['balance'], 2) ?> جنيه</div>
                            <a href="transactions.php?account_id=<?= (int)$acc['id'] ?>" class="btn btn-sm btn-outline-primary">عرض المعاملات</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>