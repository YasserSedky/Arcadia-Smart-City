<?php
$page_title = 'التحويل بين الحسابات';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pdo = DB::conn();
$user = $_SESSION['user'];
$error = null;
$success = null;

// Get user's approved accounts
$userAccounts = $pdo->prepare('SELECT * FROM bank_accounts WHERE owner_user_id = ? AND status = ? ORDER BY account_no');
$userAccounts->execute([(int)$user['id'], 'approved']);
$userAccounts = $userAccounts->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $from_account_id = (int)($_POST['from_account_id'] ?? 0);
    $to_account_no = trim($_POST['to_account_no'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);
    $description = trim($_POST['description'] ?? 'تحويل بين الحسابات');
    
    if ($from_account_id <= 0 || $to_account_no === '' || $amount <= 0) {
        $error = 'يرجى إدخال جميع البيانات المطلوبة';
    } else {
        // Verify from account belongs to user
        $fromAccount = $pdo->prepare('SELECT * FROM bank_accounts WHERE id = ? AND owner_user_id = ? AND status = ?');
        $fromAccount->execute([$from_account_id, (int)$user['id'], 'approved']);
        $fromAcc = $fromAccount->fetch();
        
        if (!$fromAcc) {
            $error = 'الحساب المصدر غير صحيح أو غير معتمد';
        } elseif ($fromAcc['balance'] < $amount) {
            $error = 'الرصيد غير كافي لإتمام التحويل';
        } else {
            // Get to account
            $toAccount = $pdo->prepare('SELECT * FROM bank_accounts WHERE account_no = ? AND status = ?');
            $toAccount->execute([$to_account_no, 'approved']);
            $toAcc = $toAccount->fetch();
            
            if (!$toAcc) {
                $error = 'الحساب الوجهة غير موجود أو غير معتمد';
            } elseif ($toAcc['id'] === $from_account_id) {
                $error = 'لا يمكن التحويل لنفس الحساب';
            } else {
                // Perform transfer
                $pdo->beginTransaction();
                try {
                    // Deduct from source account
                    $pdo->prepare('INSERT INTO bank_transactions(account_id, kind, amount, description) VALUES(?,?,?,?)')
                        ->execute([$from_account_id, 'transfer_out', $amount, $description . ' - إلى: ' . $to_account_no]);
                    $pdo->prepare('UPDATE bank_accounts SET balance = balance - ? WHERE id = ?')
                        ->execute([$amount, $from_account_id]);
                    
                    // Add to destination account
                    $pdo->prepare('INSERT INTO bank_transactions(account_id, kind, amount, description) VALUES(?,?,?,?)')
                        ->execute([$toAcc['id'], 'transfer_in', $amount, $description . ' - من: ' . $fromAcc['account_no']]);
                    $pdo->prepare('UPDATE bank_accounts SET balance = balance + ? WHERE id = ?')
                        ->execute([$amount, $toAcc['id']]);
                    
                    $pdo->commit();
                    $success = 'تم التحويل بنجاح';
                    // Clear form
                    $_POST = [];
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $error = 'حدث خطأ أثناء التحويل: ' . $e->getMessage();
                }
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<main class="container section-padding">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">التحويل بين الحسابات</h3>
        <a href="index.php" class="btn btn-outline-primary">رجوع</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($userAccounts)): ?>
        <div class="alert alert-warning">
            <i class="bi bi-info-circle"></i> لا توجد حسابات معتمدة لديك. يجب الموافقة على حسابك أولاً من قبل الإدارة.
        </div>
        <a href="request_account.php" class="btn btn-gradient">طلب فتح حساب</a>
    <?php else: ?>
        <div class="row g-4">
            <div class="col-md-8">
                <div class="feature-card">
                    <h5 class="mb-3">إجراء تحويل</h5>
                    <form method="post" class="row g-3">
                        <div class="col-12">
                            <label class="form-label">من الحساب</label>
                            <select name="from_account_id" class="form-select" required>
                                <option value="">اختر الحساب المصدر</option>
                                <?php foreach ($userAccounts as $acc): ?>
                                    <option value="<?= (int)$acc['id'] ?>" <?= isset($_POST['from_account_id']) && $_POST['from_account_id'] == $acc['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($acc['account_no']) ?> - 
                                        <?= htmlspecialchars($acc['account_name']) ?> 
                                        (الرصيد: <?= number_format((float)$acc['balance'], 2) ?> جنيه)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">إلى رقم الحساب</label>
                            <input type="text" name="to_account_no" class="form-control" 
                                   placeholder="ARC-YYYYMMDD-XXXX" 
                                   value="<?= htmlspecialchars($_POST['to_account_no'] ?? '') ?>" required>
                            <small class="text-muted">أدخل رقم الحساب الوجهة</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">المبلغ</label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control" 
                                   value="<?= htmlspecialchars($_POST['amount'] ?? '') ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">الوصف (اختياري)</label>
                            <input type="text" name="description" class="form-control" 
                                   value="<?= htmlspecialchars($_POST['description'] ?? 'تحويل بين الحسابات') ?>">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-gradient">
                                <i class="bi bi-arrow-left-right"></i> إجراء التحويل
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <h6 class="mb-3">حساباتي</h6>
                    <?php foreach ($userAccounts as $acc): ?>
                        <div class="mb-3 p-3 bg-dark rounded">
                            <strong><?= htmlspecialchars($acc['account_no']) ?></strong><br>
                            <small class="text-muted"><?= htmlspecialchars($acc['account_name']) ?></small><br>
                            <span class="text-success fw-bold"><?= number_format((float)$acc['balance'], 2) ?> جنيه</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

