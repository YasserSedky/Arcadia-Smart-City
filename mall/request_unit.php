<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../backend/config.php';
require_once __DIR__ . '/../backend/database.php';

$page_title = 'طلب إيجار وحدة';
$pdo = Database::getInstance();

// Get unit information
$unit_id = isset($_GET['unit_id']) ? (int)$_GET['unit_id'] : 0;
$unit = null;

if ($unit_id > 0) {
    $stmt = $pdo->prepare("
        SELECT u.* 
        FROM mall_units u 
        LEFT JOIN mall_tenants t ON u.id = t.unit_id 
        WHERE u.id = ? AND t.id IS NULL
    ");
    $stmt->execute([$unit_id]);
    $unit = $stmt->fetch();
}

if (!$unit) {
    $_SESSION['error'] = 'الوحدة غير متوفرة للإيجار';
    redirect(APP_BASE . '/mall/units.php');
}

// Get categories for dropdown
$categories = $pdo->query("SELECT id, name_ar FROM mall_categories ORDER BY name_ar")->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $applicant_name = trim($_POST['applicant_name'] ?? '');
        $business_name = trim($_POST['business_name'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $commercial_register = trim($_POST['commercial_register'] ?? '');
        $business_description = trim($_POST['business_description'] ?? '');

        // Validate required fields
        if (!$applicant_name || !$business_name || !$phone || !$email) {
            throw new Exception('جميع الحقول المطلوبة يجب تعبئتها');
        }

        // Insert request
        $stmt = $pdo->prepare('
            INSERT INTO mall_rental_requests 
            (unit_id, applicant_name, business_name, category_id, phone, email, commercial_register, business_description)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $unit_id,
            $applicant_name,
            $business_name,
            $category_id ?: null,
            $phone,
            $email,
            $commercial_register ?: null,
            $business_description ?: null
        ]);

        $_SESSION['success'] = 'تم تقديم طلب الإيجار بنجاح. سيتم التواصل معكم قريباً.';
        redirect(APP_BASE . '/mall/units.php');
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

include __DIR__ . '/../includes/header.php';
?>

<main class="container section-padding">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">طلب إيجار وحدة</h2>
        <a href="<?php echo APP_BASE; ?>/mall/units.php" class="btn btn-outline-light">رجوع</a>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php echo $_SESSION['error'];
            unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="feature-card">
                <h5 class="mb-4">معلومات طلب الإيجار</h5>

                <form method="post" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">اسم مقدم الطلب <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="applicant_name" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">اسم المتجر/النشاط التجاري <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="business_name" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">رقم الجوال <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control" name="phone" required
                            pattern="[0-9]{10}" title="الرجاء إدخال رقم جوال صحيح">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">نوع النشاط</label>
                        <select name="category_id" class="form-select">
                            <option value="">-- اختر نوع النشاط --</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name_ar']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">رقم السجل التجاري</label>
                        <input type="text" class="form-control" name="commercial_register">
                    </div>

                    <div class="col-12">
                        <label class="form-label">وصف النشاط التجاري</label>
                        <textarea class="form-control" name="business_description" rows="4"
                            placeholder="اذكر تفاصيل عن نشاطك التجاري، الخدمات/المنتجات التي ستقدمها، والخبرات السابقة إن وجدت"></textarea>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-outline-light">تقديم الطلب</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-md-4">
            <div class="feature-card">
                <h5 class="mb-3">معلومات الوحدة</h5>
                <p class="mb-2">
                    <strong class="unit-code">رقم الوحدة:</strong>
                    <?php echo htmlspecialchars($unit['code']); ?>
                </p>
                <p class="mb-2">
                    <strong>الطابق:</strong>
                    <?php echo htmlspecialchars($unit['level']); ?>
                </p>
                <p class="mb-2">
                    <strong>المساحة:</strong>
                    <?php echo htmlspecialchars($unit['area_sqm']); ?> م²
                </p>
                <p class="mb-0">
                    <strong>نوع الوحدة:</strong>
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
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>