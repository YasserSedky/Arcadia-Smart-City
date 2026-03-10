<?php
$page_title = 'تعديل محطة كهرباء';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
$u = $_SESSION['user'];
if (!user_can(['super_admin', 'energy_admin'])) {
    redirect('/dashboard/index.php');
}
$pdo = DB::conn();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM power_stations WHERE id = ?");
$stmt->execute([$id]);
$station = $stmt->fetch();
if (!$station) {
    $_SESSION['error'] = 'المحطة غير موجودة';
    redirect('/dashboard/energy/index.php?tab=stations');
}

// Fetch all units
$units = $pdo->query("SELECT id, unit_code FROM units ORDER BY unit_code")->fetchAll();
$responsible_units = json_decode($station['responsible_units'], true) ?: [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $responsible_units_post = $_POST['responsible_units'] ?? [];

    if (empty($name)) {
        $_SESSION['error'] = 'يرجى إدخال اسم المحطة';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE power_stations SET name = ?, location = ?, responsible_units = ? WHERE id = ?");
            $stmt->execute([$name, $location, json_encode($responsible_units_post), $id]);
            $_SESSION['success'] = 'تم تحديث المحطة بنجاح';
            redirect('/dashboard/energy/index.php?tab=stations');
        } catch (PDOException $e) {
            $_SESSION['error'] = 'خطأ في الحفظ: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
    <div class="mb-4">
        <h2>تعديل محطة كهرباء</h2>
        <a href="<?php echo APP_BASE; ?>/dashboard/energy/stations.php" class="btn btn-outline-light">العودة</a>
    </div>

    <form method="post" class="feature-card">
        <div class="mb-3">
            <label for="name" class="form-label">اسم المحطة</label>
            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($station['name']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="location" class="form-label">الموقع</label>
            <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($station['location']); ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">الوحدات المغذاة</label>
            <div class="row">
                <?php foreach ($units as $unit): ?>
                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="unit_<?php echo $unit['id']; ?>" name="responsible_units[]" value="<?php echo $unit['id']; ?>" <?php echo in_array($unit['id'], $responsible_units) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="unit_<?php echo $unit['id']; ?>">
                                <?php echo htmlspecialchars($unit['unit_code']); ?>
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-gradient">تحديث المحطة</button>
            <button type="button" class="btn btn-danger" onclick="confirmDelete()">حذف المحطة</button>
        </div>
        <form id="deleteForm" method="post" action="<?php echo APP_BASE; ?>/dashboard/energy/delete_station.php" style="display: none;">
            <input type="hidden" name="id" value="<?php echo (int)$station['id']; ?>">
        </form>
    </form>

    <script>
        function confirmDelete() {
            if (confirm('هل أنت متأكد من حذف هذه المحطة؟ هذا الإجراء لا يمكن التراجع عنه.')) {
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>