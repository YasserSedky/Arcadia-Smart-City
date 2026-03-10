<?php
$page_title = 'حذف محطة كهرباء';
require_once __DIR__ . '/../../includes/auth.php';
require_login();
$u = $_SESSION['user'];
if (!user_can(['super_admin', 'energy_admin'])) {
    redirect('/dashboard/index.php');
}
$pdo = DB::conn();

$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM power_stations WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = 'تم حذف المحطة بنجاح';
    } catch (PDOException $e) {
        $_SESSION['error'] = 'خطأ في الحذف: ' . $e->getMessage();
    }
} else {
    $_SESSION['error'] = 'معرف المحطة غير صحيح';
}

redirect('/dashboard/energy/index.php?tab=stations');
