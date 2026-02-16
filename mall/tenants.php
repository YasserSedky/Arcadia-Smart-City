<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../backend/config.php';
require_once __DIR__ . '/../backend/database.php';
include __DIR__ . '/../includes/header.php';

$page_title = 'محلات المول';
$pdo = Database::getInstance();

// Get all occupied units with tenant information
$sql = "SELECT t.*, u.code, u.level, u.area_sqm, u.type, c.name_ar as category_name 
        FROM mall_tenants t 
        JOIN mall_units u ON u.id = t.unit_id 
        LEFT JOIN mall_categories c ON t.category_id = c.id 
        ORDER BY u.level, u.code";
$stmt = $pdo->query($sql);
$tenants = $stmt->fetchAll();

// Group tenants by level for better organization
$levels = [];
foreach ($tenants as $tenant) {
    $levels[$tenant['level']][] = $tenant;
}
?>
<main class="container section-padding">
    <h2 class="mb-4">محلات المول</h2>
    <p class="text-white mb-4">قائمة المحلات المشغولة في المول مع معلومات التواصل والنشاط التجاري.</p>

    <div class="accordion" id="levelsAccordion">
        <?php foreach ($levels as $level => $levelTenants): ?>
            <div class="accordion-item bg-dark mb-3">
                <h2 class="accordion-header">
                    <button class="accordion-button bg-dark text-white" type="button" data-bs-toggle="collapse" data-bs-target="#level<?php echo md5($level); ?>">
                        الطابق: <?php echo htmlspecialchars($level); ?>
                    </button>
                </h2>
                <div id="level<?php echo md5($level); ?>" class="accordion-collapse collapse show">
                    <div class="accordion-body">
                        <div class="row g-4">
                            <?php foreach ($levelTenants as $tenant): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="feature-card h-100">
                                        <div class="mb-3">
                                            <h5 class="unit-code">وحدة <?php echo htmlspecialchars($tenant['code']); ?></h5>
                                        </div>
                                        <h6 class="mb-2 text-white"><?php echo htmlspecialchars($tenant['name_ar']); ?></h6>
                                        <p class="mb-2"><i class="fas fa-store-alt me-2"></i> <?php echo htmlspecialchars($tenant['category_name']); ?></p>
                                        <?php if ($tenant['phone']): ?>
                                            <p class="mb-2"><i class="fas fa-phone me-2"></i> <?php echo htmlspecialchars($tenant['phone']); ?></p>
                                        <?php endif; ?>
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
