<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
include __DIR__ . '/../includes/header.php';
$page_title = 'المراحل';
?>
<main class="container section-padding">
    <h2 class="mb-4">المراحل الدراسية</h2>
    <p class="text-white">نظرة عامة على المراحل: الحضانة، الابتدائي، الإعدادي، والثانوي.</p>

    <?php
    $pdo = DB::conn();
    $stages = $pdo->query('SELECT * FROM school_stages ORDER BY id')->fetchAll();
    ?>

    <div class="row g-4 mt-3">
        <?php foreach ($stages as $st): ?>
            <div class="col-md-6" data-aos="fade-up">
                <div class="feature-card">
                    <h5><?php echo htmlspecialchars($st['name_ar']); ?></h5>
                    <?php
                    $classes = $pdo->prepare('SELECT id, name_ar FROM school_classes WHERE stage_id = ? ORDER BY name_ar');
                    $classes->execute([(int)$st['id']]);
                    $cls = $classes->fetchAll();
                    ?>
                    <p class="mb-0">
                        <?php if (empty($cls)): ?>
                            لا توجد صفوف مضافة بعد لهذه المرحلة.
                        <?php else: ?>
                            الصفوف: <?php echo htmlspecialchars(implode(', ', array_map(fn($c) => $c['name_ar'], $cls))); ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php';
