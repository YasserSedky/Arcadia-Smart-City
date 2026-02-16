<?php
$page_title = 'معالجة الفائدة الشهرية';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'bank_admin'])) {
  redirect('/dashboard/index.php');
}
$pdo = DB::conn();

// Process monthly interest for active monthly certificates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process') {
    $processed = 0;
    $errors = [];
    
    // Get all active monthly certificates
    $certificates = $pdo->query("
        SELECT c.*, a.account_no, a.account_name, u.full_name
        FROM investment_certificates c
        JOIN bank_accounts a ON a.id = c.account_id
        JOIN users u ON u.id = c.user_id
        WHERE c.type = 'annual_monthly' 
        AND c.status = 'active'
        AND a.status = 'approved'
    ")->fetchAll();
    
    $currentMonth = date('Y-m-01'); // First day of current month
    
    foreach ($certificates as $cert) {
        // Check if payment already made for this month
        $checkPayment = $pdo->prepare('
            SELECT id FROM certificate_monthly_payments 
            WHERE certificate_id = ? AND payment_month = ?
        ');
        $checkPayment->execute([$cert['id'], $currentMonth]);
        
        if ($checkPayment->fetch()) {
            continue; // Already paid this month
        }
        
        // Calculate monthly interest (2% of principal)
        $monthlyInterest = $cert['principal_amount'] * 0.02;
        
        $pdo->beginTransaction();
        try {
            // Add transaction to account
            $stmt = $pdo->prepare('
                INSERT INTO bank_transactions(account_id, kind, amount, description) 
                VALUES(?,?,?,?)
            ');
            $stmt->execute([
                $cert['account_id'],
                'deposit',
                $monthlyInterest,
                'فائدة شهرية - شهادة استثمارية: ' . $cert['certificate_no']
            ]);
            $transactionId = $pdo->lastInsertId();
            
            // Update account balance
            $pdo->prepare('UPDATE bank_accounts SET balance = balance + ? WHERE id = ?')
                ->execute([$monthlyInterest, $cert['account_id']]);
            
            // Record monthly payment
            $pdo->prepare('
                INSERT INTO certificate_monthly_payments(
                    certificate_id, payment_month, amount, transaction_id
                ) VALUES(?,?,?,?)
            ')->execute([
                $cert['id'],
                $currentMonth,
                $monthlyInterest,
                $transactionId
            ]);
            
            // Update total interest paid
            $pdo->prepare('
                UPDATE investment_certificates 
                SET total_interest_paid = total_interest_paid + ? 
                WHERE id = ?
            ')->execute([$monthlyInterest, $cert['id']]);
            
            $pdo->commit();
            $processed++;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'خطأ في معالجة الشهادة ' . $cert['certificate_no'] . ': ' . $e->getMessage();
        }
    }
    
    // Check for matured certificates and mark them
    $matured = $pdo->query("
        SELECT id, certificate_no 
        FROM investment_certificates 
        WHERE status = 'active' 
        AND maturity_date <= CURDATE()
    ")->fetchAll();
    
    foreach ($matured as $cert) {
        $pdo->prepare('UPDATE investment_certificates SET status = ? WHERE id = ?')
            ->execute(['matured', $cert['id']]);
    }
    
    if ($processed > 0 || !empty($matured)) {
        $_SESSION['success'] = "تم معالجة $processed شهادة(ات) بنجاح";
        if (!empty($matured)) {
            $_SESSION['success'] .= " وتم تحديد " . count($matured) . " شهادة(ات) كمستحقة";
        }
    } else {
        $_SESSION['info'] = "لا توجد شهادات تحتاج إلى معالجة هذا الشهر";
    }
    
    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
    }
    
    redirect('/dashboard/bank/certificates.php');
}

// Get statistics
$activeMonthly = $pdo->query("
    SELECT COUNT(*) as count 
    FROM investment_certificates 
    WHERE type = 'annual_monthly' AND status = 'active'
")->fetch()['count'] ?? 0;

$pendingThisMonth = $pdo->query("
    SELECT COUNT(*) as count
    FROM investment_certificates c
    WHERE c.type = 'annual_monthly' 
    AND c.status = 'active'
    AND NOT EXISTS (
        SELECT 1 FROM certificate_monthly_payments p
        WHERE p.certificate_id = c.id 
        AND p.payment_month = DATE_FORMAT(CURDATE(), '%Y-%m-01')
    )
")->fetch()['count'] ?? 0;

$maturedCount = $pdo->query("
    SELECT COUNT(*) as count 
    FROM investment_certificates 
    WHERE status = 'matured'
")->fetch()['count'] ?? 0;

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-0">معالجة الفائدة الشهرية</h3>
      <p class="text-muted mb-0">معالجة الفائدة الشهرية للشهادات الاستثمارية</p>
    </div>
    <a href="<?php echo APP_BASE; ?>/dashboard/bank/certificates.php" class="btn btn-outline-light">رجوع</a>
  </div>

  <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
      <?= htmlspecialchars($_SESSION['success']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
  <?php endif; ?>

  <?php if (isset($_SESSION['info'])): ?>
    <div class="alert alert-info alert-dismissible fade show">
      <?= htmlspecialchars($_SESSION['info']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['info']); ?>
  <?php endif; ?>

  <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
      <?= htmlspecialchars($_SESSION['error']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
  <?php endif; ?>

  <div class="row g-4 mb-4">
    <div class="col-md-4">
      <div class="feature-card text-center">
        <h2 class="text-primary"><?= $activeMonthly ?></h2>
        <p class="mb-0">شهادة شهرية نشطة</p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="feature-card text-center">
        <h2 class="text-warning"><?= $pendingThisMonth ?></h2>
        <p class="mb-0">شهادة تحتاج معالجة هذا الشهر</p>
      </div>
    </div>
    <div class="col-md-4">
      <div class="feature-card text-center">
        <h2 class="text-danger"><?= $maturedCount ?></h2>
        <p class="mb-0">شهادة مستحقة</p>
      </div>
    </div>
  </div>

  <div class="feature-card">
    <h5 class="mb-3">معالجة الفائدة الشهرية</h5>
    <p class="mb-3">
      سيتم معالجة الفائدة الشهرية (2% من أصل المبلغ) لجميع الشهادات الشهرية النشطة التي لم يتم دفع فائدتها لهذا الشهر.
    </p>
    <form method="post" onsubmit="return confirm('هل أنت متأكد من معالجة الفائدة الشهرية لجميع الشهادات؟');">
      <input type="hidden" name="action" value="process">
      <button type="submit" class="btn btn-gradient">
        <i class="bi bi-play-circle"></i> معالجة الفائدة الشهرية
      </button>
    </form>
  </div>

  <div class="feature-card mt-4">
    <h5 class="mb-3">ملاحظات</h5>
    <ul>
      <li>يتم دفع 2% من أصل المبلغ كل شهر للشهادات الشهرية</li>
      <li>يتم إيداع الفائدة مباشرة في حساب العميل</li>
      <li>يتم تسجيل كل دفعة في سجل المعاملات</li>
      <li>يتم تحديث إجمالي الفائدة المدفوعة تلقائياً</li>
      <li>يمكن تشغيل هذه الوظيفة شهرياً أو إعدادها كـ cron job</li>
    </ul>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

