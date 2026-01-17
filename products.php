<?php
require_once __DIR__ . '/Controllers/ProductsController.php';

$controller = new ProductsController($pdo);
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

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

// Backward compatible query params.
$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($segments) {
    if ($segments[0] === 'shop') {
        $controller->shop();
        return;
    }
    if ($segments[0] === 'cart') {
        $id = isset($segments[1]) ? (int)$segments[1] : 0;
        if (($segments[1] ?? '') === 'checkout' && $method === 'POST') {
            $controller->cartCheckout();
            return;
        }
        if (($segments[1] ?? '') === 'update' && $method === 'POST') {
            $controller->cartUpdate();
            return;
        }
        if (($segments[1] ?? '') === 'add' && isset($segments[2]) && $method === 'POST') {
            $controller->cartAdd((int)$segments[2]);
            return;
        }
        if (($segments[1] ?? '') === 'remove' && isset($segments[2]) && $method === 'POST') {
            $controller->cartRemove((int)$segments[2]);
            return;
        }
        $controller->cartIndex();
        return;
    }
    if ($segments[0] === 'transactions') {
        $id = isset($segments[1]) ? (int)$segments[1] : 0;
        if ($id > 0) {
            $controller->transactionShow($id);
        } else {
            $controller->transactionsIndex();
        }
        return;
    }
    if ($segments[0] === 'products') {
        if (count($segments) === 1) {
            if ($method === 'GET') {
                $controller->index();
                return;
            }
            if ($method === 'POST') {
                $controller->store();
                return;
            }
        }

        if (($segments[1] ?? '') === 'create') {
            $controller->create();
            return;
        }

        $id = (int)($segments[1] ?? 0);
        if ($id > 0) {
            if (($segments[2] ?? '') === 'edit') {
                $controller->edit($id);
                return;
            }
            if (($segments[2] ?? '') === 'purchase') {
                if ($method === 'POST') {
                    $controller->purchase($id);
                } else {
                    $controller->purchaseForm($id);
                }
                return;
            }
            if ($method === 'POST' && ($segments[2] ?? '') === 'delete') {
                $controller->destroy($id);
                return;
            }
            if ($method === 'POST' && ($segments[2] ?? '') === 'update') {
                $controller->update($id);
                return;
            }
            if ($method === 'GET') {
                $controller->show($id);
                return;
            }
        }
    }
}

switch ($action ?: 'index') {
    case 'shop':
        $controller->shop();
        break;
    case 'cart':
        $controller->cartIndex();
        break;
    case 'cart_add':
        if ($method === 'POST') {
            $controller->cartAdd($id);
            break;
        }
        http_response_code(405);
        echo 'Method Not Allowed';
        break;
    case 'cart_remove':
        if ($method === 'POST') {
            $controller->cartRemove($id);
            break;
        }
        http_response_code(405);
        echo 'Method Not Allowed';
        break;
    case 'cart_update':
        if ($method === 'POST') {
            $controller->cartUpdate();
            break;
        }
        http_response_code(405);
        echo 'Method Not Allowed';
        break;
    case 'cart_checkout':
        if ($method === 'POST') {
            $controller->cartCheckout();
            break;
        }
        http_response_code(405);
        echo 'Method Not Allowed';
        break;
    case 'transactions':
        $controller->transactionsIndex();
        break;
    case 'transaction_show':
        $controller->transactionShow($id);
        break;
    case 'index':
        $controller->index();
        break;
    case 'show':
        $controller->show($id);
        break;
    case 'create':
        $controller->create();
        break;
    case 'store':
        if ($method === 'POST') {
            $controller->store();
            break;
        }
        header('Location: products.php?action=create');
        exit;
    case 'edit':
        $controller->edit($id);
        break;
    case 'update':
        if ($method === 'POST') {
            $controller->update($id);
            break;
        }
        header('Location: products.php?action=edit&id=' . $id);
        exit;
    case 'delete':
        if ($method === 'POST') {
            $controller->destroy($id);
            break;
        }
        header('Location: products.php?action=index');
        exit;
    case 'purchase':
        if ($method === 'POST') {
            $controller->purchase($id);
        } else {
            $controller->purchaseForm($id);
        }
        break;
    default:
        http_response_code(404);
        echo 'Not Found';
        break;
}
