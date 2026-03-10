<?php
$page_title = 'إعلانات دور العبادة';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'worship_admin'])) {
    redirect('/dashboard/worship/index.php');
}

$pdo = DB::conn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $place_id = (int)($_POST['place_id'] ?? 0);
    $title_ar = trim($_POST['title_ar'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $starts_at = $_POST['starts_at'] ?? '';
    $ends_at = $_POST['ends_at'] ?? '';

    if ($place_id > 0 && $title_ar !== '' && $content !== '' && $starts_at !== '') {
        $stmt = $pdo->prepare('INSERT INTO worship_announcements(place_id, title_ar, content, starts_at, ends_at) VALUES(?,?,?,?,?)');
        try {
            $stmt->execute([$place_id, $title_ar, $content, $starts_at, $ends_at ?: null]);
            redirect('/dashboard/worship/announcements.php');
        } catch (PDOException $e) {
            $error = 'حدث خطأ في إضافة الإعلان';
        }
    }
}

$places = $pdo->query('SELECT * FROM worship_places ORDER BY type_id, name_ar')->fetchAll();
$announcements = $pdo->query('SELECT a.*, p.name_ar as place_name 
  FROM worship_announcements a
  JOIN worship_places p ON p.id = a.place_id
  ORDER BY a.starts_at DESC')->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>الإعلانات</h3>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-lg"></i> إضافة إعلان
        </button>
    </div>

    <?php if (empty($announcements)): ?>
        <div class="alert alert-info">لا توجد إعلانات مضافة</div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($announcements as $announcement): ?>
                <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($announcement['title_ar']) ?></h5>
                            <h6 class="text-muted mb-3"><?= htmlspecialchars($announcement['place_name']) ?></h6>
                            <p class="mb-3"><?= nl2br(htmlspecialchars($announcement['content'])) ?></p>
                            <p class="mb-0 small text-muted">
                                <i class="bi bi-calendar me-1"></i>
                                من <?= (new DateTime($announcement['starts_at']))->format('Y-m-d H:i') ?>
                                <?php if ($announcement['ends_at']): ?>
                                    إلى <?= (new DateTime($announcement['ends_at']))->format('Y-m-d H:i') ?>
                                <?php endif; ?>
                            </p>
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
                    <h5 class="modal-title">إضافة إعلان</h5>
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
                        <label class="form-label">عنوان الإعلان</label>
                        <input type="text" name="title_ar" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">محتوى الإعلان</label>
                        <textarea name="content" class="form-control" rows="4" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">تاريخ البداية</label>
                        <input type="datetime-local" name="starts_at" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">تاريخ النهاية (اختياري)</label>
                        <input type="datetime-local" name="ends_at" class="form-control">
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