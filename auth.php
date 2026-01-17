<?php
// auth.php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function login_user(array $user): void
{
    $_SESSION['user'] = [
        'id' => $user['id'],
        'username' => $user['username'],
        'role' => $user['role'],
    ];
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: ' . base_path() . '/login.php');
        exit;
    }
}

function require_role(string $role): void
{
    if (!is_logged_in() || strtolower($_SESSION['user']['role']) !== strtolower($role)) {
        http_response_code(403);
        echo 'Access denied.';
        exit;
    }
}

function find_user_by_identifier(PDO $pdo, string $identifier): ?array
{
    $sql = 'SELECT id, username, password_hash, role FROM users WHERE username = :identifier OR email = :identifier LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':identifier' => $identifier]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function base_path(): string
{
    if (!empty($GLOBALS['appBasePath'])) {
        return rtrim($GLOBALS['appBasePath'], '/');
    }
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    
    $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
    if ($requestUri && $requestUri !== '/') {
        $parts = array_filter(explode('/', trim($requestUri, '/')));
        if (count($parts) > 1) {
            $cached = '/' . $parts[0];
            return $cached;
        }
    }
    
    $authDir = str_replace('\\', '/', __DIR__);
    $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
    
    if ($docRoot) {
        $authDirLower = strtolower(rtrim($authDir, '/'));
        $docRootLower = strtolower(rtrim($docRoot, '/'));
        
        if (strpos($authDirLower, $docRootLower) === 0) {
            $docRootLen = strlen(rtrim($docRoot, '/'));
            $relative = substr($authDir, $docRootLen);
            $relative = trim($relative, '/');
            if ($relative) {
                $cached = '/' . $relative;
                return $cached;
            }
        }
    }
    
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    if ($scriptName && strpos($scriptName, '.php') !== false) {
        $base = dirname($scriptName);
        $base = rtrim($base, '/\\');
        if ($base && $base !== '/' && $base !== '.') {
            $cached = $base;
            return $cached;
        }
    }
    
    $cached = '';
    return $cached;
}
