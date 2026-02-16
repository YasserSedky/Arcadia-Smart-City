<?php
require_once __DIR__ . '/../includes/auth.php';

// Check if user is logged in and has a unit assigned
if (empty($_SESSION['user']) || empty($_SESSION['user']['unit_id'])) {
    header('Location: index.php');
    exit;
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: myunit.php');
    exit;
}

// Validate inputs
$description = trim($_POST['description'] ?? '');
$priority = $_POST['priority'] ?? '';
$unit_id = (int)$_POST['unit_id'];

if (empty($description) || empty($priority) || empty($unit_id)) {
    $_SESSION['error'] = 'جميع الحقول مطلوبة';
    header('Location: myunit.php');
    exit;
}

// Validate priority value
$allowed_priorities = ['low', 'medium', 'high', 'urgent'];
if (!in_array($priority, $allowed_priorities)) {
    $_SESSION['error'] = 'قيمة الأولوية غير صحيحة';
    header('Location: myunit.php');
    exit;
}

// Verify unit belongs to logged in user
if ($unit_id !== (int)$_SESSION['user']['unit_id']) {
    $_SESSION['error'] = 'غير مصرح لك بإنشاء طلب صيانة لهذه الوحدة';
    header('Location: myunit.php');
    exit;
}

try {
    $pdo = DB::conn();

    // Create new maintenance ticket
    $stmt = $pdo->prepare("
        INSERT INTO maintenance_tickets 
            (unit_id, description, priority, status, created_at) 
        VALUES 
            (?, ?, ?, 'new', NOW())
    ");

    $stmt->execute([
        $unit_id,
        $description,
        $priority
    ]);

    $_SESSION['success'] = 'تم إرسال طلب الصيانة بنجاح';
} catch (Exception $e) {
    $_SESSION['error'] = 'حدث خطأ أثناء حفظ طلب الصيانة';
}

header('Location: myunit.php');
