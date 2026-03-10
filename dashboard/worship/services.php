<?php
$page_title = 'أنشطة دور العبادة';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'worship_admin'])) {
    redirect('/dashboard/worship/index.php');
}

$pdo = DB::conn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $place_id = (int)($_POST['place_id'] ?? 0);
    $name_ar = trim($_POST['name_ar'] ?? '');
    $schedule = trim($_POST['schedule'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($place_id > 0 && $name_ar !== '') {
        $stmt = $pdo->prepare('INSERT INTO worship_services(place_id, name_ar, schedule, description) VALUES(?,?,?,?)');
        try {
            $stmt->execute([$place_id, $name_ar, $schedule ?: null, $description ?: null]);
            redirect('/dashboard/worship/services.php');
        } catch (PDOException $e) {
            $error = 'حدث خطأ في إضافة النشاط';
        }
    }
}

$places = $pdo->query('SELECT * FROM worship_places ORDER BY type_id, name_ar')->fetchAll();
$services = $pdo->query('SELECT s.*, p.name_ar as place_name 
  FROM worship_services s
  JOIN worship_places p ON p.id = s.place_id
  ORDER BY p.name_ar, s.name_ar')->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>الأنشطة والخدمات</h3>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-lg"></i> إضافة نشاط
        </button>
    </div>

    <?php if (empty($services)): ?>
        <div class="alert alert-info">لا توجد أنشطة مضافة</div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($services as $service): ?>
                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($service['name_ar']) ?></h5>
                            <h6 class="text-muted mb-3"><?= htmlspecialchars($service['place_name']) ?></h6>
                            <?php if ($service['schedule']): ?>
                                <p class="mb-2">
                                    <i class="bi bi-calendar3 me-2"></i>
                                    <?= htmlspecialchars($service['schedule']) ?>
                                </p>
                            <?php endif; ?>
                            <?php if ($service['description']): ?>
                                <p class="mb-0"><?= nl2br(htmlspecialchars($service['description'])) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">إضافة نشاط</h5>
                    <button type="button" class="btn-close ms-0 me-2" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">دار العبادة</label>
                        <select name="place_id" class="form-select" required>
                            <option value="">اختر دار العبادة</option>
                            <?php foreach ($places as $place): ?>
                                <option value="<?= $place['id'] ?>"><?= htmlspecialchars($place['name_ar']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">اسم النشاط</label>
                        <input type="text" name="name_ar" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">المواعيد</label>
                        <input type="text" name="schedule" class="form-control" placeholder="مثال: كل يوم جمعة الساعة 4 عصراً">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">الوصف</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>