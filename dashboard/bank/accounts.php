<?php
$page_title = 'حسابات البنك';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'bank_admin'])) {
  redirect('/dashboard/index.php');
}
$pdo = DB::conn();
$currentUser = $_SESSION['user'];

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $account_id = (int)($_POST['account_id'] ?? 0);

  if ($account_id > 0) {
    if ($action === 'approve') {
      try {
        $pdo->prepare('UPDATE bank_accounts SET status = ?, approved_at = NOW(), approved_by = ? WHERE id = ?')
          ->execute(['approved', (int)$currentUser['id'], $account_id]);
        $_SESSION['success'] = 'تم الموافقة على الحساب بنجاح';
      } catch (PDOException $e) {
        $_SESSION['error'] = 'حدث خطأ أثناء الموافقة على الحساب';
      }
    } elseif ($action === 'reject') {
      $notes = trim($_POST['notes'] ?? '');
      try {
        $pdo->prepare('UPDATE bank_accounts SET status = ?, rejected_at = NOW(), rejected_by = ?, notes = ? WHERE id = ?')
          ->execute(['rejected', (int)$currentUser['id'], $notes ?: null, $account_id]);
        $_SESSION['success'] = 'تم رفض الحساب بنجاح';
      } catch (PDOException $e) {
        $_SESSION['error'] = 'حدث خطأ أثناء رفض الحساب';
      }
    } elseif ($action === 'freeze') {
      $notes = trim($_POST['notes'] ?? '');
      try {
        $pdo->prepare('UPDATE bank_accounts SET status = ?, frozen_at = NOW(), frozen_by = ?, notes = ? WHERE id = ?')
          ->execute(['frozen', (int)$currentUser['id'], $notes ?: null, $account_id]);
        $_SESSION['success'] = 'تم تجميد الحساب بنجاح';
      } catch (PDOException $e) {
        $_SESSION['error'] = 'حدث خطأ أثناء تجميد الحساب';
      }
    } elseif ($action === 'unfreeze') {
      try {
        $pdo->prepare('UPDATE bank_accounts SET status = ?, frozen_at = NULL, frozen_by = NULL WHERE id = ?')
          ->execute(['approved', $account_id]);
        $_SESSION['success'] = 'تم إلغاء تجميد الحساب بنجاح';
      } catch (PDOException $e) {
        $_SESSION['error'] = 'حدث خطأ أثناء إلغاء تجميد الحساب';
      }
    } elseif ($action === 'add_balance') {
      $amount = (float)($_POST['amount'] ?? 0);
      $description = trim($_POST['description'] ?? 'إضافة رصيد من قبل الإدارة');
      if ($amount > 0) {
        $pdo->beginTransaction();
        try {
          // Add transaction
          $pdo->prepare('INSERT INTO bank_transactions(account_id, kind, amount, description) VALUES(?,?,?,?)')
            ->execute([$account_id, 'deposit', $amount, $description]);
          // Update balance
          $pdo->prepare('UPDATE bank_accounts SET balance = balance + ? WHERE id = ?')
            ->execute([$amount, $account_id]);
          $pdo->commit();
          $_SESSION['success'] = 'تم إضافة الرصيد بنجاح';
        } catch (PDOException $e) {
          $pdo->rollBack();
          $_SESSION['error'] = 'حدث خطأ أثناء إضافة الرصيد';
        }
      }
    } elseif ($action === 'withdraw') {
      $amount = (float)($_POST['amount'] ?? 0);
      $description = trim($_POST['description'] ?? 'سحب نقدي من قبل الإدارة');
      if ($amount > 0) {
        // Check account balance
        $accountCheck = $pdo->prepare('SELECT balance, status FROM bank_accounts WHERE id = ?');
        $accountCheck->execute([$account_id]);
        $account = $accountCheck->fetch();

        if (!$account || $account['status'] !== 'approved') {
          $_SESSION['error'] = 'الحساب غير معتمد أو مجمّد';
        } elseif ((float)$account['balance'] < $amount) {
          $_SESSION['error'] = 'الرصيد غير كافي للسحب. الرصيد المتاح: ' . number_format((float)$account['balance'], 2) . ' جنيه';
        } else {
          $pdo->beginTransaction();
          try {
            // Add withdrawal transaction
            $pdo->prepare('INSERT INTO bank_transactions(account_id, kind, amount, description) VALUES(?,?,?,?)')
              ->execute([$account_id, 'withdraw', $amount, $description]);
            // Update balance
            $pdo->prepare('UPDATE bank_accounts SET balance = balance - ? WHERE id = ?')
              ->execute([$amount, $account_id]);
            $pdo->commit();
            $_SESSION['success'] = 'تم سحب ' . number_format($amount, 2) . ' جنيه بنجاح';
          } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'حدث خطأ أثناء السحب: ' . $e->getMessage();
          }
        }
      }
    } elseif ($action === 'create') {
      // Create account directly from admin panel (auto-approved)
      $account_no = trim($_POST['account_no'] ?? '');
      $account_name = trim($_POST['account_name'] ?? '');
      $type = $_POST['type'] ?? 'resident';
      $owner_user_id = $_POST['owner_user_id'] !== '' ? (int)$_POST['owner_user_id'] : null;
      if ($account_no !== '' && $account_name !== '') {
        $stmt = $pdo->prepare('INSERT INTO bank_accounts(owner_user_id, account_no, account_name, type, status, approved_at, approved_by) VALUES(?,?,?,?,?,NOW(),?)');
        try {
          $stmt->execute([$owner_user_id, $account_no, $account_name, $type, 'approved', (int)$currentUser['id']]);
        } catch (PDOException $e) {
        }
      }
    } elseif ($action === 'delete') {
      // Delete account (only if balance is 0 and no active certificates)
      $accountCheck = $pdo->prepare('SELECT balance, status, account_no FROM bank_accounts WHERE id = ?');
      $accountCheck->execute([$account_id]);
      $account = $accountCheck->fetch();

      if (!$account) {
        $_SESSION['error'] = 'الحساب غير موجود';
      } elseif ((float)$account['balance'] != 0) {
        $_SESSION['error'] = 'لا يمكن حذف الحساب لأن الرصيد غير صفر. الرصيد الحالي: ' . number_format((float)$account['balance'], 2) . ' جنيه';
      } elseif ($account['status'] === 'pending') {
        $_SESSION['error'] = 'لا يمكن حذف الحساب لأنه قيد الانتظار. يجب الموافقة عليه أو رفضه أولاً';
      } else {
        // Check for active or matured certificates (these cannot be deleted)
        $certCheck = $pdo->prepare('SELECT COUNT(*) as count FROM investment_certificates WHERE account_id = ? AND status IN (?,?)');
        $certCheck->execute([$account_id, 'active', 'matured']);
        $certCount = $certCheck->fetch()['count'] ?? 0;

        if ($certCount > 0) {
          $_SESSION['error'] = 'لا يمكن حذف الحساب لأنه يحتوي على ' . $certCount . ' شهادة(ات) استثمارية نشطة أو مستحقة. يجب إلغاء أو معالجة هذه الشهادات أولاً';
        } else {
          // Safe to delete - delete in correct order to avoid foreign key constraints
          $pdo->beginTransaction();
          try {
            // Delete monthly payments first (if any) - for all certificates
            $pdo->prepare('DELETE cmp FROM certificate_monthly_payments cmp 
                          INNER JOIN investment_certificates ic ON ic.id = cmp.certificate_id 
                          WHERE ic.account_id = ?')->execute([$account_id]);

            // Delete all certificates (cancelled/completed are safe to delete)
            $pdo->prepare('DELETE FROM investment_certificates WHERE account_id = ?')->execute([$account_id]);

            // Delete all transactions
            $pdo->prepare('DELETE FROM bank_transactions WHERE account_id = ?')->execute([$account_id]);

            // Finally delete the account
            $deleteResult = $pdo->prepare('DELETE FROM bank_accounts WHERE id = ?')->execute([$account_id]);

            // Verify deletion
            $verify = $pdo->prepare('SELECT COUNT(*) as count FROM bank_accounts WHERE id = ?');
            $verify->execute([$account_id]);
            $exists = $verify->fetch()['count'] ?? 0;

            if ($exists > 0) {
              throw new Exception('فشل حذف الحساب');
            }

            $pdo->commit();
            $_SESSION['success'] = 'تم حذف الحساب ' . htmlspecialchars($account['account_no']) . ' بنجاح مع جميع البيانات المرتبطة به';
          } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'حدث خطأ أثناء حذف الحساب: ' . $e->getMessage();
          } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'فشل حذف الحساب. يرجى المحاولة مرة أخرى';
          }
        }
      }
    }
  }
  redirect('/dashboard/bank/accounts.php');
}

