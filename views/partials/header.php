<?php
require_once __DIR__ . '/../../auth.php';
$user = current_user();
$basePath = base_path();

function url_path(string $path): string
{
    global $basePath;
    $prefix = $GLOBALS['appBasePath'] ?? $basePath;
    $prefix = rtrim($prefix, '/');
    if ($prefix === '/') {
        $prefix = '';
    }
    return $prefix . '/' . ltrim($path, '/');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle ?? 'Vending Machine'); ?></title>
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
    rel="stylesheet"
    integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN"
    crossorigin="anonymous"
  >
  <link
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
    rel="stylesheet"
    integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
    crossorigin="anonymous"
    referrerpolicy="no-referrer"
  >
</head>
<body class="bg-light">
  <div class="container py-3">
