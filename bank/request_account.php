<?php
$page_title = 'طلب فتح حساب مصرفي';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$user = $_SESSION['user'] ?? null;
if (!$user || empty($user['id'])) {
    header('Location: ' . APP_BASE . '/auth/logout.php');
    exit;
}

$pdo = DB::conn();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account_name = trim($_POST['account_name'] ?? '');
    $type = $_POST['type'] ?? 'resident';
    if ($account_name === '') {
        $error = 'يرجى إدخال اسم الحساب';
    } else {
        // Check if user already has a personal account (type='resident')
        if ($type === 'resident') {
            $checkExisting = $pdo->prepare('SELECT id FROM bank_accounts WHERE owner_user_id = ? AND type = ?');
            $checkExisting->execute([(int)$user['id'], 'resident']);
            if ($checkExisting->fetch()) {
                $error = 'لديك حساب شخصي بالفعل. لا يمكن إنشاء أكثر من حساب شخصي واحد.';
            }
        }
        
        if (!$error) {
            // generate unique account number (ARC-YYYYMMDD-XXXX)
            $date = date('Ymd');
            do {
                $rand = rand(1000, 9999);
                $account_no = 'ARC-' . $date . '-' . $rand;
                $check = $pdo->prepare('SELECT 1 FROM bank_accounts WHERE account_no = ?');
                $check->execute([$account_no]);
                $exists = (bool)$check->fetch();
            } while ($exists);

            // Create account with pending status
            $stmt = $pdo->prepare('INSERT INTO bank_accounts(owner_user_id, account_no, account_name, type, balance, status) VALUES(?,?,?,?,0.00,\'pending\')');
            try {
                $stmt->execute([(int)$user['id'], $account_no, $account_name, $type]);
                redirect('/bank/accounts.php?pending=1');
            } catch (PDOException $e) {
                $error = 'حدث خطأ أثناء إنشاء الحساب';
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<main class="container section-padding">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">طلب فتح حساب</h3>
        <a href="accounts.php" class="btn btn-outline-primary">رجوع</a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" class="row g-3">
        <div class="col-md-6">
            <label class="form-label">اسم الحساب</label>
            <input class="form-control" name="account_name" required value="<?= htmlspecialchars($_POST['account_name'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">النوع</label>
            <select name="type" class="form-select">
                <option value="resident">مقيم</option>
                <option value="business">شركة</option>
            </select>
        </div>
        <div class="col-12 d-grid">
            <button class="btn btn-gradient" type="submit">طلب فتح الحساب</button>
        </div>
    </form>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>