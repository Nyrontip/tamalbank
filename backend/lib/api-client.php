<?php
/**
 * Bank API Client - Consumes external API on port 8083
 */

function callBankApi(string $method, string $endpoint, ?array $data = null): array {
    $url = getenv('BANK_API_URL') ?: 'http://host.docker.internal:8083';
    $url .= $endpoint;
    
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
    ]);
    
    switch (strtoupper($method)) {
        case 'GET':
            break;
        case 'POST':
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
            break;
        case 'PUT':
            curl_setopt($ch, CURLOPT_CUSTOM, 'PUT');
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
            break;
        case 'DELETE':
            curl_setopt($ch, CURLOPT_CUSTOM, 'DELETE');
            break;
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception("Bank API Error: $error");
    }
    
    $decoded = json_decode($response, true);
    
    return [
        'status' => $httpCode,
        'data' => $decoded ?? [],
    ];
}

/**
 * Get account balance from external API
 */
function getAccountBalance(string $personId): array {
    $response = callBankApi('GET', "/api/account/$personId");
    
    if ($response['status'] === 200) {
        return [
            'person_id' => $personId,
            'balance' => (float) ($response['data']['balance'] ?? 0),
            'currency' => $response['data']['currency'] ?? 'USD',
        ];
    }
    
    return [
        'person_id' => $personId,
        'balance' => 0,
        'currency' => 'USD',
    ];
}

/**
 * Deduct amount from account
 */
function deductFromAccount(string $personId, float $amount, string $reason): array {
    $response = callBankApi('POST', "/api/account/$personId/deduct", [
        'amount' => $amount,
        'reason' => $reason,
    ]);
    
    if ($response['status'] === 200) {
        return [
            'success' => true,
            'transaction_id' => $response['data']['transactionId'] ?? uniqid('txn_'),
            'previous_balance' => (float) ($response['data']['previousBalance'] ?? 0),
            'new_balance' => (float) ($response['data']['newBalance'] ?? 0),
            'amount_deducted' => $amount,
        ];
    }
    
    return [
        'success' => false,
        'error' => $response['data']['message'] ?? 'Failed to deduct',
        'status' => $response['status'],
    ];
}