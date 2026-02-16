<?php
$page_title = 'الشهادات الاستثمارية';
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

// Handle certificate purchase
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'purchase') {
    $account_id = (int)($_POST['account_id'] ?? 0);
    $certificate_type = $_POST['certificate_type'] ?? '';
    $amount = (float)($_POST['amount'] ?? 0);
    
    if ($account_id <= 0 || !in_array($certificate_type, ['annual_lump', 'annual_monthly']) || $amount <= 0) {
        $error = 'يرجى إدخال جميع البيانات المطلوبة بشكل صحيح';
    } else {
        // Verify account belongs to user
        $account = $pdo->prepare('SELECT * FROM bank_accounts WHERE id = ? AND owner_user_id = ? AND status = ?');
        $account->execute([$account_id, (int)$user['id'], 'approved']);
        $acc = $account->fetch();
        
        if (!$acc) {
            $error = 'الحساب غير صحيح أو غير معتمد';
        } elseif ($acc['balance'] < $amount) {
            $error = 'الرصيد غير كافي لشراء الشهادة';
        } else {
            // Minimum amount check (optional, you can set a minimum)
            if ($amount < 1000) {
                $error = 'الحد الأدنى لشراء الشهادة هو 1000 جنيه';
            } else {
                $pdo->beginTransaction();
                try {
                    // Generate certificate number
                    $date = date('Ymd');
                    do {
                        $rand = rand(1000, 9999);
                        $certificate_no = 'CERT-' . $date . '-' . $rand;
                        $check = $pdo->prepare('SELECT 1 FROM investment_certificates WHERE certificate_no = ?');
                        $check->execute([$certificate_no]);
                        $exists = (bool)$check->fetch();
                    } while ($exists);
                    
                    // Calculate dates
                    $start_date = date('Y-m-d');
                    $maturity_date = date('Y-m-d', strtotime('+1 year'));
                    
                    // Set interest rates
                    $interest_rate = $certificate_type === 'annual_lump' ? 27.00 : 24.00;
                    $monthly_interest_rate = $certificate_type === 'annual_monthly' ? 2.00 : null;
                    
                    // Deduct amount from account
                    $pdo->prepare('INSERT INTO bank_transactions(account_id, kind, amount, description) VALUES(?,?,?,?)')
                        ->execute([$account_id, 'withdraw', $amount, 'شراء شهادة استثمارية: ' . $certificate_no]);
                    $pdo->prepare('UPDATE bank_accounts SET balance = balance - ? WHERE id = ?')
                        ->execute([$amount, $account_id]);
                    
                    // Create certificate
                    $pdo->prepare('
                        INSERT INTO investment_certificates(
                            account_id, user_id, certificate_no, type, principal_amount, 
                            interest_rate, monthly_interest_rate, start_date, maturity_date, status
                        ) VALUES(?,?,?,?,?,?,?,?,?,?)
                    ')->execute([
                        $account_id, (int)$user['id'], $certificate_no, $certificate_type, 
                        $amount, $interest_rate, $monthly_interest_rate, $start_date, $maturity_date, 'active'
                    ]);
                    
                    $pdo->commit();
                    $success = 'تم شراء الشهادة الاستثمارية بنجاح. رقم الشهادة: ' . $certificate_no;
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $error = 'حدث خطأ أثناء شراء الشهادة: ' . $e->getMessage();
                }
            }
        }
    }
}

// Handle certificate cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel') {
    $certificate_id = (int)($_POST['certificate_id'] ?? 0);
    
    if ($certificate_id <= 0) {
        $error = 'معرف الشهادة غير صحيح';
    } else {
        // Get certificate
        $cert = $pdo->prepare('SELECT * FROM investment_certificates WHERE id = ? AND user_id = ? AND status = ?');
        $cert->execute([$certificate_id, (int)$user['id'], 'active']);
        $certificate = $cert->fetch();
        
        if (!$certificate) {
            $error = 'الشهادة غير موجودة أو غير نشطة';
        } else {
            $pdo->beginTransaction();
            try {
                // Calculate penalty (8% of principal)
                $penalty = $certificate['principal_amount'] * 0.08;
                
                // Calculate refund (principal - penalty - interest paid if monthly)
                $refund_amount = $certificate['principal_amount'] - $penalty;
                
                if ($certificate['type'] === 'annual_monthly') {
                    // Deduct total interest paid
                    $refund_amount -= $certificate['total_interest_paid'];
                }
                
                // Ensure refund is not negative
                if ($refund_amount < 0) {
                    $refund_amount = 0;
                }
                
                // Update certificate
                $pdo->prepare('
                    UPDATE investment_certificates 
                    SET status = ?, cancelled_at = ?, cancellation_penalty = ?
                    WHERE id = ?
                ')->execute(['cancelled', date('Y-m-d'), $penalty, $certificate_id]);
                
                // Refund to account
                if ($refund_amount > 0) {
                    $pdo->prepare('INSERT INTO bank_transactions(account_id, kind, amount, description) VALUES(?,?,?,?)')
                        ->execute([
                            $certificate['account_id'], 
                            'deposit', 
                            $refund_amount, 
                            'إرجاع مبلغ شهادة استثمارية ملغاة: ' . $certificate['certificate_no']
                        ]);
                    $pdo->prepare('UPDATE bank_accounts SET balance = balance + ? WHERE id = ?')
                        ->execute([$refund_amount, $certificate['account_id']]);
                }
                
                $pdo->commit();
                $success = 'تم إلغاء الشهادة بنجاح. المبلغ المرتجع: ' . number_format($refund_amount, 2) . ' جنيه';
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = 'حدث خطأ أثناء إلغاء الشهادة: ' . $e->getMessage();
            }
        }
    }
}

