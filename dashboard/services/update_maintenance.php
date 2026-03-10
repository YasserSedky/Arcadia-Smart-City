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
$status = $_POST['status'] ?? '';
$notes = trim($_POST['notes'] ?? '');

if (empty($ticket_id) || empty($status)) {
    $_SESSION['error'] = 'المعرف والحالة مطلوبة';
    header('Location: maintenance.php');
    exit;
}

// Validate status
$allowed_statuses = ['in_progress', 'resolved', 'closed'];
if (!in_array($status, $allowed_statuses)) {
    $_SESSION['error'] = 'قيمة الحالة غير صحيحة';
    header('Location: maintenance.php');
    exit;
}

try {
    $pdo = DB::conn();

    // Verify ticket exists and user is assigned to it
    $stmt = $pdo->prepare("
        SELECT mt.* 
        FROM maintenance_tickets mt
        LEFT JOIN maintenance_assignments ma ON ma.ticket_id = mt.id
        WHERE mt.id = ? AND (ma.worker_user_id = ? OR mt.created_by_user_id = ?)
    ");
    $stmt->execute([$ticket_id, $_SESSION['user']['id'], $_SESSION['user']['id']]);

    if (!$stmt->fetch()) {
        $_SESSION['error'] = 'طلب الصيانة غير موجود أو غير مصرح لك بتحديثه';
        header('Location: maintenance.php');
        exit;
    }

    // Update ticket status
    $stmt = $pdo->prepare("
        UPDATE maintenance_tickets 
        SET status = ? 
        WHERE id = ?
    ");
    $stmt->execute([$status, $ticket_id]);

    // Add note if provided
    if (!empty($notes)) {
        $stmt = $pdo->prepare("
            INSERT INTO maintenance_notes 
                (ticket_id, user_id, note, created_at) 
            VALUES 
                (?, ?, ?, NOW())
        ");
        $stmt->execute([$ticket_id, $_SESSION['user']['id'], $notes]);
    }

    $_SESSION['success'] = 'تم تحديث حالة طلب الصيانة بنجاح';
} catch (Exception $e) {
    $_SESSION['error'] = 'حدث خطأ أثناء تحديث طلب الصيانة';
}

header('Location: maintenance.php');
