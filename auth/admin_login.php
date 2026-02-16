<?php $page_title = 'تسجيل دخول الأدمن';
include __DIR__ . '/../includes/header.php'; ?>

<main class="container section-padding" style="max-width:720px;">
    <div class="feature-card auth-card" data-aos="fade-up">
        <h2 class="mb-4">تسجيل دخول الأدمن</h2>
        <?php if (!empty($_GET['err'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['err']); ?></div>
        <?php endif; ?>
        <?php if (!empty($_GET['ok'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['ok']); ?></div>
        <?php endif; ?>

        <form method="post" action="<?php echo APP_BASE; ?>/auth/process_login.php" class="row g-3">
            <div class="col-12">
                <label class="form-label">البريد الالكتروني أو رقم الهاتف</label>
                <input type="text" name="phone" class="form-control" placeholder="email@example.com أو 05XXXXXXXX" required>
            </div>
            <div class="col-12 pw-wrapper">
                <label class="form-label">الرقم السري</label>
                <input type="password" name="password" class="form-control" id="admin-login-password" required>
                <span class="pw-toggle" data-target="#admin-login-password">👁️</span>
            </div>
            <div class="col-12 d-grid d-md-flex gap-2 auth-actions">
                <button class="btn btn-gradient" type="submit">دخول</button>
                <a class="btn btn-outline-light" href="<?php echo APP_BASE; ?>/auth/login.php">دخول مستخدم عادي</a>
            </div>
        </form>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>