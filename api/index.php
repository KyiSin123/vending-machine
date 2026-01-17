<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/api_helpers.php';

function api_sanitize_product(array $input): array
{
    return [
        'name' => trim($input['name'] ?? ''),
        'price' => trim($input['price'] ?? ''),
        'quantity_available' => (int)($input['quantity_available'] ?? 0),
    ];
}

function api_validate_product(array $data): array
{
    $errors = [];
    if ($data['name'] === '') {
        $errors['name'] = 'Name is required.';
    }
    if ($data['price'] === '' || !is_numeric($data['price'])) {
        $errors['price'] = 'Price must be a valid number.';
    } elseif ((float)$data['price'] < 0) {
        $errors['price'] = 'Price must be zero or higher.';
    }
    if ($data['quantity_available'] < 0) {
        $errors['quantity_available'] = 'Quantity must be zero or higher.';
    }
    return $errors;
}

function api_find_product(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT id, name, price, quantity_available FROM products WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $product = $stmt->fetch();
    return $product ?: null;
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if ($method === 'POST') {
    $override = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? ($_POST['_method'] ?? '');
    if ($override) {
        $method = strtoupper($override);
    }
}

$pathInfo = $_SERVER['PATH_INFO'] ?? '';
if ($pathInfo === '' || $pathInfo === '/') {
    $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    if ($scriptDir && strncmp($uriPath, $scriptDir, strlen($scriptDir)) === 0) {
        $pathInfo = substr($uriPath, strlen($scriptDir));
    } else {
        $pathInfo = $uriPath;
    }
}

$pathInfo = '/' . ltrim((string)$pathInfo, '/');
$segments = array_values(array_filter(explode('/', $pathInfo)));

if (!$segments) {
    api_json([
        'message' => 'Vending machine API',
        'routes' => [
            'POST /api/auth/login',
            'POST /api/auth/register',
            'POST /api/auth/logout',
            'POST /api/auth/forgot',
            'GET /api/products',
            'POST /api/products',
            'GET /api/products/{id}',
            'PUT /api/products/{id}',
            'DELETE /api/products/{id}',
            'POST /api/purchase/{id}',
            'GET /api/transactions',
            'GET /api/transactions/{id}',
        ],
    ]);
    return;
}

if ($segments[0] === 'auth' && ($segments[1] ?? '') === 'login') {
    if ($method !== 'POST') {
        api_error('Method Not Allowed', 405);
        return;
    }
    $data = api_request_body();
    $identifier = trim($data['identifier'] ?? '');
    $password = $data['password'] ?? '';
    if ($identifier === '' || $password === '') {
        api_error('Missing credentials', 422);
        return;
    }
    $stmt = $pdo->prepare('SELECT id, username, password_hash, role FROM users WHERE username = :id OR email = :id LIMIT 1');
    $stmt->execute([':id' => $identifier]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) {
        api_error('Invalid credentials', 401);
        return;
    }
    $token = jwt_sign(['sub' => $user['id'], 'role' => $user['role']]);
    api_json([
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
        ],
    ]);
    return;
}

