<?php
$page_title = 'إدارة محطات الكهرباء';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
$u = $_SESSION['user'];
if (!user_can(['super_admin', 'energy_admin'])) {
    redirect('/dashboard/index.php');
}
$pdo = DB::conn();

// Fetch power stations
$stations = $pdo->query("SELECT * FROM power_stations ORDER BY name")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>إدارة محطات الكهرباء</h2>
            <p class="text-muted">تسجيل وإدارة محطات الكهرباء في المدينة والوحدات المسؤولة عنها.</p>
        </div>
        <div>
            <a href="<?php echo APP_BASE; ?>/dashboard/energy/new_station.php" class="btn btn-gradient"><i class="bi bi-plus-lg"></i> إضافة محطة</a>
        </div>
    </div>

    <div class="row g-4">
        <?php foreach ($stations as $s): ?>
            <div class="col-md-6" data-aos="fade-up">
                <div class="feature-card text-start">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1"><?php echo htmlspecialchars($s['name']); ?></h5>
                            <div class="text-muted">الموقع: <?php echo htmlspecialchars($s['location'] ?: 'غير محدد'); ?></div>
                        </div>
                        <div>
                            <a href="<?php echo APP_BASE; ?>/dashboard/energy/edit_station.php?id=<?php echo (int)$s['id']; ?>" class="btn btn-outline-light btn-sm">تعديل</a>
                            <button class="btn btn-danger btn-sm ms-1" onclick="confirmDelete(<?php echo (int)$s['id']; ?>)">حذف</button>
                        </div>
                    </div>
                    <hr>
                    <div>
                        <strong>الوحدات المغذاة:</strong>
                        <?php
                        $units = json_decode($s['responsible_units'], true);
                        if ($units) {
                            echo '<ul>';
                            foreach ($units as $unit_id) {
                                // Fetch unit code or label
                                $stmt = $pdo->prepare("SELECT unit_code FROM units WHERE id = ?");
                                $stmt->execute([$unit_id]);
                                $unit = $stmt->fetch();
                                echo '<li>' . htmlspecialchars($unit['unit_code'] ?? 'وحدة ' . $unit_id) . '</li>';
                            }
                            echo '</ul>';
                        } else {
                            echo 'لا توجد وحدات محددة';
                        }
                        ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($stations)): ?>
            <div class="col-12">
                <div class="alert alert-secondary">لا توجد محطات بعد، قم بإضافة أول محطة.</div>
            </div>
        <?php endif; ?>
    </div>
</main>

<form id="deleteForm" method="post" action="<?php echo APP_BASE; ?>/dashboard/energy/delete_station.php" style="display: none;">
    <input type="hidden" name="id" id="deleteId">
</form>

<script>
    function confirmDelete(id) {
        if (confirm('هل أنت متأكد من حذف هذه المحطة؟ هذا الإجراء لا يمكن التراجع عنه.')) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteForm').submit();
        }
    }
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>