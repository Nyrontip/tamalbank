<?php
/**
 * Products - Get available products
 */

require_once __DIR__ . '/../lib/db.php';

function handleGetProducts(): array {
    $db = getDb();
    
    $type = $_GET['type'] ?? null;
    
    if ($type) {
        $stmt = $db->prepare('SELECT id, name, type, balance, gives_tamalbits, created_at FROM products WHERE type = :type');
        $stmt->execute(['type' => $type]);
    } else {
        $stmt = $db->query('SELECT id, name, type, balance, gives_tamalbits, created_at FROM products');
    }
    
    $products = [];
    while ($row = $stmt->fetch()) {
        $products[] = [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'type' => $row['type'],
            'balance' => (float) $row['balance'],
            'gives_tamalbits' => (bool) $row['gives_tamalbits'],
        ];
    }
    
    return ['products' => $products];
}

function handleGetProduct(int $id): array {
    $db = getDb();
    
    $stmt = $db->prepare('SELECT id, name, type, balance, gives_tamalbits FROM products WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        http_response_code(404);
        return ['error' => 'Product not found'];
    }
    
    return [
        'id' => (int) $product['id'],
        'name' => $product['name'],
        'type' => $product['type'],
        'balance' => (float) $product['balance'],
        'gives_tamalbits' => (bool) $product['gives_tamalbits'],
    ];
}