if ($segments[0] === 'auth' && ($segments[1] ?? '') === 'register') {
    if ($method !== 'POST') {
        api_error('Method Not Allowed', 405);
        return;
    }
    $data = api_request_body();
    $username = trim($data['username'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $role = trim($data['role'] ?? 'User');

    $errors = [];
    if ($username === '') {
        $errors['username'] = 'Username is required.';
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email.';
    }
    if ($password === '' || strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    }
    $roleOptions = ['Admin', 'User'];
    if (!in_array($role, $roleOptions, true)) {
        $errors['role'] = 'Invalid role.';
    }
    if ($errors) {
        api_json(['errors' => $errors], 422);
        return;
    }

    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
    $stmt->execute([':username' => $username]);
    if ($stmt->fetch()) {
        api_json(['errors' => ['username' => 'Username is already taken.']], 422);
        return;
    }
    if ($email !== '') {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            api_json(['errors' => ['email' => 'Email is already registered.']], 422);
            return;
        }
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, role) VALUES (:username, :email, :password_hash, :role)');
    $stmt->execute([
        ':username' => $username,
        ':email' => $email !== '' ? $email : null,
        ':password_hash' => $passwordHash,
        ':role' => $role,
    ]);

    api_json(['message' => 'Account created.'], 201);
    return;
}

if ($segments[0] === 'auth' && ($segments[1] ?? '') === 'logout') {
    if ($method !== 'POST') {
        api_error('Method Not Allowed', 405);
        return;
    }
    api_json(['message' => 'Logged out.']);
    return;
}

if ($segments[0] === 'auth' && ($segments[1] ?? '') === 'forgot') {
    if ($method !== 'POST') {
        api_error('Method Not Allowed', 405);
        return;
    }
    $data = api_request_body();
    $identifier = trim($data['identifier'] ?? '');
    if ($identifier === '') {
        api_error('Missing identifier', 422);
        return;
    }

    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :id OR email = :id LIMIT 1');
    $stmt->execute([':id' => $identifier]);
    $user = $stmt->fetch();
    if ($user) {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');

        $stmt = $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (:user_id, :token_hash, :expires_at)');
        $stmt->execute([
            ':user_id' => $user['id'],
            ':token_hash' => $tokenHash,
            ':expires_at' => $expiresAt,
        ]);

        api_json([
            'message' => 'If the account exists, a reset token has been generated.',
            'reset_token' => $token,
        ]);
        return;
    }

    api_json(['message' => 'If the account exists, a reset token has been generated.']);
    return;
}

if ($segments[0] === 'products') {
    $user = api_require_auth($pdo);

    if (count($segments) === 1) {
        if ($method === 'GET') {
            $sort = $_GET['sort'] ?? 'id';
            $dir = strtolower($_GET['dir'] ?? 'desc');
            $page = max(1, (int)($_GET['page'] ?? 1));
            $perPage = min(50, max(1, (int)($_GET['per_page'] ?? 10)));

            $allowedSorts = ['id', 'name', 'price', 'quantity_available'];
            if (!in_array($sort, $allowedSorts, true)) {
                $sort = 'id';
            }
            $dir = $dir === 'asc' ? 'asc' : 'desc';

            $total = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
            $totalPages = max(1, (int)ceil($total / $perPage));
            if ($page > $totalPages) {
                $page = $totalPages;
            }
            $offset = ($page - 1) * $perPage;

            $sql = 'SELECT id, name, price, quantity_available
                    FROM products
                    ORDER BY ' . $sort . ' ' . $dir . '
                    LIMIT :limit OFFSET :offset';
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            api_json([
                'data' => $stmt->fetchAll(),
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'total_pages' => $totalPages,
                ],
                'sort' => $sort,
                'dir' => $dir,
            ]);
            return;
        }

        if ($method === 'POST') {
            api_require_role($user, 'Admin');
            $data = api_sanitize_product(api_request_body());
            $errors = api_validate_product($data);
            if ($errors) {
                api_json(['errors' => $errors], 422);
                return;
            }
            $stmt = $pdo->prepare(
                'INSERT INTO products (name, price, quantity_available) VALUES (:name, :price, :quantity)'
            );
            $stmt->execute([
                ':name' => $data['name'],
                ':price' => $data['price'],
                ':quantity' => $data['quantity_available'],
            ]);
            $id = (int)$pdo->lastInsertId();
            api_json(['data' => api_find_product($pdo, $id)], 201);
            return;
        }

        api_error('Method Not Allowed', 405);
        return;
    }

    $id = (int)($segments[1] ?? 0);
    if ($id <= 0) {
        api_error('Not Found', 404);
        return;
    }

    if ($method === 'GET') {
        $product = api_find_product($pdo, $id);
        if (!$product) {
            api_error('Not Found', 404);
            return;
        }
        api_json(['data' => $product]);
        return;
    }

    if ($method === 'PUT' || $method === 'PATCH') {
        api_require_role($user, 'Admin');
        $product = api_find_product($pdo, $id);
        if (!$product) {
            api_error('Not Found', 404);
            return;
        }
        $data = api_sanitize_product(api_request_body());
        $errors = api_validate_product($data);
        if ($errors) {
            api_json(['errors' => $errors], 422);
            return;
        }
        $stmt = $pdo->prepare(
            'UPDATE products SET name = :name, price = :price, quantity_available = :quantity WHERE id = :id'
        );
        $stmt->execute([
            ':name' => $data['name'],
            ':price' => $data['price'],
            ':quantity' => $data['quantity_available'],
            ':id' => $id,
        ]);
        api_json(['data' => api_find_product($pdo, $id)]);
        return;
    }

    if ($method === 'DELETE') {
        api_require_role($user, 'Admin');
        $product = api_find_product($pdo, $id);
        if (!$product) {
            api_error('Not Found', 404);
            return;
        }
        $stmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
        $stmt->execute([':id' => $id]);
        api_json(['message' => 'Product deleted.']);
        return;
    }

    api_error('Method Not Allowed', 405);
    return;
}

