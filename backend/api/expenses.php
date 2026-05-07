<?php
/**
 * Expenses - Register new expenses
 */

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/api-client.php';

function handleCreateExpense(string $personId): array {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $productId = (int) ($input['product_id'] ?? 0);
    $type = trim($input['type'] ?? '');
    $description = trim($input['description'] ?? '');
    
    if (!$productId) {
        http_response_code(400);
        return ['success' => false, 'error' => 'product_id is required'];
    }
    
    if (empty($type)) {
        http_response_code(400);
        return ['success' => false, 'error' => 'type is required'];
    }
    
    $db = getDb();
    
    // Get product details
    $stmt = $db->prepare('SELECT id, name, balance, gives_tamalbits FROM products WHERE id = :id');
    $stmt->execute(['id' => $productId]);
    $product = $stmt->fetch();
    
    if (!$product) {
        http_response_code(404);
        return ['success' => false, 'error' => 'Product not found'];
    }
    
    $amount = (float) $product['balance'];
    $productName = $product['name'];
    $givesTamalbits = (bool) $product['gives_tamalbits'];
    
    // Get current balance before deduction
    try {
        $balanceBefore = getAccountBalance($personId);
        $previousBalance = $balanceBefore['balance'];
    } catch (Exception $e) {
        http_response_code(502);
        return ['success' => false, 'error' => 'Failed to get balance'];
    }
    
    // Check if enough balance
    if ($previousBalance < $amount) {
        http_response_code(422);
        return ['success' => false, 'error' => 'Insufficient balance'];
    }
    
    // Deduct from account
    try {
        $deductResult = deductFromAccount($personId, $amount, $description ?: "Compra: $productName");
    } catch (Exception $e) {
        http_response_code(502);
        return ['success' => false, 'error' => 'Failed to deduct from account'];
    }
    
    if (!$deductResult['success']) {
        http_response_code(422);
        return ['success' => false, 'error' => $deductResult['error'] ?? 'Failed to deduct'];
    }
    
    // Save movement to DB
    $stmt = $db->prepare('
        INSERT INTO movements (product_id, person_id, amount, type, description, api_transaction_id, api_deducted)
        VALUES (:product_id, :person_id, :amount, :type, :description, :api_transaction_id, :api_deducted)
    ');
    
    $stmt->execute([
        'product_id' => $productId,
        'person_id' => $personId,
        'amount' => -$amount, // negative for expense
        'type' => $type,
        'description' => $description,
        'api_transaction_id' => $deductResult['transaction_id'],
        'api_deducted' => true,
    ]);
    
    $expenseId = (int) $db->lastInsertId('movements_id_seq');
    
    // Calculate Tamalbits (only if product gives tamalbits)
    $tamalbitsEarned = 0;
    if ($givesTamalbits) {
        $tamalbitsEarned = floor($amount / 10); // 1 Tamalbit per $10
    }
    
    return [
        'success' => true,
        'expense' => [
            'id' => $expenseId,
            'product_id' => $productId,
            'product_name' => $productName,
            'amount' => $amount,
            'type' => $type,
            'description' => $description,
            'api_transaction_id' => $deductResult['transaction_id'],
            'api_deducted' => true,
            'tamalbits_earned' => $tamalbitsEarned,
            'created_at' => date('c'),
        ],
        'balance' => [
            'previous' => $previousBalance,
            'current' => $deductResult['new_balance'],
        ],
        'tamalbits' => [
            'earned' => $tamalbitsEarned,
            'total' => $tamalbitsEarned,
        ],
    ];
}

function handleGetExpenses(string $personId): array {
    $db = getDb();
    
    $limit = (int) ($_GET['limit'] ?? 20);
    $offset = (int) ($_GET['offset'] ?? 0);
    $type = $_GET['type'] ?? null;
    
    if ($limit > 100) $limit = 100;
    
    $where = 'WHERE m.person_id = :person_id';
    $params = ['person_id' => $personId];
    
    if ($type) {
        $where .= ' AND m.type = :type';
        $params['type'] = $type;
    }
    
    $stmt = $db->prepare("
        SELECT m.id, m.product_id, m.amount, m.type, m.description, m.api_transaction_id, 
               m.api_deducted, m.created_at, p.name as product_name, p.gives_tamalbits
        FROM movements m
        LEFT JOIN products p ON m.product_id = p.id
        $where
        ORDER BY m.created_at DESC
        LIMIT $limit OFFSET $offset
    ");
    
    $stmt->execute($params);
    
    $expenses = [];
    $totalTamalbits = 0;
    
    while ($row = $stmt->fetch()) {
        $amount = (float) abs($row['amount']);
        $givesTamalbits = (bool) $row['gives_tamalbits'];
        $tamalbits = 0;
        
        if ($givesTamalbits) {
            $tamalbits = floor($amount / 10);
            $totalTamalbits += $tamalbits;
        }
        
        $expenses[] = [
            'id' => (int) $row['id'],
            'product_id' => (int) $row['product_id'],
            'product_name' => $row['product_name'],
            'amount' => $amount,
            'type' => $row['type'],
            'description' => $row['description'],
            'api_deducted' => (bool) $row['api_deducted'],
            'tamalbits_earned' => $tamalbits,
            'created_at' => $row['created_at'],
        ];
    }
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM movements WHERE person_id = " . $db->quote($personId);
    if ($type) {
        $countSql .= " AND type = " . $db->quote($type);
    }
    $countStmt = $db->query($countSql);
    $total = (int) $countStmt->fetch()['total'];
    
    return [
        'expenses' => $expenses,
        'pagination' => [
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
        ],
        'tamalbits' => [
            'total' => $totalTamalbits,
        ],
    ];
}