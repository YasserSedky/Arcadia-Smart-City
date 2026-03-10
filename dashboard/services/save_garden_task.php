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
$area_label = trim($_POST['area_label'] ?? '');
$task = trim($_POST['task'] ?? '');
$scheduled_date = trim($_POST['scheduled_date'] ?? '');
$notes = trim($_POST['notes'] ?? '');

if (empty($area_label) || empty($task) || empty($scheduled_date)) {
    $_SESSION['error'] = 'المنطقة والمهمة والتاريخ مطلوبة';
    header('Location: gardens.php');
    exit;
}

// Validate date format
if (!DateTime::createFromFormat('Y-m-d', $scheduled_date)) {
    $_SESSION['error'] = 'صيغة التاريخ غير صحيحة';
    header('Location: gardens.php');
    exit;
}

try {
    $pdo = DB::conn();

    // Create new garden task
    $stmt = $pdo->prepare("
        INSERT INTO garden_tasks 
            (area_label, task, scheduled_date, status, notes) 
        VALUES 
            (?, ?, ?, 'scheduled', ?)
    ");

    $stmt->execute([
        $area_label,
        $task,
        $scheduled_date,
        $notes
    ]);

    $_SESSION['success'] = 'تم إنشاء مهمة الحديقة بنجاح';
} catch (Exception $e) {
    $_SESSION['error'] = 'حدث خطأ أثناء إنشاء مهمة الحديقة';
}

header('Location: gardens.php');
