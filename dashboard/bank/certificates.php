<?php
$page_title = 'إدارة الشهادات الاستثمارية';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'bank_admin'])) {
  redirect('/dashboard/index.php');
}
$pdo = DB::conn();
$currentUser = $_SESSION['user'];

// Handle maturity processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_maturity') {
    $certificate_id = (int)($_POST['certificate_id'] ?? 0);
    
    if ($certificate_id > 0) {
        $cert = $pdo->prepare('SELECT * FROM investment_certificates WHERE id = ? AND status = ?');
        $cert->execute([$certificate_id, 'matured']);
        $certificate = $cert->fetch();
        
        if ($certificate) {
            $pdo->beginTransaction();
            try {
                if ($certificate['type'] === 'annual_lump') {
                    // Calculate total return (principal + 27% interest)
                    $total_return = $certificate['principal_amount'] * 1.27;
                    $interest_amount = $certificate['principal_amount'] * 0.27;
                    
                    // Deposit to account
                    $pdo->prepare('INSERT INTO bank_transactions(account_id, kind, amount, description) VALUES(?,?,?,?)')
                        ->execute([
                            $certificate['account_id'],
                            'deposit',
                            $total_return,
                            'استحقاق شهادة استثمارية: ' . $certificate['certificate_no'] . ' (أصل + فائدة 27%)'
                        ]);
                    $pdo->prepare('UPDATE bank_accounts SET balance = balance + ? WHERE id = ?')
                        ->execute([$total_return, $certificate['account_id']]);
                } else {
                    // Monthly type - only return principal (interest already paid monthly)
                    $pdo->prepare('INSERT INTO bank_transactions(account_id, kind, amount, description) VALUES(?,?,?,?)')
                        ->execute([
                            $certificate['account_id'],
                            'deposit',
                            $certificate['principal_amount'],
                            'استحقاق شهادة استثمارية: ' . $certificate['certificate_no'] . ' (أصل المبلغ)'
                        ]);
                    $pdo->prepare('UPDATE bank_accounts SET balance = balance + ? WHERE id = ?')
                        ->execute([$certificate['principal_amount'], $certificate['account_id']]);
                }
                
                // Update certificate status
                $pdo->prepare('UPDATE investment_certificates SET status = ? WHERE id = ?')
                    ->execute(['completed', $certificate_id]);
                
                $pdo->commit();
                $_SESSION['success'] = 'تم معالجة استحقاق الشهادة بنجاح';
            } catch (PDOException $e) {
                $pdo->rollBack();
                $_SESSION['error'] = 'حدث خطأ أثناء معالجة الاستحقاق';
            }
        }
    }
    redirect('/dashboard/bank/certificates.php');
}

// Get all certificates
$certificates = $pdo->query('
    SELECT c.*, 
           a.account_no, 
           a.account_name,
           u.full_name as owner_name,
           u.phone as owner_phone
    FROM investment_certificates c
    JOIN bank_accounts a ON a.id = c.account_id
    JOIN users u ON u.id = c.user_id
    ORDER BY c.created_at DESC
')->fetchAll();

// Check for matured certificates
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
      <h3 class="mb-0">إدارة الشهادات الاستثمارية</h3>
      <p class="text-muted mb-0">عرض وإدارة جميع الشهادات الاستثمارية</p>
    </div>
    <a href="<?php echo APP_BASE; ?>/dashboard/bank/index.php" class="btn btn-outline-light">رجوع</a>
  </div>

  <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
      <?= htmlspecialchars($_SESSION['success']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
  <?php endif; ?>

  <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
      <?= htmlspecialchars($_SESSION['error']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
  <?php endif; ?>

  <?php if ($maturedCount > 0): ?>
    <div class="alert alert-warning">
      <i class="bi bi-exclamation-triangle"></i> يوجد <?= $maturedCount ?> شهادة(ات) مستحقة تحتاج إلى معالجة
    </div>
  <?php endif; ?>

  <div class="feature-card">
    <h5 class="mb-3">جميع الشهادات الاستثمارية</h5>
    <div class="table-responsive">
      <table class="table table-dark table-striped align-middle mb-0">
        <thead>
          <tr>
            <th>رقم الشهادة</th>
            <th>المالك</th>
            <th>الحساب</th>
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
                <?= htmlspecialchars($cert['owner_name']) ?><br>
                <small class="text-muted"><?= htmlspecialchars($cert['owner_phone']) ?></small>
              </td>
              <td>
                <?= htmlspecialchars($cert['account_no']) ?><br>
                <small class="text-muted"><?= htmlspecialchars($cert['account_name']) ?></small>
              </td>
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
              <td>
                <?= htmlspecialchars($cert['maturity_date']) ?>
                <?php if (strtotime($cert['maturity_date']) <= time() && $cert['status'] === 'active'): ?>
                  <br><span class="badge bg-warning">مستحقة</span>
                <?php endif; ?>
              </td>
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
                <?php if ($cert['status'] === 'matured' || (strtotime($cert['maturity_date']) <= time() && $cert['status'] === 'active')): ?>
                  <form method="post" class="d-inline" onsubmit="return confirm('هل أنت متأكد من معالجة استحقاق هذه الشهادة؟');">
                    <input type="hidden" name="action" value="process_maturity">
                    <input type="hidden" name="certificate_id" value="<?= (int)$cert['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-success">
                      <i class="bi bi-check-circle"></i> معالجة الاستحقاق
                    </button>
                  </form>
                <?php elseif ($cert['status'] === 'cancelled' && $cert['cancelled_at']): ?>
                  <small class="text-muted">ملغاة في: <?= htmlspecialchars($cert['cancelled_at']) ?></small>
                <?php elseif ($cert['status'] === 'completed'): ?>
                  <small class="text-success">مكتملة</small>
                <?php else: ?>
                  <small class="text-muted">قيد التنفيذ</small>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($certificates)): ?>
            <tr>
              <td colspan="11" class="text-center text-muted">لا توجد شهادات استثمارية</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

