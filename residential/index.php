<?php
$page_title = 'المنطقة السكنية';
require_once __DIR__ . '/../includes/auth.php';
$pdo = DB::conn();

// Get all buildings and their units
$buildings = $pdo->query("
    SELECT b.*, 
           COUNT(u.id) as total_units,
           COUNT(usr.id) as occupied_units
    FROM buildings b 
    LEFT JOIN units u ON u.building_id = b.id
    LEFT JOIN users usr ON usr.unit_id = u.id AND usr.is_active = 1
    WHERE b.type IN ('apartment_block', 'villa')
    GROUP BY b.id
    ORDER BY b.type DESC, b.label
")->fetchAll();

// Get current user's unit if logged in
$user = $_SESSION['user'] ?? null;
$myUnit = null;
if ($user && !empty($user['id'])) {
    $stmt = $pdo->prepare("
        SELECT u.*, b.type as building_type, b.label as building_label
        FROM units u 
        JOIN buildings b ON b.id = u.building_id
        WHERE u.id = ?
    ");
    $stmt->execute([(int)$user['unit_id']]);
    $myUnit = $stmt->fetch();
}

include __DIR__ . '/../includes/header.php';
?>

<main class="container section-padding">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">المنطقة السكنية</h3>
        <?php if (!empty($user)): ?>
            <?php if ($myUnit): ?>
                <a href="myunit.php" class="btn btn-gradient">
                    <i class="bi bi-house-fill"></i>
                    وحدتي السكنية
                </a>
            <?php endif; ?>
        <?php else: ?>
            <a href="/auth/login.php" class="btn btn-outline-primary">تسجيل الدخول</a>
        <?php endif; ?>
    </div>

    <?php if ($myUnit): ?>
        <div class="alert alert-info mb-4">
            <i class="bi bi-info-circle-fill"></i>
            أنت تسكن في: <?= htmlspecialchars($myUnit['unit_code']) ?>
            <a href="myunit.php" class="alert-link">عرض التفاصيل</a>
        </div>
    <?php endif; ?>

    <h5 class="mb-3">العمارات السكنية</h5>
    <div class="row g-4">
        <?php foreach ($buildings as $building): ?>
            <?php if ($building['type'] === 'apartment_block'): ?>
                <div class="col-md-6 col-lg-4" data-aos="fade-up">
                    <div class="residential-block apartment">
                        <i class="bi bi-building"></i>
                        <h5><?= htmlspecialchars($building['label']) ?></h5>
                        <div class="text-muted mb-2">
                            <?= $building['occupied_units'] ?> / <?= $building['total_units'] ?> وحدة مشغولة
                        </div>
                        <?php
                        // Get units for this building
                        $units = $pdo->prepare("
                            SELECT u.*, usr.id as has_resident 
                            FROM units u 
                            LEFT JOIN users usr ON usr.unit_id = u.id AND usr.is_active = 1
                            WHERE u.building_id = ? 
                            ORDER BY u.unit_number
                        ");
                        $units->execute([$building['id']]);
                        $units = $units->fetchAll();
                        ?>
                        <div class="residential-units">
                            <?php foreach ($units as $unit): ?>
                                <div class="residential-unit <?= $unit['has_resident'] ? 'occupied' : '' ?>" 
                                     data-unit-id="<?= (int)$unit['id'] ?>"
                                     data-unit-code="<?= htmlspecialchars($unit['unit_code']) ?>"
                                     style="cursor: pointer;"
                                     title="اضغط لعرض معلومات الوحدة">
                                    <?= htmlspecialchars($unit['unit_number']) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <h5 class="mb-3 mt-5">الفلل</h5>
    <div class="row g-4">
        <?php foreach ($buildings as $building): ?>
            <?php if ($building['type'] === 'villa'): ?>
                <div class="col-md-6 col-lg-4" data-aos="fade-up">
                    <div class="residential-block villa">
                        <i class="bi bi-house"></i>
                        <h5>فيلا <?= htmlspecialchars($building['label']) ?></h5>
                        <?php
                        // Get unit status for this villa
                        $unitStatus = $pdo->prepare("
                            SELECT u.id, u.unit_code, CASE WHEN usr.id IS NOT NULL THEN 1 ELSE 0 END as occupied
                            FROM units u 
                            LEFT JOIN users usr ON usr.unit_id = u.id AND usr.is_active = 1
                            WHERE u.building_id = ?
                        ");
                        $unitStatus->execute([$building['id']]);
                        $status = $unitStatus->fetch();
                        ?>
                        <?php if ($status): ?>
                            <div class="mt-2 <?= $status['occupied'] ? 'text-success' : 'text-muted' ?>" 
                                 data-unit-id="<?= (int)$status['id'] ?>"
                                 data-unit-code="<?= htmlspecialchars($status['unit_code']) ?>"
                                 style="cursor: pointer;"
                                 title="اضغط لعرض معلومات الوحدة">
                                <?= $status['occupied'] ? 'مشغولة' : 'متاحة' ?>
                            </div>
                        <?php else: ?>
                            <div class="mt-2 text-muted">غير متاحة</div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</main>

<!-- Modal for Unit Owner Info -->
<div class="modal fade" id="unitOwnerModal" tabindex="-1" aria-labelledby="unitOwnerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="unitOwnerModalLabel">معلومات الوحدة السكنية</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="unitOwnerContent">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">جاري التحميل...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const unitOwnerModal = new bootstrap.Modal(document.getElementById('unitOwnerModal'));
    const modalContent = document.getElementById('unitOwnerContent');
    const modalTitle = document.getElementById('unitOwnerModalLabel');
    
    // Handle clicks on apartment units
    document.querySelectorAll('.residential-unit[data-unit-id]').forEach(function(unitElement) {
        unitElement.addEventListener('click', function() {
            const unitId = this.getAttribute('data-unit-id');
            const unitCode = this.getAttribute('data-unit-code');
            showUnitOwner(unitId, unitCode);
        });
    });
    
    // Handle clicks on villa status
    document.querySelectorAll('.residential-block.villa .mt-2[data-unit-id]').forEach(function(villaElement) {
        villaElement.addEventListener('click', function() {
            const unitId = this.getAttribute('data-unit-id');
            const unitCode = this.getAttribute('data-unit-code');
            showUnitOwner(unitId, unitCode);
        });
    });
    
    function showUnitOwner(unitId, unitCode) {
        modalTitle.textContent = 'معلومات الوحدة: ' + unitCode;
        modalContent.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">جاري التحميل...</span></div></div>';
        unitOwnerModal.show();
        
        fetch('<?php echo APP_BASE; ?>/residential/get_unit_owner.php?unit_id=' + unitId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.owner_name) {
                        const registeredDate = data.registered_at ? new Date(data.registered_at).toLocaleDateString('ar-EG') : '';
                        modalContent.innerHTML = `
                            <div class="text-center">
                                <i class="bi bi-person-circle display-4 text-primary mb-3"></i>
                                <h5 class="mb-3">صاحب الوحدة</h5>
                                <p class="fs-4 fw-bold text-primary">${data.owner_name}</p>
                                ${registeredDate ? `<p class="text-muted small">تاريخ التسجيل: ${registeredDate}</p>` : ''}
                            </div>
                        `;
                    } else {
                        modalContent.innerHTML = `
                            <div class="text-center">
                                <i class="bi bi-house display-4 text-muted mb-3"></i>
                                <p class="text-muted">${data.message || 'لا يوجد سكان مسجلين في هذه الوحدة'}</p>
                            </div>
                        `;
                    }
                } else {
                    modalContent.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i>
                            ${data.message || 'حدث خطأ أثناء جلب المعلومات'}
                        </div>
                    `;
                }
            })
            .catch(error => {
                modalContent.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                        حدث خطأ أثناء الاتصال بالخادم
                    </div>
                `;
            });
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>