<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/auth.php';

class ProductsController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function index(): void
    {
        require_role('Admin');
        $sort = $_GET['sort'] ?? 'id';
        $dir = strtolower($_GET['dir'] ?? 'desc');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 10;

        $allowedSorts = ['id', 'name', 'price', 'quantity_available'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'id';
        }
        $dir = $dir === 'asc' ? 'asc' : 'desc';

        $total = (int)$this->pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
        $totalPages = max(1, (int)ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        $sql = 'SELECT id, name, price, quantity_available
                FROM products
                ORDER BY ' . $sort . ' ' . $dir . '
                LIMIT :limit OFFSET :offset';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $products = $stmt->fetchAll();

        $message = trim($_GET['message'] ?? '');
        if ($this->wantsJson()) {
            $this->json([
                'data' => $products,
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
        include dirname(__DIR__) . '/views/products/index.php';
    }

    public function shop(): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 12;

        $total = (int)$this->pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
        $totalPages = max(1, (int)ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        $stmt = $this->pdo->prepare(
            'SELECT id, name, price, quantity_available FROM products ORDER BY name ASC LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $products = $stmt->fetchAll();

        include dirname(__DIR__) . '/views/products/shop.php';
    }

    public function cartIndex(): void
    {
        require_login();
        $cart = $_SESSION['cart'] ?? [];
        $items = [];
        $total = 0.0;

        if ($cart) {
            $ids = array_keys($cart);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $this->pdo->prepare(
                "SELECT id, name, price, quantity_available FROM products WHERE id IN ($placeholders)"
            );
            $stmt->execute($ids);
            $products = $stmt->fetchAll();
            $map = [];
            foreach ($products as $product) {
                $map[$product['id']] = $product;
            }
            foreach ($cart as $productId => $qty) {
                if (!isset($map[$productId])) {
                    continue;
                }
                $product = $map[$productId];
                $lineTotal = (float)$product['price'] * $qty;
                $total += $lineTotal;
                $items[] = [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'quantity_available' => $product['quantity_available'],
                    'quantity' => $qty,
                    'line_total' => $lineTotal,
                ];
            }
        }

        $message = trim($_GET['message'] ?? '');
        include dirname(__DIR__) . '/views/cart/index.php';
    }

    public function cartAdd(int $id): void
    {
        require_login();
        $quantity = (int)($_POST['quantity'] ?? 1);
        if ($quantity < 1) {
            $quantity = 1;
        }
        $product = $this->findProduct($id);
        if (!$product) {
            $this->notFound();
            return;
        }
        $cart = $_SESSION['cart'] ?? [];
        $current = (int)($cart[$id] ?? 0);
        $newQty = $current + $quantity;
        $cart[$id] = $newQty;
        $_SESSION['cart'] = $cart;
        header('Location: ' . base_path() . '/shop?message=Added%20to%20cart.');
        exit;
    }

    public function cartUpdate(): void
    {
        require_login();
        $quantities = $_POST['quantities'] ?? [];
        $cart = $_SESSION['cart'] ?? [];
        foreach ($quantities as $productId => $qty) {
            $productId = (int)$productId;
            $qty = (int)$qty;
            if ($qty <= 0) {
                unset($cart[$productId]);
            } else {
                $cart[$productId] = $qty;
            }
        }
        $_SESSION['cart'] = $cart;
        header('Location: ' . base_path() . '/cart?message=Cart%20updated.');
        exit;
    }

    public function cartRemove(int $id): void
    {
        require_login();
        $cart = $_SESSION['cart'] ?? [];
        unset($cart[$id]);
        $_SESSION['cart'] = $cart;
        header('Location: ' . base_path() . '/cart?message=Item%20removed.');
        exit;
    }

    public function cartCheckout(): void
    {
        require_login();
        $cart = $_SESSION['cart'] ?? [];
        if (!$cart) {
            header('Location: ' . base_path() . '/cart?message=Cart%20is%20empty.');
            exit;
        }

        $user = current_user();
        $this->pdo->beginTransaction();
        try {
            $ids = array_keys($cart);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $this->pdo->prepare(
                "SELECT id, name, price, quantity_available FROM products WHERE id IN ($placeholders) FOR UPDATE"
            );
            $stmt->execute($ids);
            $products = $stmt->fetchAll();
            $map = [];
            foreach ($products as $product) {
                $map[$product['id']] = $product;
            }

            $total = 0.0;
            foreach ($cart as $productId => $qty) {
                if (!isset($map[$productId])) {
                    throw new RuntimeException('Product not found.');
                }
                $available = (int)$map[$productId]['quantity_available'];
                if ($qty > $available) {
                    throw new RuntimeException('Not enough stock for ' . $map[$productId]['name'] . '.');
                }
                $total += (float)$map[$productId]['price'] * $qty;
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO transactions (user_id, total_amount) VALUES (:user_id, :total_amount)'
            );
            $stmt->execute([
                ':user_id' => $user['id'] ?? null,
                ':total_amount' => number_format($total, 3, '.', ''),
            ]);
            $transactionId = (int)$this->pdo->lastInsertId();

            foreach ($cart as $productId => $qty) {
                $product = $map[$productId];
                $unitPrice = (float)$product['price'];
                $lineTotal = number_format($unitPrice * $qty, 3, '.', '');

                $stmt = $this->pdo->prepare(
                    'INSERT INTO transaction_items (transaction_id, product_id, quantity, unit_price, line_total)
                     VALUES (:transaction_id, :product_id, :quantity, :unit_price, :line_total)'
                );
                $stmt->execute([
                    ':transaction_id' => $transactionId,
                    ':product_id' => $productId,
                    ':quantity' => $qty,
                    ':unit_price' => number_format($unitPrice, 3, '.', ''),
                    ':line_total' => $lineTotal,
                ]);

                $stmt = $this->pdo->prepare(
                    'UPDATE products SET quantity_available = quantity_available - :quantity WHERE id = :id'
                );
                $stmt->execute([':quantity' => $qty, ':id' => $productId]);
            }

            $this->pdo->commit();
            $_SESSION['cart'] = [];
            header('Location: ' . base_path() . '/shop?message=Purchase%20completed.');
            exit;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            header('Location: ' . base_path() . '/cart?message=' . urlencode($e->getMessage()));
            exit;
        }
    }

    public function transactionsIndex(): void
    {
        require_role('Admin');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 10;

        $total = (int)$this->pdo->query('SELECT COUNT(*) FROM transactions')->fetchColumn();
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
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $transactions = $stmt->fetchAll();

        include dirname(__DIR__) . '/views/transactions/index.php';
    }

    public function transactionShow(int $id): void
    {
        require_role('Admin');
        $stmt = $this->pdo->prepare(
            'SELECT t.id, t.total_amount, t.created_at, u.username
             FROM transactions t
             LEFT JOIN users u ON u.id = t.user_id
             WHERE t.id = :id'
        );
        $stmt->execute([':id' => $id]);
        $transaction = $stmt->fetch();
        if (!$transaction) {
            $this->notFound();
        }

        $stmt = $this->pdo->prepare(
            'SELECT ti.quantity, ti.unit_price, ti.line_total, p.name
             FROM transaction_items ti
             JOIN products p ON p.id = ti.product_id
             WHERE ti.transaction_id = :id'
        );
        $stmt->execute([':id' => $id]);
        $items = $stmt->fetchAll();

        include dirname(__DIR__) . '/views/transactions/show.php';
    }

    public function show(int $id): void
    {
        require_role('Admin');
        $product = $this->findProduct($id);
        if (!$product) {
            $this->notFound();
            return;
        }
        if ($this->wantsJson()) {
            $this->json(['data' => $product]);
            return;
        }
        include dirname(__DIR__) . '/views/products/show.php';
    }

    public function create(array $errors = [], array $old = []): void
    {
        require_role('Admin');
        if ($this->wantsJson()) {
            $this->json(['message' => 'Use POST /products.php?action=store to create a product.'], 400);
            return;
        }
        include dirname(__DIR__) . '/views/products/create.php';
    }

    public function store(): void
    {
        require_role('Admin');
        $data = $this->sanitizeProductInput($_POST);
        $errors = $this->validateProduct($data);

        if (!$errors) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO products (name, price, quantity_available) VALUES (:name, :price, :quantity)'
            );
            $stmt->execute([
                ':name' => $data['name'],
                ':price' => $data['price'],
                ':quantity' => $data['quantity_available'],
            ]);

            if ($this->wantsJson()) {
                $id = (int)$this->pdo->lastInsertId();
                $this->json([
                    'message' => 'Product created.',
                    'data' => $this->findProduct($id),
                ], 201);
                return;
            }

            header('Location: products.php?action=index&message=Product%20created.');
            exit;
        }

        if ($this->wantsJson()) {
            $this->json(['errors' => $errors], 422);
            return;
        }
        $this->create($errors, $data);
    }

    public function edit(int $id, array $errors = [], array $old = []): void
    {
        require_role('Admin');
        $product = $this->findProduct($id);
        if (!$product) {
            $this->notFound();
            return;
        }
        if ($old) {
            $product = array_merge($product, $old);
        }
        if ($this->wantsJson()) {
            $this->json(['data' => $product]);
            return;
        }
        include dirname(__DIR__) . '/views/products/edit.php';
    }

    public function update(int $id): void
    {
        require_role('Admin');
        $product = $this->findProduct($id);
        if (!$product) {
            $this->notFound();
            return;
        }

        $data = $this->sanitizeProductInput($_POST);
        $errors = $this->validateProduct($data);

        if (!$errors) {
            $stmt = $this->pdo->prepare(
                'UPDATE products SET name = :name, price = :price, quantity_available = :quantity WHERE id = :id'
            );
            $stmt->execute([
                ':name' => $data['name'],
                ':price' => $data['price'],
                ':quantity' => $data['quantity_available'],
                ':id' => $id,
            ]);

            if ($this->wantsJson()) {
                $this->json([
                    'message' => 'Product updated.',
                    'data' => $this->findProduct($id),
                ]);
                return;
            }

            header('Location: products.php?action=index&message=Product%20updated.');
            exit;
        }

        if ($this->wantsJson()) {
            $this->json(['errors' => $errors], 422);
            return;
        }
        $this->edit($id, $errors, $data);
    }

    public function destroy(int $id): void
    {
        require_role('Admin');
        $product = $this->findProduct($id);
        if (!$product) {
            $this->notFound();
            return;
        }
        $stmt = $this->pdo->prepare('DELETE FROM products WHERE id = :id');
        $stmt->execute([':id' => $id]);
        if ($this->wantsJson()) {
            $this->json(['message' => 'Product deleted.']);
            return;
        }
        header('Location: products.php?action=index&message=Product%20deleted.');
        exit;
    }

    public function purchaseForm(int $id, array $errors = [], array $old = []): void
    {
        require_login();
        $product = $this->findProduct($id);
        if (!$product) {
            $this->notFound();
            return;
        }
        $data = $old ?: ['quantity' => 1];
        if ($this->wantsJson()) {
            $this->json([
                'data' => $product,
                'quantity' => $data['quantity'],
                'errors' => $errors,
            ]);
            return;
        }
        include dirname(__DIR__) . '/views/products/purchase.php';
    }

    public function purchase(int $id): void
    {
        require_login();
        $quantity = (int)($_POST['quantity'] ?? 0);
        $errors = [];

        if ($quantity <= 0) {
            $errors['quantity'] = 'Quantity must be at least 1.';
        }

        $product = $this->findProduct($id);
        if (!$product) {
            $this->notFound();
        }

        if ($quantity > (int)$product['quantity_available']) {
            $errors['quantity'] = 'Not enough stock available.';
        }

        if ($errors) {
            if ($this->wantsJson()) {
                $this->json(['errors' => $errors], 422);
                return;
            }
            $this->purchaseForm($id, $errors, ['quantity' => $quantity]);
            return;
        }

        $user = current_user();
        $unitPrice = (float)$product['price'];
        $lineTotal = number_format($unitPrice * $quantity, 3, '.', '');

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('SELECT quantity_available FROM products WHERE id = :id FOR UPDATE');
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch();
            if (!$row || (int)$row['quantity_available'] < $quantity) {
                throw new RuntimeException('Insufficient stock.');
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO transactions (user_id, total_amount) VALUES (:user_id, :total_amount)'
            );
            $stmt->execute([
                ':user_id' => $user['id'] ?? null,
                ':total_amount' => $lineTotal,
            ]);
            $transactionId = (int)$this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare(
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

            $stmt = $this->pdo->prepare(
                'UPDATE products SET quantity_available = quantity_available - :quantity WHERE id = :id'
            );
            $stmt->execute([':quantity' => $quantity, ':id' => $id]);

            $this->pdo->commit();

            if ($this->wantsJson()) {
                $this->json([
                    'message' => 'Purchase completed.',
                    'transaction_id' => $transactionId,
                ]);
                return;
            }

            $basePath = base_path();
            header('Location: ' . $basePath . '/products/' . $id . '/purchase?message=Purchase%20completed.');
            exit;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            $errors['general'] = 'Purchase failed. Please try again.';
            if ($this->wantsJson()) {
                $this->json(['errors' => $errors], 500);
                return;
            }
            $this->purchaseForm($id, $errors, ['quantity' => $quantity]);
        }
    }

    private function sanitizeProductInput(array $input): array
    {
        return [
            'name' => trim($input['name'] ?? ''),
            'price' => trim($input['price'] ?? ''),
            'quantity_available' => (int)($input['quantity_available'] ?? 0),
        ];
    }

    private function validateProduct(array $data): array
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

    private function findProduct(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, price, quantity_available FROM products WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $product = $stmt->fetch();
        return $product ?: null;
    }

    private function notFound(): void
    {
        if ($this->wantsJson()) {
            $this->json(['error' => 'Not Found'], 404);
            return;
        }
        http_response_code(404);
        echo 'Not Found';
        exit;
    }

    private function wantsJson(): bool
    {
        if (isset($_GET['format']) && $_GET['format'] === 'json') {
            return true;
        }
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return stripos($accept, 'application/json') !== false;
    }

    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
    }
}
