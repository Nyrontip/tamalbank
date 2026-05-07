<?php
/**
 * Router - TamalBank API
 */

$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$personId = $_SERVER['HTTP_X_PERSON_ID'] ?? '';

// Debug - remove in production
error_log("REQUEST_URI: $requestUri, METHOD: $requestMethod");

// Remove query string and /api prefix
$path = parse_url($requestUri, PHP_URL_PATH);
$path = str_replace('/api', '', $path);
$path = rtrim($path, '/');

// Debug
error_log("PATH after remove /api: $path");

// Parse route: /endpoint/{id}
$parts = explode('/', trim($path, '/'));
$endpoint = $parts[0] ?? '';
$id = $parts[1] ?? null;

// Response helper
function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Require auth for protected routes
$protected = !in_array($endpoint, ['auth', 'status', 'products', '']);

if ($protected && empty($personId)) {
    http_response_code(401);
    jsonResponse([
        'error' => 'Unauthorized',
        'message' => 'X-Person-Id header is required'
    ]);
}

// Route handling
try {
    switch ($endpoint) {
        // Auth
        case 'auth':
            if ($requestMethod === 'POST') {
                require_once __DIR__ . '/api/auth.php';
                $result = handleLogin();
                // Check for 404 in response
                if (isset($result['user_exists'])) {
                    $statusCode = $result['user_exists'] ? 200 : 404;
                } else {
                    $statusCode = 200;
                }
                header('Content-Type: application/json');
                http_response_code($statusCode);
                echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                exit;
            }
            break;
            
        // Account
        case 'account':
            if ($requestMethod === 'GET') {
                require_once __DIR__ . '/api/account.php';
                jsonResponse(handleGetAccount($personId));
            } elseif ($requestMethod === 'POST') {
                require_once __DIR__ . '/api/account.php';
                jsonResponse(handleDeductAccount($personId));
            }
            break;
            
        // Products
        case 'products':
            require_once __DIR__ . '/api/products.php';
            if ($requestMethod === 'GET') {
                if ($id) {
                    jsonResponse(handleGetProduct((int) $id));
                } else {
                    jsonResponse(handleGetProducts());
                }
            }
            break;
            
        // Expenses
        case 'expenses':
            require_once __DIR__ . '/api/expenses.php';
            if ($requestMethod === 'GET') {
                jsonResponse(handleGetExpenses($personId));
            } elseif ($requestMethod === 'POST') {
                jsonResponse(handleCreateExpense($personId));
            }
            break;
            
        // Tamalbits
        case 'tamalbits':
            require_once __DIR__ . '/api/tamalbits.php';
            if ($requestMethod === 'GET') {
                jsonResponse(handleGetTamalbits($personId));
            }
            break;
            
        // Status
        case 'status':
            if ($requestMethod === 'GET') {
                $config = include __DIR__ . '/config.php';
                jsonResponse([
                    'status' => 'ok',
                    'timestamp' => date('c'),
                    'version' => $config['version'],
                ]);
            }
            break;
            
        // Fallback - 404
        default:
            http_response_code(404);
            jsonResponse([
                'error' => 'Not Found',
                'message' => 'Endpoint not found: ' . $endpoint
            ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    jsonResponse([
        'error' => 'Internal Server Error',
        'message' => $e->getMessage()
    ]);
}

// If no route matched, 404
http_response_code(404);
jsonResponse(['error' => 'Not Found']);