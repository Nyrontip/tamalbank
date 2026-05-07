<?php
/**
 * Account - Proxy to Bank API
 */

require_once __DIR__ . '/../lib/api-client.php';

function handleGetAccount(string $personId): array {
    try {
        return getAccountBalance($personId);
    } catch (Exception $e) {
        http_response_code(502);
        return ['error' => 'Bad Gateway', 'message' => $e->getMessage()];
    }
}

function handleDeductAccount(string $personId): array {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $amount = (float) ($input['amount'] ?? 0);
    $reason = trim($input['reason'] ?? '');
    
    if ($amount <= 0) {
        http_response_code(400);
        return ['success' => false, 'error' => 'Amount must be greater than 0'];
    }
    
    if (empty($reason)) {
        http_response_code(400);
        return ['success' => false, 'error' => 'Reason is required'];
    }
    
    try {
        $result = deductFromAccount($personId, $amount, $reason);
        
        if (!$result['success']) {
            http_response_code($result['status'] ?? 422);
            return $result;
        }
        
        return $result;
    } catch (Exception $e) {
        http_response_code(502);
        return ['success' => false, 'error' => 'Bad Gateway', 'message' => $e->getMessage()];
    }
}