// Get user's certificates
$certificates = $pdo->prepare('
    SELECT c.*, a.account_no, a.account_name
    FROM investment_certificates c
    JOIN bank_accounts a ON a.id = c.account_id
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC
');
$certificates->execute([(int)$user['id']]);
$certificates = $certificates->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<main class="container section-padding">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">الشهادات الاستثمارية</h3>
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
        <!-- Purchase Certificate Form -->
        <div class="feature-card mb-4">
            <h5 class="mb-3">شراء شهادة استثمارية جديدة</h5>
            <form method="post" class="row g-3">
                <input type="hidden" name="action" value="purchase">
                <div class="col-md-4">
                    <label class="form-label">من الحساب</label>
                    <select name="account_id" class="form-select" required>
                        <option value="">اختر الحساب</option>
                        <?php foreach ($userAccounts as $acc): ?>
                            <option value="<?= (int)$acc['id'] ?>">
                                <?= htmlspecialchars($acc['account_no']) ?> - 
                                <?= htmlspecialchars($acc['account_name']) ?> 
                                (الرصيد: <?= number_format((float)$acc['balance'], 2) ?> جنيه)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">نوع الشهادة</label>
                    <select name="certificate_type" class="form-select" required id="certificate_type">
                        <option value="">اختر النوع</option>
                        <option value="annual_lump">شهادة سنوية (27% فائدة عند الاستحقاق)</option>
                        <option value="annual_monthly">شهادة شهرية (24% فائدة - 2% شهرياً)</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">المبلغ (الحد الأدنى: 1000 جنيه)</label>
                    <input type="number" step="0.01" min="1000" name="amount" class="form-control" required>
                </div>
                <div class="col-12">
                    <div class="alert alert-info">
                        <strong>شهادة سنوية (27%):</strong> يتم إرجاع أصل المبلغ + 27% فائدة بعد سنة واحدة.<br>
                        <strong>شهادة شهرية (24%):</strong> يتم إيداع 2% من أصل المبلغ كل شهر في حسابك، وفي نهاية السنة يتم إرجاع أصل المبلغ.<br>
                        <strong>ملاحظة:</strong> في حالة الإلغاء قبل الاستحقاق، يتم خصم 8% من أصل المبلغ + خصم الأرباح المدفوعة (للشهادة الشهرية).
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-gradient">
                        <i class="bi bi-cart-plus"></i> شراء الشهادة
                    </button>
                </div>
            </form>
        </div>

        <!-- User's Certificates -->
        <div class="feature-card">
            <h5 class="mb-3">شهاداتي الاستثمارية</h5>
            <?php if (empty($certificates)): ?>
                <div class="alert alert-info">لا توجد شهادات استثمارية لديك</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>رقم الشهادة</th>
                                <th>النوع</th>
                                <th>أصل المبلغ</th>
                                <th>الفائدة</th>
                                <th>تاريخ البدء</th>
                                <th>تاريخ الاستحقاق</th>
                                <th>الفائدة المدفوعة</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($certificates as $cert): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($cert['certificate_no']) ?></strong></td>
                                    <td>
                                        <?php if ($cert['type'] === 'annual_lump'): ?>
                                            <span class="badge bg-primary">سنوية (27%)</span>
                                        <?php else: ?>
                                            <span class="badge bg-info">شهرية (24%)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= number_format((float)$cert['principal_amount'], 2) ?> جنيه</strong></td>
                                    <td><?= number_format((float)$cert['interest_rate'], 2) ?>%</td>
                                    <td><?= htmlspecialchars($cert['start_date']) ?></td>
                                    <td><?= htmlspecialchars($cert['maturity_date']) ?></td>
                                    <td><?= number_format((float)$cert['total_interest_paid'], 2) ?> جنيه</td>
                                    <td>
                                        <?php
                                        $statusBadge = [
                                            'active' => ['bg-success', 'نشطة'],
                                            'matured' => ['bg-warning', 'مستحقة'],
                                            'cancelled' => ['bg-danger', 'ملغاة'],
                                            'completed' => ['bg-secondary', 'مكتملة']
                                        ];
                                        $status = $statusBadge[$cert['status']] ?? ['bg-secondary', $cert['status']];
                                        ?>
                                        <span class="badge <?= $status[0] ?>"><?= $status[1] ?></span>
                                    </td>
                                    <td>
                                        <?php if ($cert['status'] === 'active'): ?>
                                            <form method="post" class="d-inline" onsubmit="return confirm('هل أنت متأكد من إلغاء هذه الشهادة؟ سيتم خصم 8% من أصل المبلغ + خصم الأرباح المدفوعة (إن وجدت).');">
                                                <input type="hidden" name="action" value="cancel">
                                                <input type="hidden" name="certificate_id" value="<?= (int)$cert['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="bi bi-x-circle"></i> إلغاء
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

