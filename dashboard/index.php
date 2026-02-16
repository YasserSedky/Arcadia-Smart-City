<?php
$page_title = 'لوحة التحكم';
require_once __DIR__ . '/../includes/auth.php';
require_login();
include __DIR__ . '/../includes/header.php';

$u = $_SESSION['user'];
$role = $u['role_code'];
$isSuper = ($role === 'super_admin');

function sectionCard($icon, $title, $desc, $href)
{
  // ensure hrefs use APP_BASE when given as absolute paths starting with /
  if (is_string($href) && str_starts_with($href, '/')) {
    $href = APP_BASE . $href;
  }
  echo '<div class="col-md-6 col-lg-4" data-aos="fade-up">';
  echo '  <a class="text-decoration-none" href="' . htmlspecialchars($href) . '">';
  echo '    <div class="feature-card h-100">';
  echo '      <i class="bi ' . htmlspecialchars($icon) . '"></i>';
  echo '      <h5 class="mt-3">' . htmlspecialchars($title) . '</h5>';
  echo '      <p class="mb-0">' . htmlspecialchars($desc) . '</p>';
  echo '    </div>';
  echo '  </a>';
  echo '</div>';
}
?>

<main class="container section-padding">
  <div class="mb-4">
    <h2>مرحباً، <?php echo htmlspecialchars($u['name']); ?></h2>
    <p class="text-muted">صلاحيتك: <?php echo htmlspecialchars($role); ?></p>
  </div>

  <div class="row g-4">
    <?php
    if ($isSuper || $role === 'hospital_admin') sectionCard('bi-hospital', 'المستشفى', 'إدارة الأقسام والعيادات والمواعيد', '/dashboard/hospital/index.php');
    if ($isSuper || $role === 'mall_admin') sectionCard('bi-bag', 'المول', 'إدارة المحلات والمطاعم والترفيه', '/dashboard/mall/index.php');
    if ($isSuper || $role === 'school_admin') sectionCard('bi-mortarboard', 'المدرسة', 'إدارة المراحل والطلاب', '/dashboard/school/index.php');
    if ($isSuper || $role === 'sports_admin') sectionCard('bi-dribbble', 'النادي الرياضي', 'إدارة الملاعب والمسابح والصالات', '/dashboard/sports/index.php');
    if ($isSuper || $role === 'conference_admin') sectionCard('bi-easel', 'قاعة المؤتمرات', 'حجوزات وتنظيم فعاليات', '/dashboard/conference/index.php');
    if ($isSuper || $role === 'bank_admin') sectionCard('bi-bank', 'البنك', 'خدمات الحسابات والمعاملات', '/dashboard/bank/index.php');
    if ($isSuper || $role === 'security_admin') sectionCard('bi-shield-lock', 'الأمن والطوارئ', 'البوابات، الإسعاف، الإطفاء', '/dashboard/security/index.php');
    if ($isSuper || $role === 'residential_admin') sectionCard('bi-buildings', 'السكن', 'إدارة العمارات والفيلات والوحدات والسكان', '/dashboard/residential/index.php');
    if ($isSuper || $role === 'hq_admin') sectionCard('bi-building-gear', 'المقر الإداري HQ', 'إعلانات وإدارة المدينة', '/dashboard/hq/index.php');
    if ($isSuper || $role === 'services_admin') sectionCard('bi-tools', 'الخدمات والصيانة', 'بلاغات الصيانة والحدائق', '/dashboard/services/index.php');
    if ($isSuper || $role === 'energy_admin') sectionCard('bi-sun', 'الطاقة الشمسية', 'إدارة الألواح والإنتاج', '/dashboard/energy/index.php');

    // Resident view
    if ($role === 'resident') {
      sectionCard('bi-house', 'وحدتي', 'تفاصيل الوحدة وطلبات الصيانة', '#');
      sectionCard('bi-calendar-check', 'الحجوزات', 'حجز مرافق ومواعيد', '#');
      sectionCard('bi-credit-card', 'المدفوعات', 'فواتير ورسوم', '#');
    }

    // Hospital staff
    if (in_array($role, ['doctor', 'nurse', 'hospital_staff'], true)) {
      sectionCard('bi-clipboard2-pulse', 'المهام', 'قوائم مهام ومواعيد', '#');
    }

    if ($role === 'maintenance_worker') {
      sectionCard('bi-wrench', 'أوامر العمل', 'بلاغات وصيانة', '#');
    }
    ?>
  </div>

  <div class="mt-4">
    <a href="<?php echo APP_BASE; ?>/auth/logout.php" class="btn btn-outline-light">تسجيل الخروج</a>
  </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>