<?php
/**
 * Router - TamalBank API
 */

$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];
$personId = $_SERVER['HTTP_X_PERSON_ID'] ?? '';

// Remove query string and /api prefix
$path = parse_url($requestUri, PHP_URL_PATH);
$path = str_replace('/api', '', $path);
$path = rtrim($path, '/');

// Parse route: /endpoint/{id}
$parts = explode('/', trim($path, '/'));
$endpoint = $parts[0] ?? '';
$id = $parts[1] ?? null;

// Response helper
function jsonResponse(array $data, int $code = 0): void {
    if ($code > 0) http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

$publicEndpoints = ['auth', 'status', 'products'];
$validEndpoints = ['auth', 'account', 'products', 'expenses', 'tamalbits', 'status'];
$authRequiredEndpoints = ['expenses', 'tamalbits'];

// Basic routing check
if (!empty($endpoint) && !in_array($endpoint, $publicEndpoints)) {
    // 404 if endpoint doesn't exist
    if (!in_array($endpoint, $validEndpoints)) {
        http_response_code(404);
        jsonResponse(['error' => 'Not Found', 'message' => 'Endpoint not found']);
    }
    // Require X-Person-Id header
    if (in_array($endpoint, $authRequiredEndpoints)) {
        if (empty($personId)) {
            http_response_code(401);
            jsonResponse(['error' => 'Unauthorized', 'message' => 'X-Person-Id header required']);
        }
    }
}

// Route handling
try {
    switch ($endpoint) {
        // Auth - POST only
        case 'auth':
            if ($requestMethod !== 'POST') {
                http_response_code(405);
                jsonResponse(['error' => 'Method Not Allowed']);
            }
            require_once __DIR__ . '/api/auth.php';
            $result = handleLogin();
            $statusCode = isset($result['user_exists']) ? ($result['user_exists'] ? 200 : 404) : 200;
            header('Content-Type: application/json');
            http_response_code($statusCode);
            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;

        // Account - GET and POST
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

        // Products - GET only
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

        // Expenses - GET and POST
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

        // Tamalbits - GET only
        case 'tamalbits':
            if ($requestMethod !== 'GET') {
                http_response_code(405);
                jsonResponse(['error' => 'Method Not Allowed']);
            }
            require_once __DIR__ . '/api/tamalbits.php';
            jsonResponse(handleGetTamalbits($personId));
            break;

        // Status - health check
        case 'status':
            $dbOk = false;
            $apiOk = false;
            try {
                require_once __DIR__ . '/lib/db.php';
                $db = getDb();
                $db->query('SELECT 1');
                $dbOk = true;
            } catch (Exception $e) {}
            try {
                require_once __DIR__ . '/lib/api-client.php';
                $resp = callBankApi('GET', '/api/account/240420241036');
                $apiOk = ($resp['status'] === 200);
            } catch (Exception $e) {}
            $overallStatus = ($dbOk && $apiOk) ? 'ok' : 'degraded';
            $httpCode = ($dbOk && $apiOk) ? 200 : 503;
            header('Content-Type: application/json');
            http_response_code($httpCode);
            echo json_encode([
                'status' => $overallStatus,
                'timestamp' => date('c'),
                'version' => '1.0.0',
                'checks' => [
                    'database' => $dbOk ? 'ok' : 'error',
                    'external_api' => $apiOk ? 'ok' : 'error'
                ]
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;

        // Fallback
        default:
            http_response_code(404);
            jsonResponse(['error' => 'Not Found', 'message' => 'Endpoint not found']);
    }
} catch (Exception $e) {
    http_response_code(500);
    jsonResponse(['error' => 'Internal Server Error', 'message' => $e->getMessage()]);
}

http_response_code(404);
jsonResponse(['error' => 'Not Found']);