<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../backend/config.php';
require_once __DIR__ . '/../backend/database.php';
include __DIR__ . '/../includes/header.php';

$page_title = 'وحدات المول';
$pdo = Database::getInstance();

// Get only available units (not rented)
$sql = "SELECT u.* 
        FROM mall_units u 
        LEFT JOIN mall_tenants t ON u.id = t.unit_id 
        WHERE t.id IS NULL
        ORDER BY u.level, u.code";
$stmt = $pdo->query($sql);
$units = $stmt->fetchAll();

// Group units by level
$levels = [];
foreach ($units as $unit) {
    $levels[$unit['level']][] = $unit;
}
?>
<main class="container section-padding">
    <h2 class="mb-4">الوحدات والمساحات</h2>
    <p class="text-white mb-4">استعرض الوحدات (مساحات التأجير) في المول مع المساحة والحالة.</p>

    <div class="accordion" id="levelsAccordion">
        <?php foreach ($levels as $level => $levelUnits): ?>
            <div class="accordion-item bg-dark mb-3">
                <h2 class="accordion-header">
                    <button class="accordion-button bg-dark text-white" type="button" data-bs-toggle="collapse" data-bs-target="#level<?php echo md5($level); ?>">
                        <?php echo htmlspecialchars($level); ?>
                    </button>
                </h2>
                <div id="level<?php echo md5($level); ?>" class="accordion-collapse collapse show">
                    <div class="accordion-body">
                        <div class="row g-4">
                            <?php foreach ($levelUnits as $unit): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="feature-card h-100">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <h5 class="unit-code">وحدة <?php echo htmlspecialchars($unit['code']); ?></h5>
                                            <span class="badge bg-success">متاحة</span>
                                        </div>
                                        <p class="mb-2">المساحة: <?php echo htmlspecialchars($unit['area_sqm']); ?> م²</p>
                                        <p class="mb-2">الطابق: <?php echo htmlspecialchars($unit['level']); ?></p>
                                        <div class="mt-3">
                                            <a href="<?php echo APP_BASE; ?>/mall/request_unit.php?unit_id=<?php echo $unit['id']; ?>"
                                                class="btn btn-outline-light w-100">
                                                تقديم طلب إيجار
                                            </a>
                                        </div>
                                        <p class="mb-0">
                                            النوع:
                                            <?php
                                            $types = [
                                                'shop' => 'محل تجاري',
                                                'barber_male' => 'حلاق رجالي',
                                                'barber_female' => 'كوافير نسائي',
                                                'restaurant' => 'مطعم',
                                                'cafe' => 'كافيه',
                                                'kiosk' => 'كشك',
                                                'cinema' => 'سينما',
                                                'gaming' => 'صالة ألعاب',
                                                'furniture' => 'معرض أثاث',
                                                'electronics' => 'أجهزة كهربائية'
                                            ];
                                            echo $types[$unit['type']] ?? $unit['type'];
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php';
