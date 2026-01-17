<?php
require_once __DIR__ . '/config.php';

function api_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
}

function api_error(string $message, int $status): void
{
    api_json(['error' => $message], $status);
}

function api_base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function api_base64url_decode(string $data): string
{
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $data .= str_repeat('=', 4 - $remainder);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

function jwt_sign(array $payload, int $ttlSeconds = 3600): string
{
    global $jwtSecret;
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $payload['iat'] = time();
    $payload['exp'] = time() + $ttlSeconds;

    $segments = [
        api_base64url_encode(json_encode($header)),
        api_base64url_encode(json_encode($payload)),
    ];
    $signature = hash_hmac('sha256', implode('.', $segments), $jwtSecret, true);
    $segments[] = api_base64url_encode($signature);
    return implode('.', $segments);
}

function jwt_verify(string $token): ?array
{
    global $jwtSecret;
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }
    [$headerB64, $payloadB64, $signatureB64] = $parts;
    $signature = api_base64url_decode($signatureB64);
    $expected = hash_hmac('sha256', $headerB64 . '.' . $payloadB64, $jwtSecret, true);
    if (!hash_equals($expected, $signature)) {
        return null;
    }
    $payloadJson = api_base64url_decode($payloadB64);
    $payload = json_decode($payloadJson, true);
    if (!is_array($payload)) {
        return null;
    }
    if (!isset($payload['exp']) || time() >= (int)$payload['exp']) {
        return null;
    }
    return $payload;
}

function api_bearer_token(): ?string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!$header && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }
    if (preg_match('/Bearer\s+(\S+)/i', $header, $matches)) {
        return $matches[1];
    }
    return null;
}

function api_auth_user(PDO $pdo): ?array
{
    $token = api_bearer_token();
    if (!$token) {
        return null;
    }
    $payload = jwt_verify($token);
    if (!$payload || empty($payload['sub'])) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT id, username, role FROM users WHERE id = :id');
    $stmt->execute([':id' => (int)$payload['sub']]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function api_require_auth(PDO $pdo): array
{
    $user = api_auth_user($pdo);
    if (!$user) {
        api_error('Unauthorized', 401);
        exit;
    }
    return $user;
}

function api_require_role(array $user, string $role): void
{
    if (strtolower($user['role']) !== strtolower($role)) {
        api_error('Forbidden', 403);
        exit;
    }
}

function api_request_body(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
    return $_POST;
}
