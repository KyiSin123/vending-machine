<?php
require_once __DIR__ . '/auth.php';

$basePath = base_path();

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    logout_user();
    header('Location: ' . $basePath . '/login.php');
    exit;
}

http_response_code(405);
echo 'Method Not Allowed';
