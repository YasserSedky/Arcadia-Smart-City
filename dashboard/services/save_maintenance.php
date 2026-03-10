<?php
require_once __DIR__ . '/../includes/auth.php';

// Verify user is logged in and is a maintenance worker
if (!isset($_SESSION['user']) || !hasRole('maintenance_worker')) {
    header('Location: maintenance.php');
    exit;
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: maintenance.php');
    exit;
}

// Validate inputs
$title = trim($_POST['title'] ?? '');
$details = trim($_POST['details'] ?? '');
$priority = $_POST['priority'] ?? '';
$unit_id = !empty($_POST['unit_id']) ? (int)$_POST['unit_id'] : null;

if (empty($title) || empty($details) || empty($priority)) {
    $_SESSION['error'] = 'جميع الحقول المطلوبة يجب ملؤها';
    header('Location: maintenance.php');
    exit;
}

// Validate priority
$allowed_priorities = ['low', 'medium', 'high', 'urgent'];
if (!in_array($priority, $allowed_priorities)) {
    $_SESSION['error'] = 'قيمة الأولوية غير صحيحة';
    header('Location: maintenance.php');
    exit;
}

// If unit_id is provided, verify it exists
if ($unit_id) {
    $pdo = DB::conn();
    $stmt = $pdo->prepare("SELECT id FROM units WHERE id = ?");
    $stmt->execute([$unit_id]);
    if (!$stmt->fetch()) {
        $_SESSION['error'] = 'الوحدة السكنية غير موجودة';
        header('Location: maintenance.php');
        exit;
    }
}

try {
    $pdo = DB::conn();

    // Create new maintenance ticket
    $stmt = $pdo->prepare("
        INSERT INTO maintenance_tickets 
            (created_by_user_id, unit_id, title, details, priority, status, created_at) 
        VALUES 
            (?, ?, ?, ?, ?, 'open', NOW())
    ");

    $stmt->execute([
        $_SESSION['user']['id'],
        $unit_id,
        $title,
        $details,
        $priority
    ]);

    $_SESSION['success'] = 'تم إنشاء طلب الصيانة بنجاح';
} catch (Exception $e) {
    $_SESSION['error'] = 'حدث خطأ أثناء إنشاء طلب الصيانة';
}

header('Location: maintenance.php');
