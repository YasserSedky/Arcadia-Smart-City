<?php
$page_title = 'معاملات البنك';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'bank_admin'])) {
  redirect('/dashboard/index.php');
}
$pdo = DB::conn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $account_id = (int)($_POST['account_id'] ?? 0);
  $kind = $_POST['kind'] ?? 'deposit';
  $amount = (float)($_POST['amount'] ?? 0);
  $desc = trim($_POST['description'] ?? '');
  if ($account_id > 0 && $amount > 0) {
    // Check if account is approved and not frozen
    $accountCheck = $pdo->prepare('SELECT status FROM bank_accounts WHERE id = ?');
    $accountCheck->execute([$account_id]);
    $account = $accountCheck->fetch();
    
    if (!$account || $account['status'] !== 'approved') {
      $_SESSION['error'] = 'لا يمكن إجراء معاملة على حساب غير معتمد أو مجمّد';
    } else {
      $pdo->beginTransaction();
      try {
        $pdo->prepare('INSERT INTO bank_transactions(account_id, kind, amount, description) VALUES(?,?,?,?)')
          ->execute([$account_id, $kind, $amount, $desc ?: null]);
        $sign = in_array($kind, ['deposit', 'transfer_in', 'payment'], true) ? 1 : -1;
        $pdo->prepare('UPDATE bank_accounts SET balance = balance + (? * ?) WHERE id=?')
          ->execute([$sign, $amount, $account_id]);
        $pdo->commit();
        $_SESSION['success'] = 'تم تنفيذ المعاملة بنجاح';
      } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = 'حدث خطأ أثناء تنفيذ المعاملة';
      }
    }
  }
  redirect('/dashboard/bank/transactions.php');
}

$accountsStmt = $pdo->prepare('SELECT id, account_no, account_name FROM bank_accounts WHERE status = ? ORDER BY account_no');
$accountsStmt->execute(['approved']);
$accounts = $accountsStmt->fetchAll();
$rows = $pdo->query('
  SELECT t.*, 
         a.account_no, 
         a.account_name,
         a.status as account_status,
         u.full_name as owner_name
  FROM bank_transactions t 
  JOIN bank_accounts a ON a.id = t.account_id 
  LEFT JOIN users u ON u.id = a.owner_user_id
  ORDER BY t.ts DESC 
  LIMIT 500
')->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-0">مراقبة المعاملات</h3>
      <p class="text-muted mb-0">عرض وإدارة جميع المعاملات المصرفية</p>
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

  <div class="feature-card mb-4">
    <form method="post" class="row g-3">
      <div class="col-md-4"><label class="form-label">الحساب</label>
        <select name="account_id" class="form-select" required>
          <option value="">اختر حساب</option>
          <?php foreach ($accounts as $a): ?>
            <option value="<?= (int)$a['id'] ?>">
              <?= htmlspecialchars($a['account_no'] . ' - ' . $a['account_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3"><label class="form-label">النوع</label>
        <select name="kind" class="form-select">
          <option value="deposit">إيداع</option>
          <option value="withdraw">سحب</option>
          <option value="transfer_in">تحويل داخل</option>
          <option value="transfer_out">تحويل خارج</option>
          <option value="charge">رسوم</option>
          <option value="payment">دفعة</option>
        </select>
      </div>
      <div class="col-md-2"><label class="form-label">المبلغ</label><input type="number" step="0.01" class="form-control" name="amount" required></div>
      <div class="col-md-3"><label class="form-label">وصف</label><input class="form-control" name="description"></div>
      <div class="col-md-12 d-grid d-md-flex gap-2"><button class="btn btn-gradient" type="submit">تنفيذ</button></div>
    </form>
  </div>

  <div class="table-responsive feature-card">
    <table class="table table-dark table-striped align-middle mb-0">
      <thead>
        <tr>
          <th>الوقت</th>
          <th>الحساب</th>
          <th>المالك</th>
          <th>النوع</th>
          <th>المبلغ</th>
          <th>الوصف</th>
          <th>حالة الحساب</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= htmlspecialchars(date('Y-m-d H:i:s', strtotime($r['ts']))) ?></td>
            <td>
              <strong><?= htmlspecialchars($r['account_no']) ?></strong><br>
              <small class="text-muted"><?= htmlspecialchars($r['account_name']) ?></small>
            </td>
            <td><?= htmlspecialchars($r['owner_name'] ?? '—') ?></td>
            <td>
              <?php
              $kindLabels = [
                'deposit' => ['إيداع', 'success'],
                'withdraw' => ['سحب', 'danger'],
                'transfer_in' => ['تحويل داخل', 'info'],
                'transfer_out' => ['تحويل خارج', 'warning'],
                'charge' => ['رسوم', 'secondary'],
                'payment' => ['دفعة', 'primary']
              ];
              $kind = $kindLabels[$r['kind']] ?? [$r['kind'], 'secondary'];
              ?>
              <span class="badge bg-<?= $kind[1] ?>"><?= $kind[0] ?></span>
            </td>
            <td>
              <strong class="<?= in_array($r['kind'], ['deposit', 'transfer_in', 'payment']) ? 'text-success' : 'text-danger' ?>">
                <?= in_array($r['kind'], ['deposit', 'transfer_in', 'payment']) ? '+' : '-' ?>
                <?= number_format((float)$r['amount'], 2) ?> جنيه
              </strong>
            </td>
            <td><?= htmlspecialchars($r['description'] ?? '—') ?></td>
            <td>
              <?php
              $statusBadge = [
                'pending' => ['bg-warning', 'قيد الانتظار'],
                'approved' => ['bg-success', 'معتمد'],
                'frozen' => ['bg-danger', 'مجمّد'],
                'rejected' => ['bg-secondary', 'مرفوض']
              ];
              $status = $statusBadge[$r['account_status']] ?? ['bg-secondary', $r['account_status']];
              ?>
              <span class="badge <?= $status[0] ?>"><?= $status[1] ?></span>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?>
          <tr>
            <td colspan="7" class="text-center text-muted">لا توجد معاملات</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
