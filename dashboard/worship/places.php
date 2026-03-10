<?php
$page_title = 'دور العبادة';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'worship_admin'])) {
    redirect('/dashboard/worship/index.php');
}

$pdo = DB::conn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type_id = (int)($_POST['type_id'] ?? 0);
    $name_ar = trim($_POST['name_ar'] ?? '');
    $capacity = (int)($_POST['capacity'] ?? 0);
    $location = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $prayer_times = $_POST['prayer_times'] ?? null;

    if ($type_id > 0 && $name_ar !== '') {
        $stmt = $pdo->prepare('INSERT INTO worship_places(type_id, name_ar, capacity, location, description, prayer_times) VALUES(?,?,?,?,?,?)');
        try {
            $stmt->execute([$type_id, $name_ar, $capacity, $location ?: null, $description ?: null, $prayer_times ? json_encode($prayer_times) : null]);
            redirect('/dashboard/worship/places.php');
        } catch (PDOException $e) {
            $error = 'حدث خطأ في إضافة دار العبادة';
        }
    }
}

$types = $pdo->query('SELECT * FROM worship_types ORDER BY id')->fetchAll();
$places = $pdo->query('SELECT p.*, t.name_ar as type_name 
  FROM worship_places p 
  JOIN worship_types t ON t.id = p.type_id 
  ORDER BY p.type_id, p.name_ar')->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>دور العبادة</h3>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-lg"></i> إضافة دار عبادة
        </button>
    </div>

    <?php if (empty($places)): ?>
        <div class="alert alert-info">لا توجد دور عبادة مضافة</div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($places as $place): ?>
                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($place['name_ar']) ?></h5>
                            <h6 class="text-muted mb-3"><?= htmlspecialchars($place['type_name']) ?></h6>
                            <?php if ($place['capacity']): ?>
                                <p class="mb-2">
                                    <i class="bi bi-people me-2"></i>
                                    السعة: <?= number_format($place['capacity']) ?> شخص
                                </p>
                            <?php endif; ?>
                            <?php if ($place['location']): ?>
                                <p class="mb-2">
                                    <i class="bi bi-geo-alt me-2"></i>
                                    <?= htmlspecialchars($place['location']) ?>
                                </p>
                            <?php endif; ?>
                            <?php if ($place['description']): ?>
                                <p class="mb-2"><?= nl2br(htmlspecialchars($place['description'])) ?></p>
                            <?php endif; ?>
                            <?php if ($place['prayer_times']):
                                $times = json_decode($place['prayer_times'], true);
                                if ($times): ?>
                                    <div class="mt-3">
                                        <h6 class="mb-2">مواقيت الصلاة:</h6>
                                        <div class="row g-2">
                                            <?php foreach ($times as $prayer => $time): ?>
                                                <div class="col-6">
                                                    <small class="text-muted"><?= htmlspecialchars($prayer) ?>:</small>
                                                    <br><?= htmlspecialchars($time) ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
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
                    <h5 class="modal-title">إضافة دار عبادة</h5>
                    <button type="button" class="btn-close ms-0 me-2" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">النوع</label>
                        <select name="type_id" class="form-select" required>
                            <option value="">اختر النوع</option>
                            <?php foreach ($types as $type): ?>
                                <option value="<?= $type['id'] ?>"><?= htmlspecialchars($type['name_ar']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">الاسم</label>
                        <input type="text" name="name_ar" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">السعة</label>
                        <input type="number" name="capacity" class="form-control" min="0">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">الموقع</label>
                        <input type="text" name="location" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">الوصف</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="mb-3" id="prayerTimesSection" style="display:none">
                        <label class="form-label">مواقيت الصلاة</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="time" name="prayer_times[الفجر]" class="form-control" placeholder="الفجر">
                            </div>
                            <div class="col-6">
                                <input type="time" name="prayer_times[الظهر]" class="form-control" placeholder="الظهر">
                            </div>
                            <div class="col-6">
                                <input type="time" name="prayer_times[العصر]" class="form-control" placeholder="العصر">
                            </div>
                            <div class="col-6">
                                <input type="time" name="prayer_times[المغرب]" class="form-control" placeholder="المغرب">
                            </div>
                            <div class="col-6">
                                <input type="time" name="prayer_times[العشاء]" class="form-control" placeholder="العشاء">
                            </div>
                        </div>
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

<script>
    document.querySelector('select[name="type_id"]').addEventListener('change', function() {
        // Show prayer times section only for mosques (type_id = 1)
        document.getElementById('prayerTimesSection').style.display =
            this.value === '1' ? 'block' : 'none';
    });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>