<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!user_can(['super_admin','energy_admin'])) { redirect('/dashboard/index.php'); }

function fail_back($msg){
  redirect('/dashboard/energy/new_array.php?err=' . urlencode($msg));
}

try{
  $building_id = (int)($_POST['building_id'] ?? 0);
  $name = trim($_POST['name'] ?? '');
  $capacity_kw = (float)($_POST['capacity_kw'] ?? 0);
  $install_date = $_POST['install_date'] ?? null;
  $notes = trim($_POST['notes'] ?? '');

  if ($building_id <= 0 || $name === '' || $capacity_kw <= 0){
    fail_back('يرجى إدخال البيانات المطلوبة بشكل صحيح');
  }

  $pdo = DB::conn();
  $stmt = $pdo->prepare('INSERT INTO solar_arrays(building_id,name,capacity_kw,install_date,notes) VALUES(?,?,?,?,?)');
  $stmt->execute([$building_id,$name,$capacity_kw,$install_date ?: null,$notes ?: null]);
  redirect('/dashboard/energy/index.php');
} catch(PDOException $e){
  fail_back('خطأ: ' . $e->getMessage());
}