if ($segments[0] === 'purchase' && isset($segments[1])) {
    $user = api_require_auth($pdo);
    if ($method !== 'POST') {
        api_error('Method Not Allowed', 405);
        return;
    }
    $id = (int)$segments[1];
    $data = api_request_body();
    $quantity = (int)($data['quantity'] ?? 0);
    if ($quantity <= 0) {
        api_json(['errors' => ['quantity' => 'Quantity must be at least 1.']], 422);
        return;
    }
    $product = api_find_product($pdo, $id);
    if (!$product) {
        api_error('Not Found', 404);
        return;
    }
    if ($quantity > (int)$product['quantity_available']) {
        api_json(['errors' => ['quantity' => 'Not enough stock available.']], 422);
        return;
    }

    $unitPrice = (float)$product['price'];
    $lineTotal = number_format($unitPrice * $quantity, 3, '.', '');

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT quantity_available FROM products WHERE id = :id FOR UPDATE');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row || (int)$row['quantity_available'] < $quantity) {
            throw new RuntimeException('Insufficient stock.');
        }

        $stmt = $pdo->prepare(
            'INSERT INTO transactions (user_id, total_amount) VALUES (:user_id, :total_amount)'
        );
        $stmt->execute([
            ':user_id' => $user['id'],
            ':total_amount' => $lineTotal,
        ]);
        $transactionId = (int)$pdo->lastInsertId();

        $stmt = $pdo->prepare(
            'INSERT INTO transaction_items (transaction_id, product_id, quantity, unit_price, line_total)
             VALUES (:transaction_id, :product_id, :quantity, :unit_price, :line_total)'
        );
        $stmt->execute([
            ':transaction_id' => $transactionId,
            ':product_id' => $id,
            ':quantity' => $quantity,
            ':unit_price' => number_format($unitPrice, 3, '.', ''),
            ':line_total' => $lineTotal,
        ]);

        $stmt = $pdo->prepare(
            'UPDATE products SET quantity_available = quantity_available - :quantity WHERE id = :id'
        );
        $stmt->execute([':quantity' => $quantity, ':id' => $id]);

        $pdo->commit();
        api_json([
            'message' => 'Purchase completed.',
            'transaction_id' => $transactionId,
            'product_id' => $id,
            'quantity' => $quantity,
            'remaining' => (int)$row['quantity_available'] - $quantity,
        ]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        api_error('Purchase failed. Please try again.', 500);
    }
    return;
}

if ($segments[0] === 'transactions') {
    $user = api_require_auth($pdo);
    api_require_role($user, 'Admin');

    if (count($segments) === 1) {
        if ($method !== 'GET') {
            api_error('Method Not Allowed', 405);
            return;
        }
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(50, max(1, (int)($_GET['per_page'] ?? 10)));

        $total = (int)$pdo->query('SELECT COUNT(*) FROM transactions')->fetchColumn();
        $totalPages = max(1, (int)ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        $sql = 'SELECT t.id, t.total_amount, t.created_at, u.username
                FROM transactions t
                LEFT JOIN users u ON u.id = t.user_id
                ORDER BY t.id DESC
                LIMIT :limit OFFSET :offset';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        api_json([
            'data' => $stmt->fetchAll(),
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
            ],
        ]);
        return;
    }

    $id = (int)($segments[1] ?? 0);
    if ($id <= 0) {
        api_error('Not Found', 404);
        return;
    }

    if ($method !== 'GET') {
        api_error('Method Not Allowed', 405);
        return;
    }

    $stmt = $pdo->prepare(
        'SELECT t.id, t.total_amount, t.created_at, u.username
         FROM transactions t
         LEFT JOIN users u ON u.id = t.user_id
         WHERE t.id = :id'
    );
    $stmt->execute([':id' => $id]);
    $transaction = $stmt->fetch();
    if (!$transaction) {
        api_error('Not Found', 404);
        return;
    }

    $stmt = $pdo->prepare(
        'SELECT ti.quantity, ti.unit_price, ti.line_total, p.name
         FROM transaction_items ti
         JOIN products p ON p.id = ti.product_id
         WHERE ti.transaction_id = :id'
    );
    $stmt->execute([':id' => $id]);
    $items = $stmt->fetchAll();

    api_json([
        'data' => $transaction,
        'items' => $items,
    ]);
    return;
}

api_error('Not Found', 404);
