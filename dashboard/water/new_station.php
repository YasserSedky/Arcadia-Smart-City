<?php
$page_title = 'إضافة محطة مياه جديدة';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
$u = $_SESSION['user'];
if (!user_can(['super_admin', 'water_admin'])) {
    redirect('/dashboard/index.php');
}
$pdo = DB::conn();

// Fetch all units for selection
$units = $pdo->query("SELECT id, unit_code FROM units ORDER BY unit_code")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $supplied_units = $_POST['supplied_units'] ?? [];

    if (empty($name)) {
        $_SESSION['error'] = 'يرجى إدخال اسم المحطة';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO water_stations (name, location, supplied_units) VALUES (?, ?, ?)");
            $stmt->execute([$name, $location, json_encode($supplied_units)]);
            $_SESSION['success'] = 'تم إضافة المحطة بنجاح';
            redirect('/dashboard/water/index.php');
        } catch (PDOException $e) {
            $_SESSION['error'] = 'خطأ في الحفظ: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
    <div class="mb-4">
        <h2>إضافة محطة مياه جديدة</h2>
        <a href="<?php echo APP_BASE; ?>/dashboard/water/index.php" class="btn btn-outline-light">العودة</a>
    </div>

    <form method="post" class="feature-card">
        <div class="mb-3">
            <label for="name" class="form-label">اسم المحطة *</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="mb-3">
            <label for="location" class="form-label">الموقع</label>
            <input type="text" class="form-control" id="location" name="location">
        </div>
        <div class="mb-3">
            <label class="form-label">الوحدات المغذاة</label>
            <div class="row">
                <?php foreach ($units as $unit): ?>
                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="unit_<?php echo $unit['id']; ?>" name="supplied_units[]" value="<?php echo $unit['id']; ?>">
                            <label class="form-check-label" for="unit_<?php echo $unit['id']; ?>">
                                <?php echo htmlspecialchars($unit['unit_code']); ?>
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <button type="submit" class="btn btn-gradient">إضافة المحطة</button>
    </form>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>