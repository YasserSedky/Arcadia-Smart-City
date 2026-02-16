<?php
$page_title = 'المقر الإداري HQ';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'hq_admin'])) {
  redirect('/dashboard/index.php');
}
include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
  <h2 class="mb-4">المقر الإداري HQ</h2>
  <div class="row g-4">
    <div class="col-md-6" data-aos="fade-up">
      <a class="text-decoration-none" href="<?php echo APP_BASE; ?>/dashboard/hq/notices.php">
        <div class="feature-card h-100">
          <i class="bi bi-megaphone"></i>
          <h5 class="mt-3">الإعلانات</h5>
          <p class="mb-0">نشر تعاميم وإشعارات المدينة</p>
        </div>
      </a>
    </div>
  </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
