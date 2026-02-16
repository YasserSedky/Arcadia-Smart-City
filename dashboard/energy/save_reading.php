<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin','energy_admin'])) { redirect('/dashboard/index.php'); }

function back($array_id, $msg=''){
  $loc = '/dashboard/energy/readings.php?array_id=' . (int)$array_id;
  if ($msg) $loc .= '&err=' . urlencode($msg);
  redirect($loc);
}

try{
  $array_id = (int)($_POST['array_id'] ?? 0);
  $ts = $_POST['ts'] ?? '';
  $power_kw = $_POST['power_kw'] !== '' ? (float)$_POST['power_kw'] : null;
  $energy_kwh = $_POST['energy_kwh'] !== '' ? (float)$_POST['energy_kwh'] : null;
  $temperature_c = $_POST['temperature_c'] !== '' ? (float)$_POST['temperature_c'] : null;
  $status = $_POST['status'] ?? 'ok';

  if ($array_id <= 0 || $ts === ''){ back($array_id, 'بيانات غير مكتملة'); }

  $pdo = DB::conn();
  $stmt = $pdo->prepare('INSERT INTO solar_readings(array_id, ts, power_kw, energy_kwh, temperature_c, status) VALUES(?,?,?,?,?,?)');
  $stmt->execute([$array_id, $ts, $power_kw, $energy_kwh, $temperature_c, $status]);
  back($array_id);
} catch(PDOException $e){
  back((int)($_POST['array_id'] ?? 0), 'خطأ: ' . $e->getMessage());
}



