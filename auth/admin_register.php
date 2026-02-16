<?php $page_title = 'انشاء حساب ادمن';
include __DIR__ . '/../includes/header.php'; ?>

<main class="container section-padding" style="max-width:920px;">
    <div class="feature-card auth-card" data-aos="fade-up">
        <h2 class="mb-4">إنشاء حساب أدمن</h2>
        <?php if (!empty($_GET['err'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['err']); ?></div>
        <?php endif; ?>
        <?php if (!empty($_GET['ok'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['ok']); ?></div>
        <?php endif; ?>

        <form method="post" action="<?php echo APP_BASE; ?>/auth/process_admin_register.php" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">الاسم الكامل</label>
                <input type="text" name="full_name" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">رقم الهاتف</label>
                <input type="tel" name="phone" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">البريد الالكتروني</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">نوع الادمن</label>
                <select name="role_code" class="form-select" required>
                    <option value="super_admin">مدير رئيسي</option>
                    <option value="hq_admin">المقر الإداري</option>
                    <option value="security_admin">أمن</option>
                    <option value="hospital_admin">المستشفى</option>
                    <option value="mall_admin">المول</option>
                </select>
            </div>

            <div class="col-md-6 pw-wrapper">
                <label class="form-label">الرقم السري</label>
                <input type="password" name="password" class="form-control" id="admin-password" required>
                <span class="pw-toggle" data-target="#admin-password">👁️</span>
            </div>
            <div class="col-md-6 pw-wrapper">
                <label class="form-label">تأكيد الرقم السري</label>
                <input type="password" name="confirm_password" class="form-control" id="admin-confirm-password" required>
                <span class="pw-toggle" data-target="#admin-confirm-password">👁️</span>
            </div>

            <div class="col-12">
                <label class="form-label">رمز الأمان لإنشاء حساب أدمن</label>
                <input type="text" name="admin_secret" class="form-control" placeholder="أدخل الرمز السري" required>
                <div class="helper-text">سيُطلب هذا الرمز لحماية إنشاء حسابات الأدمن عبر الويب.</div>
            </div>

            <div class="col-12 d-grid d-md-flex gap-2 auth-actions">
                <button class="btn btn-gradient" type="submit">إنشاء حساب أدمن</button>
                <a class="btn btn-outline-light" href="<?php echo APP_BASE; ?>/auth/login.php">عودة لتسجيل الدخول</a>
            </div>
        </form>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>