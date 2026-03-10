<?php
require_once __DIR__ . '/../includes/auth.php';

// Verify user is logged in and is a garden worker
if (!isset($_SESSION['user']) || !hasRole('garden_worker')) {
    header('Location: gardens.php');
    exit;
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: gardens.php');
    exit;
}

// Validate inputs
$task_id = (int)$_POST['task_id'];
$status = $_POST['status'] ?? '';
$notes = trim($_POST['notes'] ?? '');

if (empty($task_id) || empty($status)) {
    $_SESSION['error'] = 'المعرف والحالة مطلوبة';
    header('Location: gardens.php');
    exit;
}

// Validate status
$allowed_statuses = ['in_progress', 'done', 'cancelled'];
if (!in_array($status, $allowed_statuses)) {
    $_SESSION['error'] = 'قيمة الحالة غير صحيحة';
    header('Location: gardens.php');
    exit;
}

try {
    $pdo = DB::conn();

    // Verify task exists and can be updated
    $stmt = $pdo->prepare("
        SELECT * FROM garden_tasks 
        WHERE id = ? AND status NOT IN ('done', 'cancelled')
    ");
    $stmt->execute([$task_id]);

    if (!$stmt->fetch()) {
        $_SESSION['error'] = 'المهمة غير موجودة أو لا يمكن تحديثها';
        header('Location: gardens.php');
        exit;
    }

    // Update task status
    $stmt = $pdo->prepare("
        UPDATE garden_tasks 
        SET status = ?, notes = CONCAT(IFNULL(notes, ''), '\n', ?)
        WHERE id = ?
    ");
    $stmt->execute([$status, $notes, $task_id]);

    $_SESSION['success'] = 'تم تحديث حالة المهمة بنجاح';
} catch (Exception $e) {
    $_SESSION['error'] = 'حدث خطأ أثناء تحديث المهمة';
}

header('Location: gardens.php');
