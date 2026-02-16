<?php
require_once __DIR__ . '/../backend/config.php';
ensure_session();
$_SESSION = [];
session_destroy();
redirect('/auth/login.php?ok=' . urlencode('تم تسجيل الخروج'));



