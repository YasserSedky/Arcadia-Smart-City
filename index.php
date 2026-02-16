<?php
require_once __DIR__ . '/backend/config.php';
ensure_session();
// Get user from session
$user = null;
if (isset($_SESSION['user']) && is_array($_SESSION['user']) && !empty($_SESSION['user']['id'])) {
    $user = $_SESSION['user'];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Arcadia Smart City</title>
    <meta name="description" content="مدينة ذكية متكاملة بتصميم حديث ومستقبلي">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- AOS -->
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo APP_BASE; ?>/assets/css/style.css" rel="stylesheet">
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg sticky-top glass-nav">
        <div class="container">
            <div>
                <a class="navbar-brand fw-bold text-gradient" href="#home">Arcadia Smart City</a>
                <div class="small text-muted" style="font-size:12px;">مدينتك الذكية المتكاملة</div>
            </div>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="#about">عن المدينة</a></li>
                    <li class="nav-item"><a class="nav-link" href="#hospital">المستشفى</a></li>
                    <li class="nav-item"><a class="nav-link" href="#mall">المول</a></li>
                    <li class="nav-item"><a class="nav-link" href="#school">المدرسة</a></li>
                    <li class="nav-item"><a class="nav-link" href="#worship">دور العبادة</a></li>
                    <li class="nav-item"><a class="nav-link" href="#sports">النادي الرياضي</a></li>
                    <li class="nav-item"><a class="nav-link" href="#conference">قاعة المؤتمرات</a></li>
                    <li class="nav-item"><a class="nav-link" href="#bank">البنك</a></li>
                    <li class="nav-item"><a class="nav-link" href="#security">الأمن والطوارئ</a></li>
                    <li class="nav-item"><a class="nav-link" href="#residential">السكن</a></li>
                    <li class="nav-item"><a class="nav-link" href="#services">الخدمات</a></li>
                    <?php if (!empty($user)): ?>
                        <li class="nav-item dropdown ms-lg-2">
                            <a class="btn btn-gradient dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle"></i> <?= htmlspecialchars($user['name']) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo APP_BASE; ?>/dashboard/index.php"><i class="bi bi-speedometer2"></i> لوحة التحكم</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="<?php echo APP_BASE; ?>/auth/logout.php"><i class="bi bi-box-arrow-right"></i> تسجيل الخروج</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="btn btn-gradient ms-lg-2" href="<?php echo APP_BASE; ?>/auth/login.php">تسجيل الدخول</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <header id="home" class="hero d-flex align-items-center">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-lg-6" data-aos="fade-up">
                    <h1 class="display-5 fw-bold">مدينة ذكية متكاملة لمستقبل أفضل</h1>
                    <p class="lead mt-3 hero-subtitle">Arcadia تجمع بين الصحة، التعليم، الترفيه، السكن والخدمات تحت منظومة واحدة متصلة.</p>
                    <div class="mt-4 d-flex gap-3 flex-wrap">
                        <a href="#about" class="btn btn-lg btn-gradient">اكتشف أكثر</a>
                        <a href="<?php echo APP_BASE; ?>/auth/register.php" class="btn btn-lg btn-outline-light">إنشاء حساب</a>
                    </div>
                </div>
                <div class="col-lg-6" data-aos="fade-up" data-aos-delay="150">
                    <div class="hero-visual"></div>
                </div>
            </div>
        </div>
    </header>

    <!-- About -->
    <section id="about" class="section-padding">
        <div class="container">
            <div class="section-head" data-aos="fade-up">
                <h2>عن Arcadia</h2>
                <p>مدينة ذكية متصلة بالبنية التحتية الرقمية، تضمن جودة حياة عالية لكافة السكان والعاملين والزوار.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-3" data-aos="zoom-in">
                    <div class="feature-card">
                        <i class="bi bi-hospital"></i>
                        <h5>رعاية صحية</h5>
                        <p>مستشفى متكامل بكل التخصصات وصيدلية وغرف عمليات وطوارئ.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-aos="zoom-in" data-aos-delay="100">
                    <div class="feature-card">
                        <i class="bi bi-bag"></i>
                        <h5>تسوق وترفيه</h5>
                        <p>مول متكامل بمحلات ومطاعم وكافيهات وسينما وصالات ألعاب.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-aos="zoom-in" data-aos-delay="200">
                    <div class="feature-card">
                        <i class="bi bi-mortarboard"></i>
                        <h5>تعليم متكامل</h5>
                        <p>مدرسة من الحضانة وحتى الثانوي بمعايير عالمية.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-aos="zoom-in" data-aos-delay="300">
                    <div class="feature-card">
                        <i class="bi bi-cpu"></i>
                        <h5>تحكم ذكي</h5>
                        <p>لوحات تحكم مقسمة لكل إدارة مع صلاحيات دقيقة.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Hospital -->
    <section id="hospital" class="section-padding bg-contrast">
        <div class="container">
            <div class="section-head" data-aos="fade-up">
                <h2>المستشفى</h2>
                <p>تخصصات كاملة: عيادات خارجية، صيدلية داخلية، غرف عمليات، استقبال وطوارئ، تمريض، نظافة.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-3" data-aos="fade-up">
                    <div class="info-card">
                        <h6>العيادات</h6>
                        <p>جميع التخصصات</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
                    <div class="info-card">
                        <h6>الصيدلية</h6>
                        <p>صرف آلي متكامل</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
                    <div class="info-card">
                        <h6>العمليات</h6>
                        <p>أجنحة متقدمة</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
                    <div class="info-card">
                        <h6>التمريض والطوارئ</h6>
                        <p>جاهزية 24/7</p>
                    </div>
                </div>
            </div>
            <div class="text-center mt-4" data-aos="fade-up">
                <a href="<?php echo APP_BASE; ?>/hospital/index.php" class="btn btn-lg btn-gradient">زيارة قسم المستشفى</a>
            </div>
        </div>
    </section>

    <!-- Mall -->
    <section id="mall" class="section-padding">
        <div class="container">
            <div class="section-head" data-aos="fade-up">
                <h2>المول</h2>
                <p>محلات تجارية، حلاقة رجالي وحريمي، مطاعم وكافيهات، سينما، وصالات ألعاب.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4" data-aos="zoom-in">
                    <div class="info-card">
                        <h6>محلات</h6>
                        <p>أزياء، إلكترونيات، وغيرها</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="zoom-in" data-aos-delay="150">
                    <div class="info-card">
                        <h6>مطاعم وكافيهات</h6>
                        <p>مأكولات عالمية</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="zoom-in" data-aos-delay="300">
                    <div class="info-card">
                        <h6>ترفيه</h6>
                        <p>سينما وألعاب</p>
                    </div>
                </div>
            </div>
            <div class="text-center mt-4" data-aos="fade-up">
                <a href="<?php echo APP_BASE; ?>/mall/index.php" class="btn btn-lg btn-gradient">زيارة قسم المول</a>
            </div>
        </div>
    </section>

    <!-- School -->
    <section id="school" class="section-padding bg-contrast">
        <div class="container">
            <div class="section-head" data-aos="fade-up">
                <h2>المدرسة</h2>
                <p>حضانة - ابتدائي - إعدادي - ثانوي مع مختبرات وملاعب.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-3" data-aos="fade-up">
                    <div class="info-card">
                        <h6>المراحل الدراسية</h6>
                        <p>حضانة حتى الثانوي</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
                    <div class="info-card">
                        <h6>الصفوف والمناهج</h6>
                        <p>تعليم متكامل</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
                    <div class="info-card">
                        <h6>المختبرات</h6>
                        <p>علوم وتكنولوجيا</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
                    <div class="info-card">
                        <h6>الملاعب والأنشطة</h6>
                        <p>تنمية شاملة</p>
                    </div>
                </div>
            </div>
            <div class="text-center mt-4" data-aos="fade-up">
                <a href="<?php echo APP_BASE; ?>/school/index.php" class="btn btn-lg btn-gradient">زيارة قسم المدرسة</a>
            </div>
        </div>
    </section>

    <!-- Worship -->
    <section id="worship" class="section-padding">
        <div class="container">
            <div class="section-head" data-aos="fade-up">
                <h2>دور العبادة</h2>
                <p>جامع وكنيسة لخدمة المجتمع.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-6" data-aos="zoom-in">
                    <div class="info-card">
                        <h6>مسجد</h6>
                        <p>صلوات وأنشطة دينية</p>
                    </div>
                </div>
                <div class="col-md-6" data-aos="zoom-in" data-aos-delay="150">
                    <div class="info-card">
                        <h6>كنيسة</h6>
                        <p>قداسات وخدمات روحية</p>
                    </div>
                </div>
            </div>
            <div class="text-center mt-4" data-aos="fade-up">
                <a href="<?php echo APP_BASE; ?>/worship/index.php" class="btn btn-lg btn-gradient">زيارة قسم دور العبادة</a>
            </div>
        </div>
    </section>

    <!-- Sports -->
    <section id="sports" class="section-padding bg-contrast">
        <div class="container">
            <div class="section-head" data-aos="fade-up">
                <h2>النادي الرياضي</h2>
                <p>ملاعب متعددة، مسابح وصالات رياضية.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4" data-aos="zoom-in">
                    <div class="info-card">
                        <h6>الملاعب</h6>
                        <p>كرة قدم، سلة، تنس</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="zoom-in" data-aos-delay="150">
                    <div class="info-card">
                        <h6>المسبح</h6>
                        <p>مسبح أولمبي مغطى</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="zoom-in" data-aos-delay="300">
                    <div class="info-card">
                        <h6>الصالات</h6>
                        <p>لياقة وسكواش</p>
                    </div>
                </div>
            </div>
            <div class="text-center mt-4" data-aos="fade-up">
                <a href="<?php echo APP_BASE; ?>/sports/index.php" class="btn btn-lg btn-gradient">زيارة النادي الرياضي</a>
            </div>
        </div>
    </section>

    <!-- Conference -->
    <section id="conference" class="section-padding">
        <div class="container">
            <div class="section-head" data-aos="fade-up">
                <h2>قاعة المؤتمرات والمناسبات</h2>
                <p>جاهزة للفعاليات والاجتماعات الكبرى.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-3" data-aos="fade-up">
                    <div class="info-card">
                        <h6>القاعات</h6>
                        <p>متعددة الأحجام</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
                    <div class="info-card">
                        <h6>الحجوزات</h6>
                        <p>حجز سهل ومباشر</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
                    <div class="info-card">
                        <h6>الفعاليات</h6>
                        <p>مؤتمرات ومناسبات</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
                    <div class="info-card">
                        <h6>التجهيزات</h6>
                        <p>أحدث المعدات</p>
                    </div>
                </div>
            </div>
            <div class="text-center mt-4" data-aos="fade-up">
                <a href="<?php echo APP_BASE; ?>/conference/index.php" class="btn btn-lg btn-gradient">زيارة قاعة المؤتمرات والفعاليات</a>
            </div>
        </div>
    </section>

    <!-- Bank -->
    <section id="bank" class="section-padding bg-contrast">
        <div class="container">
            <div class="section-head" data-aos="fade-up">
                <h2>البنك</h2>
                <p>خدمات مصرفية متكاملة للسكان والزوار.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-3" data-aos="fade-up">
                    <div class="info-card">
                        <h6>الحسابات</h6>
                        <p>فتح وإدارة الحسابات</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
                    <div class="info-card">
                        <h6>المعاملات</h6>
                        <p>تحويلات ومدفوعات</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
                    <div class="info-card">
                        <h6>الحجوزات</h6>
                        <p>مواعيد مع موظفي البنك</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
                    <div class="info-card">
                        <h6>الخدمات</h6>
                        <p>قروض واستفسارات</p>
                    </div>
                </div>
            </div>
            <div class="text-center mt-4" data-aos="fade-up">
                <a href="<?php echo APP_BASE; ?>/bank/index.php" class="btn btn-lg btn-gradient">زيارة قسم البنك</a>
            </div>
        </div>
    </section>

    <!-- Security & Emergency -->
    <section id="security" class="section-padding">
        <div class="container">
            <div class="section-head" data-aos="fade-up">
                <h2>الأمن والطوارئ</h2>
                <p>نقطة شرطة، إطفاء، إسعاف، وحراسات للبوابات (6 بوابات).</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4" data-aos="zoom-in">
                    <div class="info-card">
                        <h6>البوابات</h6>
                        <p>6 بوابات رئيسية مؤمنة</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="zoom-in" data-aos-delay="150">
                    <div class="info-card">
                        <h6>الطوارئ</h6>
                        <p>خدمة 24/7</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="zoom-in" data-aos-delay="300">
                    <div class="info-card">
                        <h6>نظام البلاغات</h6>
                        <p>إبلاغ فوري عن الحوادث</p>
                    </div>
                </div>
            </div>
            <div class="text-center mt-4" data-aos="fade-up">
                <a href="<?php echo APP_BASE; ?>/security/index.php" class="btn btn-lg btn-gradient">زيارة قسم الأمن والطوارئ</a>
            </div>
        </div>
    </section>

    <!-- Residential -->
    <section id="residential" class="section-padding bg-contrast">
        <div class="container">
            <div class="section-head" data-aos="fade-up">
                <h2>المنطقة السكنية</h2>
                <p>20 عمارة (كل عمارة 6 شقق) و80 فيلا مع ترقيم موحّد في قاعدة البيانات.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-3" data-aos="fade-up">
                    <div class="info-card">
                        <h6>العمارات</h6>
                        <p>20 عمارة سكنية</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
                    <div class="info-card">
                        <h6>الشقق</h6>
                        <p>6 شقق لكل عمارة</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
                    <div class="info-card">
                        <h6>الفلل</h6>
                        <p>80 فيلا مستقلة</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
                    <div class="info-card">
                        <h6>الإدارة</h6>
                        <p>نظام إدارة متكامل</p>
                    </div>
                </div>
            </div>
            <div class="text-center mt-4" data-aos="fade-up">
                <a href="<?php echo APP_BASE; ?>/residential/index.php" class="btn btn-lg btn-gradient">زيارة المنطقة السكنية</a>
            </div>
        </div>
    </section>

    <!-- Services -->
    <section id="services" class="section-padding">
        <div class="container">
            <div class="section-head" data-aos="fade-up">
                <h2>الخدمات</h2>
                <p>صيانة متكاملة، حدائق، وإدارة بوابات.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-3" data-aos="fade-up">
                    <div class="info-card">
                        <h6>الصيانة</h6>
                        <p>بلاغات وإصلاحات</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
                    <div class="info-card">
                        <h6>الحدائق</h6>
                        <p>عناية بالمسطحات الخضراء</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
                    <div class="info-card">
                        <h6>البوابات</h6>
                        <p>إدارة وحراسة</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
                    <div class="info-card">
                        <h6>الخدمات العامة</h6>
                        <p>نظافة وصيانة</p>
                    </div>
                </div>
            </div>
            <div class="text-center mt-4" data-aos="fade-up">
                <a href="<?php echo APP_BASE; ?>/services/index.php" class="btn btn-lg btn-gradient">زيارة قسم الخدمات</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer text-center py-4">
        <div class="container">
            <p class="mb-1">© <span id="year"></span> Arcadia Smart City</p>
            <div class="d-flex gap-3 justify-content-center">
                <a href="#home" class="link-light"><i class="bi bi-arrow-up"></i></a>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="<?php echo APP_BASE; ?>/assets/js/main.js"></script>
</body>

</html>