// Get pending accounts
$pendingAccounts = $pdo->query("
  SELECT b.*, u.full_name, u.phone, u.email
  FROM bank_accounts b
  LEFT JOIN users u ON u.id = b.owner_user_id
  WHERE b.status = 'pending'
  ORDER BY b.created_at DESC
")->fetchAll();

// Get all accounts with status
$allAccounts = $pdo->query("
  SELECT b.*, u.full_name, u.phone, u.email,
         approver.full_name as approved_by_name,
         freezer.full_name as frozen_by_name
  FROM bank_accounts b
  LEFT JOIN users u ON u.id = b.owner_user_id
  LEFT JOIN users approver ON approver.id = b.approved_by
  LEFT JOIN users freezer ON freezer.id = b.frozen_by
  ORDER BY 
    CASE b.status
      WHEN 'pending' THEN 1
      WHEN 'frozen' THEN 2
      WHEN 'rejected' THEN 3
      ELSE 4
    END,
    b.created_at DESC
")->fetchAll();

$users = $pdo->query('SELECT id, full_name FROM users ORDER BY full_name')->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">إدارة الحسابات</h3>
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

  <!-- Pending Accounts Section -->
  <?php if (!empty($pendingAccounts)): ?>
    <div class="feature-card mb-4">
      <h5 class="mb-3 text-warning">
        <i class="bi bi-clock-history"></i> طلبات الحسابات المعلقة للموافقة (<?= count($pendingAccounts) ?>)
      </h5>
      <div class="table-responsive">
        <table class="table table-dark table-striped align-middle mb-0">
          <thead>
            <tr>
              <th>رقم الحساب</th>
              <th>اسم الحساب</th>
              <th>النوع</th>
              <th>المالك</th>
              <th>الهاتف</th>
              <th>تاريخ الطلب</th>
              <th>الإجراءات</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pendingAccounts as $acc): ?>
              <tr>
                <td><strong><?= htmlspecialchars($acc['account_no']) ?></strong></td>
                <td><?= htmlspecialchars($acc['account_name']) ?></td>
                <td>
                  <span class="badge bg-secondary">
                    <?= $acc['type'] === 'resident' ? 'مقيم' : ($acc['type'] === 'business' ? 'شركة' : 'المدينة') ?>
                  </span>
                </td>
                <td><?= htmlspecialchars($acc['full_name'] ?? '—') ?></td>
                <td><?= htmlspecialchars($acc['phone'] ?? '—') ?></td>
                <td><?= htmlspecialchars($acc['created_at'] ?? '—') ?></td>
                <td>
                  <form method="post" class="d-inline" onsubmit="return confirm('هل أنت متأكد من الموافقة على هذا الحساب؟');">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="account_id" value="<?= (int)$acc['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-success">
                      <i class="bi bi-check-circle"></i> موافقة
                    </button>
                  </form>
                  <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?= (int)$acc['id'] ?>">
                    <i class="bi bi-x-circle"></i> رفض
                  </button>

                  <!-- Reject Modal -->
                  <div class="modal fade" id="rejectModal<?= (int)$acc['id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                      <div class="modal-content bg-dark">
                        <div class="modal-header">
                          <h5 class="modal-title">رفض الحساب</h5>
                          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="post">
                          <div class="modal-body">
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="account_id" value="<?= (int)$acc['id'] ?>">
                            <p>الحساب: <strong><?= htmlspecialchars($acc['account_no']) ?></strong></p>
                            <div class="mb-3">
                              <label class="form-label">سبب الرفض (اختياري)</label>
                              <textarea name="notes" class="form-control" rows="3"></textarea>
                            </div>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                            <button type="submit" class="btn btn-danger">رفض الحساب</button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>

  <!-- Create New Account (Admin) -->
  <div class="feature-card mb-4">
    <h5 class="mb-3">إنشاء حساب جديد (معتمد تلقائياً)</h5>
    <form method="post" class="row g-3">
      <input type="hidden" name="action" value="create">
      <div class="col-md-3">
        <label class="form-label">رقم الحساب</label>
        <input class="form-control" name="account_no" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">اسم الحساب</label>
        <input class="form-control" name="account_name" required>
      </div>
      <div class="col-md-2">
        <label class="form-label">النوع</label>
        <select name="type" class="form-select">
          <option value="resident">مقيم</option>
          <option value="business">شركة</option>
          <option value="city">المدينة</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">المالك (اختياري)</label>
        <select name="owner_user_id" class="form-select">
          <option value="">—</option>
          <?php foreach ($users as $u): ?>
            <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-1 d-grid">
        <label class="form-label">&nbsp;</label>
        <button class="btn btn-gradient" type="submit">إضافة</button>
      </div>
    </form>
  </div>

  <!-- All Accounts Table -->
  <div class="feature-card">
    <h5 class="mb-3">جميع الحسابات</h5>
    <div class="table-responsive">
      <table class="table table-dark table-striped align-middle mb-0">
        <thead>
          <tr>
            <th>رقم الحساب</th>
            <th>اسم الحساب</th>
            <th>النوع</th>
            <th>الحالة</th>
            <th>الرصيد</th>
            <th>المالك</th>
            <th>تاريخ الإنشاء</th>
            <th>الإجراءات</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($allAccounts as $acc): ?>
            <tr>
              <td><strong><?= htmlspecialchars($acc['account_no']) ?></strong></td>
              <td><?= htmlspecialchars($acc['account_name']) ?></td>
              <td>
                <span class="badge bg-secondary">
                  <?= $acc['type'] === 'resident' ? 'مقيم' : ($acc['type'] === 'business' ? 'شركة' : 'المدينة') ?>
                </span>
              </td>
              <td>
                <?php
                $statusBadge = [
                  'pending' => ['bg-warning', 'قيد الانتظار'],
                  'approved' => ['bg-success', 'معتمد'],
                  'frozen' => ['bg-danger', 'مجمّد'],
                  'rejected' => ['bg-secondary', 'مرفوض']
                ];
                $status = $statusBadge[$acc['status']] ?? ['bg-secondary', $acc['status']];
                ?>
                <span class="badge <?= $status[0] ?>"><?= $status[1] ?></span>
              </td>
              <td><strong><?= number_format((float)$acc['balance'], 2) ?> جنيه</strong></td>
              <td><?= htmlspecialchars($acc['full_name'] ?? '—') ?></td>
              <td><?= htmlspecialchars($acc['created_at'] ?? '—') ?></td>
              <td>
                <div class="d-flex gap-1 flex-wrap">
                  <?php if ($acc['status'] === 'approved'): ?>
                    <button type="button" class="btn btn-xs btn-warning" style="font-size: 0.75rem; padding: 0.2rem 0.4rem;" data-bs-toggle="modal" data-bs-target="#freezeModal<?= (int)$acc['id'] ?>" title="تجميد">
                      <i class="bi bi-lock"></i>
                    </button>
                    <button type="button" class="btn btn-xs btn-info" style="font-size: 0.75rem; padding: 0.2rem 0.4rem;" data-bs-toggle="modal" data-bs-target="#addBalanceModal<?= (int)$acc['id'] ?>" title="إضافة رصيد">
                      <i class="bi bi-plus-circle"></i>
                    </button>
                    <button type="button" class="btn btn-xs btn-secondary" style="font-size: 0.75rem; padding: 0.2rem 0.4rem;" data-bs-toggle="modal" data-bs-target="#withdrawModal<?= (int)$acc['id'] ?>" title="سحب نقدي">
                      <i class="bi bi-dash-circle"></i>
                    </button>
                  <?php elseif ($acc['status'] === 'frozen'): ?>
                    <form method="post" class="d-inline" onsubmit="return confirm('هل أنت متأكد من إلغاء تجميد هذا الحساب؟');">
                      <input type="hidden" name="action" value="unfreeze">
                      <input type="hidden" name="account_id" value="<?= (int)$acc['id'] ?>">
                      <button type="submit" class="btn btn-xs btn-success" style="font-size: 0.75rem; padding: 0.2rem 0.4rem;" title="إلغاء التجميد">
                        <i class="bi bi-unlock"></i>
                      </button>
                    </form>
                  <?php endif; ?>
                  <a href="<?php echo APP_BASE; ?>/dashboard/bank/transactions.php?account_id=<?= (int)$acc['id'] ?>" class="btn btn-xs btn-primary" style="font-size: 0.75rem; padding: 0.2rem 0.4rem;" title="المعاملات">
                    <i class="bi bi-list-ul"></i>
                  </a>
                  <?php
                  $canDelete = ((float)$acc['balance'] == 0 && $acc['status'] !== 'pending');
                  $deleteTitle = $canDelete ? 'حذف' : 'حذف (غير متاح - الرصيد غير صفر أو الحساب معلق)';
                  ?>
                  <button type="button"
                    class="btn btn-xs btn-danger <?= !$canDelete ? 'disabled' : '' ?>"
                    style="font-size: 0.75rem; padding: 0.2rem 0.4rem; <?= !$canDelete ? 'opacity: 0.5; cursor: not-allowed;' : '' ?>"
                    <?= $canDelete ? 'data-bs-toggle="modal" data-bs-target="#deleteModal' . (int)$acc['id'] . '"' : '' ?>
                    title="<?= htmlspecialchars($deleteTitle) ?>">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>

                <!-- Freeze Modal -->
                <div class="modal fade" id="freezeModal<?= (int)$acc['id'] ?>" tabindex="-1">
                  <div class="modal-dialog">
                    <div class="modal-content bg-dark">
                      <div class="modal-header">
                        <h5 class="modal-title">تجميد الحساب</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                      </div>
                      <form method="post">
                        <div class="modal-body">
                          <input type="hidden" name="action" value="freeze">
                          <input type="hidden" name="account_id" value="<?= (int)$acc['id'] ?>">
                          <p>الحساب: <strong><?= htmlspecialchars($acc['account_no']) ?></strong></p>
                          <div class="mb-3">
                            <label class="form-label">سبب التجميد (اختياري)</label>
                            <textarea name="notes" class="form-control" rows="3"></textarea>
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                          <button type="submit" class="btn btn-danger">تجميد الحساب</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>

                <!-- Add Balance Modal -->
                <div class="modal fade" id="addBalanceModal<?= (int)$acc['id'] ?>" tabindex="-1">
                  <div class="modal-dialog">
                    <div class="modal-content bg-dark">
                      <div class="modal-header">
                        <h5 class="modal-title">إضافة رصيد</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                      </div>
                      <form method="post">
                        <div class="modal-body">
                          <input type="hidden" name="action" value="add_balance">
                          <input type="hidden" name="account_id" value="<?= (int)$acc['id'] ?>">
                          <p>الحساب: <strong><?= htmlspecialchars($acc['account_no']) ?></strong></p>
                          <p>الرصيد الحالي: <strong><?= number_format((float)$acc['balance'], 2) ?> جنيه</strong></p>
                          <div class="mb-3">
                            <label class="form-label">المبلغ</label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                          </div>
                          <div class="mb-3">
                            <label class="form-label">الوصف (اختياري)</label>
                            <input type="text" name="description" class="form-control" value="إضافة رصيد من قبل الإدارة">
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                          <button type="submit" class="btn btn-success">إضافة الرصيد</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>

                <!-- Withdraw Modal -->
                <div class="modal fade" id="withdrawModal<?= (int)$acc['id'] ?>" tabindex="-1">
                  <div class="modal-dialog">
                    <div class="modal-content bg-dark">
                      <div class="modal-header">
                        <h5 class="modal-title">سحب نقدي</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                      </div>
                      <form method="post">
                        <div class="modal-body">
                          <input type="hidden" name="action" value="withdraw">
                          <input type="hidden" name="account_id" value="<?= (int)$acc['id'] ?>">
                          <p>الحساب: <strong><?= htmlspecialchars($acc['account_no']) ?></strong></p>
                          <p>الرصيد المتاح: <strong class="text-success"><?= number_format((float)$acc['balance'], 2) ?> جنيه</strong></p>
                          <div class="mb-3">
                            <label class="form-label">المبلغ المراد سحبه</label>
                            <input type="number" step="0.01" min="0.01" max="<?= (float)$acc['balance'] ?>" name="amount" class="form-control" required id="withdrawAmount<?= (int)$acc['id'] ?>">
                            <small class="text-muted">الحد الأقصى: <?= number_format((float)$acc['balance'], 2) ?> جنيه</small>
                          </div>
                          <div class="mb-3">
                            <label class="form-label">الوصف (اختياري)</label>
                            <input type="text" name="description" class="form-control" value="سحب نقدي من قبل الإدارة">
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                          <button type="submit" class="btn btn-warning">سحب المبلغ</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>

                <!-- Delete Modal -->
                <div class="modal fade" id="deleteModal<?= (int)$acc['id'] ?>" tabindex="-1">
                  <div class="modal-dialog">
                    <div class="modal-content bg-dark">
                      <div class="modal-header">
                        <h5 class="modal-title text-danger">حذف الحساب</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                      </div>
                      <form method="post" onsubmit="return confirm('هل أنت متأكد تماماً من حذف هذا الحساب؟ سيتم حذف جميع المعاملات المرتبطة به. لا يمكن التراجع عن هذا الإجراء!');">
                        <div class="modal-body">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="account_id" value="<?= (int)$acc['id'] ?>">
                          <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i> <strong>تحذير:</strong> سيتم حذف هذا الحساب نهائياً مع جميع المعاملات المرتبطة به.
                          </div>
                          <p><strong>رقم الحساب:</strong> <?= htmlspecialchars($acc['account_no']) ?></p>
                          <p><strong>اسم الحساب:</strong> <?= htmlspecialchars($acc['account_name']) ?></p>
                          <p><strong>الرصيد:</strong> <?= number_format((float)$acc['balance'], 2) ?> جنيه</p>
                          <p class="text-muted small">ملاحظة: يمكن حذف الحساب فقط إذا كان الرصيد صفر ولا توجد شهادات استثمارية نشطة.</p>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                          <button type="submit" class="btn btn-danger">حذف الحساب</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($allAccounts)): ?>
            <tr>
              <td colspan="8" class="text-center text-muted">لا توجد حسابات</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>