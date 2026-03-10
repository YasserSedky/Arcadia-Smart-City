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
$ticket_id = (int)$_POST['ticket_id'];
$worker_ids = $_POST['worker_ids'] ?? [];
$notes = trim($_POST['notes'] ?? '');

if (empty($ticket_id) || empty($worker_ids)) {
    $_SESSION['error'] = 'يجب اختيار عامل واحد على الأقل';
    header('Location: maintenance.php');
    exit;
}

try {
    $pdo = DB::conn();

    // Verify ticket exists and is in open status
    $stmt = $pdo->prepare("
        SELECT * FROM maintenance_tickets 
        WHERE id = ? AND status = 'open'
    ");
    $stmt->execute([$ticket_id]);

    if (!$stmt->fetch()) {
        $_SESSION['error'] = 'طلب الصيانة غير موجود أو لا يمكن تعيين عمال له';
        header('Location: maintenance.php');
        exit;
    }

    // Verify all worker IDs are valid maintenance workers
    $workersList = implode(',', array_map('intval', $worker_ids));
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM users u 
        JOIN user_roles ur ON ur.user_id = u.id 
        JOIN roles r ON r.id = ur.role_id
        WHERE u.id IN ($workersList) AND r.name = 'maintenance_worker'
    ");
    $stmt->execute();
    $result = $stmt->fetch();

    if ($result['count'] != count($worker_ids)) {
        $_SESSION['error'] = 'بعض العمال المختارين غير مصرح لهم بالصيانة';
        header('Location: maintenance.php');
        exit;
    }

    // Start transaction
    $pdo->beginTransaction();

    // Update ticket status
    $stmt = $pdo->prepare("
        UPDATE maintenance_tickets 
        SET status = 'assigned' 
        WHERE id = ?
    ");
    $stmt->execute([$ticket_id]);

    // Assign workers
    $stmt = $pdo->prepare("
        INSERT INTO maintenance_assignments 
            (ticket_id, worker_user_id, assigned_at, notes) 
        VALUES 
            (?, ?, NOW(), ?)
    ");

    foreach ($worker_ids as $worker_id) {
        $stmt->execute([$ticket_id, $worker_id, $notes]);
    }

    $pdo->commit();
    $_SESSION['success'] = 'تم تعيين العمال بنجاح';
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = 'حدث خطأ أثناء تعيين العمال';
}

header('Location: maintenance.php');
