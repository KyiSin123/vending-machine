<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Controllers/ProductsController.php';

final class ProductsControllerTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION = [];
        $_GET = [];
        $_POST = [];
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_GET = [];
        $_POST = [];
        unset($_SERVER['HTTP_ACCEPT']);
    }

    private function mockStatement(array $methods = []): PDOStatement
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('bindValue')->willReturn(true);
        if (array_key_exists('fetch', $methods)) {
            $stmt->method('fetch')->willReturn($methods['fetch']);
        }
        if (array_key_exists('fetchAll', $methods)) {
            $stmt->method('fetchAll')->willReturn($methods['fetchAll']);
        }
        if (array_key_exists('fetchColumn', $methods)) {
            $stmt->method('fetchColumn')->willReturn($methods['fetchColumn']);
        }
        return $stmt;
    }

    public function testIndexReturnsJsonWithPagination(): void
    {
        $_SESSION['user'] = ['id' => 1, 'username' => 'admin', 'role' => 'Admin'];
        $_GET = ['format' => 'json', 'sort' => 'price', 'dir' => 'asc', 'page' => 1];

        $countStmt = $this->mockStatement(['fetchColumn' => 12]);
        $dataStmt = $this->mockStatement([
            'fetchAll' => [
                ['id' => 1, 'name' => 'Coke', 'price' => '3.990', 'quantity_available' => 10],
            ],
        ]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('query')
            ->with('SELECT COUNT(*) FROM products')
            ->willReturn($countStmt);
        $pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($dataStmt);

        $controller = new ProductsController($pdo);
        ob_start();
        $controller->index();
        $output = ob_get_clean();

        $payload = json_decode($output, true);
        $this->assertSame('price', $payload['sort']);
        $this->assertSame('asc', $payload['dir']);
        $this->assertSame(12, $payload['pagination']['total']);
        $this->assertCount(1, $payload['data']);
    }

    public function testShowReturnsNotFoundJson(): void
    {
        $_SESSION['user'] = ['id' => 1, 'username' => 'admin', 'role' => 'Admin'];
        $_GET = ['format' => 'json'];

        $stmt = $this->mockStatement(['fetch' => false]);
        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $controller = new ProductsController($pdo);
        ob_start();
        $controller->show(999);
        $output = ob_get_clean();

        $payload = json_decode($output, true);
        $this->assertSame('Not Found', $payload['error']);
    }

    public function testStoreValidationErrors(): void
    {
        $_SESSION['user'] = ['id' => 1, 'username' => 'admin', 'role' => 'Admin'];
        $_GET = ['format' => 'json'];
        $_POST = ['name' => '', 'price' => '', 'quantity_available' => -1];

        $pdo = $this->createMock(PDO::class);
        $controller = new ProductsController($pdo);

        ob_start();
        $controller->store();
        $output = ob_get_clean();

        $payload = json_decode($output, true);
        $this->assertArrayHasKey('errors', $payload);
        $this->assertArrayHasKey('name', $payload['errors']);
        $this->assertArrayHasKey('price', $payload['errors']);
        $this->assertArrayHasKey('quantity_available', $payload['errors']);
    }

    public function testPurchaseInsufficientStock(): void
    {
        $_SESSION['user'] = ['id' => 2, 'username' => 'user', 'role' => 'User'];
        $_GET = ['format' => 'json'];
        $_POST = ['quantity' => 5];

        $productStmt = $this->mockStatement([
            'fetch' => ['id' => 3, 'name' => 'Water', 'price' => '0.500', 'quantity_available' => 2],
        ]);
        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($productStmt);

        $controller = new ProductsController($pdo);
        ob_start();
        $controller->purchase(3);
        $output = ob_get_clean();

        $payload = json_decode($output, true);
        $this->assertArrayHasKey('errors', $payload);
        $this->assertArrayHasKey('quantity', $payload['errors']);
    }

    public function testPurchaseSuccess(): void
    {
        $_SESSION['user'] = ['id' => 2, 'username' => 'user', 'role' => 'User'];
        $_GET = ['format' => 'json'];
        $_POST = ['quantity' => 2];

        $productStmt = $this->mockStatement([
            'fetch' => ['id' => 5, 'name' => 'Pepsi', 'price' => '6.885', 'quantity_available' => 10],
        ]);
        $lockStmt = $this->mockStatement([
            'fetch' => ['quantity_available' => 10],
        ]);
        $insertTxnStmt = $this->mockStatement();
        $insertItemStmt = $this->mockStatement();
        $updateStmt = $this->mockStatement();

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturnCallback(function (string $sql) use (
            $productStmt,
            $lockStmt,
            $insertTxnStmt,
            $insertItemStmt,
            $updateStmt
        ) {
            if (strpos($sql, 'SELECT id, name, price, quantity_available FROM products') !== false) {
                return $productStmt;
            }
            if (strpos($sql, 'SELECT quantity_available FROM products') !== false) {
                return $lockStmt;
            }
            if (strpos($sql, 'INSERT INTO transactions') !== false) {
                return $insertTxnStmt;
            }
            if (strpos($sql, 'INSERT INTO transaction_items') !== false) {
                return $insertItemStmt;
            }
            return $updateStmt;
        });
        $pdo->method('beginTransaction')->willReturn(true);
        $pdo->method('commit')->willReturn(true);
        $pdo->method('lastInsertId')->willReturn('42');

        $controller = new ProductsController($pdo);
        ob_start();
        $controller->purchase(5);
        $output = ob_get_clean();

        $payload = json_decode($output, true);
        $this->assertSame('Purchase completed.', $payload['message']);
        $this->assertSame(42, $payload['transaction_id']);
    }

    public function testStoreSuccess(): void
    {
        $_SESSION['user'] = ['id' => 1, 'username' => 'admin', 'role' => 'Admin'];
        $_GET = ['format' => 'json'];
        $_POST = ['name' => 'Coke', 'price' => '3.990', 'quantity_available' => 5];

        $insertStmt = $this->mockStatement();
        $findStmt = $this->mockStatement([
            'fetch' => ['id' => 7, 'name' => 'Coke', 'price' => '3.990', 'quantity_available' => 5],
        ]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturnCallback(function (string $sql) use ($insertStmt, $findStmt) {
            if (strpos($sql, 'INSERT INTO products') !== false) {
                return $insertStmt;
            }
            return $findStmt;
        });
        $pdo->method('lastInsertId')->willReturn('7');

        $controller = new ProductsController($pdo);
        ob_start();
        $controller->store();
        $output = ob_get_clean();

        $payload = json_decode($output, true);
        $this->assertSame('Product created.', $payload['message']);
        $this->assertSame(7, $payload['data']['id']);
    }

    public function testUpdateSuccess(): void
    {
        $_SESSION['user'] = ['id' => 1, 'username' => 'admin', 'role' => 'Admin'];
        $_GET = ['format' => 'json'];
        $_POST = ['name' => 'Pepsi', 'price' => '6.885', 'quantity_available' => 12];

        $findStmt = $this->mockStatement([
            'fetch' => ['id' => 4, 'name' => 'Pepsi', 'price' => '6.885', 'quantity_available' => 12],
        ]);
        $updateStmt = $this->mockStatement();

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturnCallback(function (string $sql) use ($findStmt, $updateStmt) {
            if (strpos($sql, 'UPDATE products SET') !== false) {
                return $updateStmt;
            }
            return $findStmt;
        });

        $controller = new ProductsController($pdo);
        ob_start();
        $controller->update(4);
        $output = ob_get_clean();

        $payload = json_decode($output, true);
        $this->assertSame('Product updated.', $payload['message']);
        $this->assertSame(4, $payload['data']['id']);
    }

    public function testDestroySuccess(): void
    {
        $_SESSION['user'] = ['id' => 1, 'username' => 'admin', 'role' => 'Admin'];
        $_GET = ['format' => 'json'];

        $findStmt = $this->mockStatement([
            'fetch' => ['id' => 9, 'name' => 'Water', 'price' => '0.500', 'quantity_available' => 10],
        ]);
        $deleteStmt = $this->mockStatement();

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturnCallback(function (string $sql) use ($findStmt, $deleteStmt) {
            if (strpos($sql, 'DELETE FROM products') !== false) {
                return $deleteStmt;
            }
            return $findStmt;
        });

        $controller = new ProductsController($pdo);
        ob_start();
        $controller->destroy(9);
        $output = ob_get_clean();

        $payload = json_decode($output, true);
        $this->assertSame('Product deleted.', $payload['message']);
    }
}
