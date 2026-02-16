<?php
$page_title = 'تصنيفات المحلات';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin', 'mall_admin'])) {
    redirect('/dashboard/index.php');
}
require_once __DIR__ . '/../../backend/config.php';
require_once __DIR__ . '/../../backend/database.php';
$pdo = Database::getInstance();

// Handle category creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add' && !empty($_POST['name_ar'])) {
            $stmt = $pdo->prepare('INSERT INTO mall_categories (name_ar) VALUES (?)');
            try {
                $stmt->execute([trim($_POST['name_ar'])]);
                $_SESSION['success'] = 'تم إضافة التصنيف بنجاح';
            } catch (PDOException $e) {
                $_SESSION['error'] = 'حدث خطأ أثناء إضافة التصنيف';
            }
        } elseif ($_POST['action'] === 'delete' && !empty($_POST['category_id'])) {
            $stmt = $pdo->prepare('DELETE FROM mall_categories WHERE id = ? AND NOT EXISTS (SELECT 1 FROM mall_tenants WHERE category_id = ?)');
            try {
                $stmt->execute([$_POST['category_id'], $_POST['category_id']]);
                if ($stmt->rowCount() > 0) {
                    $_SESSION['success'] = 'تم حذف التصنيف بنجاح';
                } else {
                    $_SESSION['error'] = 'لا يمكن حذف التصنيف لارتباطه بمحلات';
                }
            } catch (PDOException $e) {
                $_SESSION['error'] = 'حدث خطأ أثناء حذف التصنيف';
            }
        }
    }
    redirect(APP_BASE . '/dashboard/mall/categories.php');
}

// Get all categories with usage count
$categories = $pdo->query('
    SELECT c.*, COUNT(t.id) as usage_count 
    FROM mall_categories c 
    LEFT JOIN mall_tenants t ON c.id = t.category_id 
    GROUP BY c.id 
    ORDER BY c.name_ar
')->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<main class="container section-padding">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">تصنيفات المحلات</h3>
        <a href="<?php echo APP_BASE; ?>/dashboard/mall/index.php" class="btn btn-outline-light">رجوع</a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success'];
                                            unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error'];
                                        unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4">
            <div class="feature-card">
                <h5 class="mb-3">إضافة تصنيف جديد</h5>
                <form method="post">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">اسم التصنيف</label>
                        <input type="text" name="name_ar" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-gradient">إضافة</button>
                </form>
            </div>
        </div>
        <div class="col-md-8">
            <div class="feature-card">
                <h5 class="mb-3">التصنيفات الحالية</h5>
                <div class="table-responsive">
                    <table class="table table-dark">
                        <thead>
                            <tr>
                                <th>التصنيف</th>
                                <th>عدد المحلات</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($category['name_ar']); ?></td>
                                    <td><?php echo $category['usage_count']; ?></td>
                                    <td>
                                        <?php if ($category['usage_count'] == 0): ?>
                                            <form method="post" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا التصنيف؟')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>