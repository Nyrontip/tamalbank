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
function jsonResponse(array $data, int $code = 0): void {
    // Only set code if explicitly provided (non-zero)
    if ($code > 0) {
        http_response_code($code);
    }
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

$publicEndpoints = ['auth', 'status', 'products'];

// Route exists check - if endpoint doesn't match any case, it will be caught by default
$validEndpoints = ['auth', 'account', 'products', 'expenses', 'tamalbits', 'status'];

// Require auth for protected routes
if (!empty($endpoint) && !in_array($endpoint, $publicEndpoints)) {
    if (!in_array($endpoint, $validEndpoints)) {
        // Endpoint doesn't exist - 404 before auth check
        http_response_code(404);
        jsonResponse([
            'error' => 'Not Found',
            'message' => 'Endpoint not found: ' . $endpoint
        ]);
    }
    if (empty($personId)) {
        http_response_code(401);
        jsonResponse([
            'error' => 'Unauthorized',
            'message' => 'X-Person-Id header is required'
        ]);
    }
}

// Route handling
try {
    switch ($endpoint) {
        // Auth
        case 'auth':
            if ($requestMethod !== 'POST') {
                http_response_code(405);
                jsonResponse(['error' => 'Method Not Allowed']);
            }
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
            
        // Account
        case 'account':
            if ($requestMethod !== 'GET' && $requestMethod !== 'POST') {
                http_response_code(405);
                jsonResponse(['error' => 'Method Not Allowed']);
            }
            require_once __DIR__ . '/api/account.php';
            if ($requestMethod === 'GET') {
                jsonResponse(handleGetAccount($personId));
            } else {
                jsonResponse(handleDeductAccount($personId));
            }
            break;
            
        // Products
        case 'products':
            if ($requestMethod !== 'GET') {
                http_response_code(405);
                jsonResponse(['error' => 'Method Not Allowed']);
            }
            require_once __DIR__ . '/api/products.php';
            if ($id) {
                jsonResponse(handleGetProduct((int) $id));
            } else {
                jsonResponse(handleGetProducts());
            }
            break;
            
        // Expenses
        case 'expenses':
            if ($requestMethod !== 'GET' && $requestMethod !== 'POST') {
                http_response_code(405);
                jsonResponse(['error' => 'Method Not Allowed']);
            }
            require_once __DIR__ . '/api/expenses.php';
            if ($requestMethod === 'GET') {
                jsonResponse(handleGetExpenses($personId));
            } else {
                jsonResponse(handleCreateExpense($personId));
            }
            break;
            
        // Tamalbits
        case 'tamalbits':
            if ($requestMethod !== 'GET') {
                http_response_code(405);
                jsonResponse(['error' => 'Method Not Allowed']);
            }
            require_once __DIR__ . '/api/tamalbits.php';
            jsonResponse(handleGetTamalbits($personId));
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