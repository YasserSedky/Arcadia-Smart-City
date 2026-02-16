<?php
$page_title = 'إدارة البنك';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'bank_admin'])) {
  redirect('/dashboard/index.php');
}
include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <h2 class="mb-4">إدارة البنك</h2>
  <div class="row g-4">
    <div class="col-md-6" data-aos="fade-up">
      <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/dashboard/bank/accounts.php">
        <div class="feature-card h-100">
          <i class="bi bi-wallet2"></i>
          <h5 class="mt-3">الحسابات</h5>
          <p class="mb-0">فتح وإدارة الحسابات</p>
        </div>
      </a>
    </div>
    <div class="col-md-6" data-aos="fade-up">
      <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/dashboard/bank/transactions.php">
        <div class="feature-card h-100">
          <i class="bi bi-cash-stack"></i>
          <h5 class="mt-3">المعاملات</h5>
          <p class="mb-0">إيداع/سحب/تحويل</p>
        </div>
      </a>
    </div>
    <div class="col-md-6" data-aos="fade-up">
      <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/dashboard/bank/certificates.php">
        <div class="feature-card h-100">
          <i class="bi bi-file-earmark-text"></i>
          <h5 class="mt-3">الشهادات الاستثمارية</h5>
          <p class="mb-0">إدارة الشهادات الاستثمارية</p>
        </div>
      </a>
    </div>
    <div class="col-md-6" data-aos="fade-up">
      <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/dashboard/bank/process_monthly_interest.php">
        <div class="feature-card h-100">
          <i class="bi bi-calendar-check"></i>
          <h5 class="mt-3">معالجة الفائدة الشهرية</h5>
          <p class="mb-0">معالجة الفائدة الشهرية للشهادات</p>
        </div>
      </a>
    </div>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
