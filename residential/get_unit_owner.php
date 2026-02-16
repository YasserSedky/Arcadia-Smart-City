<?php
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$unit_id = isset($_GET['unit_id']) ? (int)$_GET['unit_id'] : 0;

if ($unit_id < 1) {
    echo json_encode(['success' => false, 'message' => 'معرف الوحدة غير صحيح']);
    exit;
}

try {
    $pdo = DB::conn();
    
    // Get first user who registered in this unit (ordered by created_at ASC)
    $stmt = $pdo->prepare("
        SELECT u.full_name, u.created_at
        FROM users u
        WHERE u.unit_id = ? AND u.is_active = 1
        ORDER BY u.created_at ASC
        LIMIT 1
    ");
    $stmt->execute([$unit_id]);
    $owner = $stmt->fetch();
    
    if ($owner) {
        echo json_encode([
            'success' => true,
            'owner_name' => $owner['full_name'],
            'registered_at' => $owner['created_at']
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'owner_name' => null,
            'message' => 'لا يوجد سكان مسجلين في هذه الوحدة'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'حدث خطأ: ' . $e->getMessage()]);
}

