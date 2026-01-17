<?php
require_once __DIR__ . '/auth.php';
$basePath = base_path();
header('Location: ' . $basePath . '/shop');
exit;
?>