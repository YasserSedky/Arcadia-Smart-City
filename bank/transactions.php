<?php
$page_title = 'المعاملات البنكية';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pdo = DB::conn();
$account_id = (int)($_GET['account_id'] ?? 0);

// Verify account belongs to user or user is admin
$user = $_SESSION['user'] ?? null;
$is_admin = user_can(['super_admin', 'bank_admin']);
if ($account_id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM bank_accounts WHERE id = ?');
    $stmt->execute([$account_id]);
    $acc = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$acc) redirect('/bank/accounts.php');
    if (!$is_admin && (int)$acc['owner_user_id'] !== (int)$user['id']) {
        // unauthorized
        redirect('/bank/accounts.php');
    }
    $rows = $pdo->prepare('SELECT t.*, a.account_no FROM bank_transactions t JOIN bank_accounts a ON a.id=t.account_id WHERE t.account_id = ? ORDER BY t.ts DESC LIMIT 300');
    $rows->execute([$account_id]);
    $rows = $rows->fetchAll();
} else {
    // show summary of recent transactions across user's accounts
    $rows = [];
}

include __DIR__ . '/../includes/header.php';
?>

<main class="container section-padding">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">المعاملات</h3>
        <a href="accounts.php" class="btn btn-outline-primary">رجوع</a>
    </div>

    <?php if (empty($rows)): ?>
        <div class="alert alert-info">لا توجد معاملات لعرضها</div>
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
                    <?php foreach ($rows as $r): ?>
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
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>