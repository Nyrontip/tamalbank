<?php
/**
 * Tamalbits - Calculate total Tamalbits
 */

require_once __DIR__ . '/../lib/db.php';

function handleGetTamalbits(string $personId): array {
    $db = getDb();
    
    // Calculate Tamalbits from all movements where product gives tamalbits
    $stmt = $db->prepare("
        SELECT SUM(ABS(m.amount) / 10) as total
        FROM movements m
        JOIN products p ON m.product_id = p.id
        WHERE m.person_id = :person_id AND p.gives_tamalbits = true AND m.api_deducted = true
    ");
    
    $stmt->execute(['person_id' => $personId]);
    $result = $stmt->fetch();
    
    $total = $result['total'] ? floor((float) $result['total']) : 0;
    
    // Get products that give Tamalbits
    $productStmt = $db->query("SELECT name FROM products WHERE gives_tamalbits = true");
    $productsThatGive = [];
    while ($row = $productStmt->fetch()) {
        $productsThatGive[] = $row['name'];
    }
    
    return [
        'tamalbits_total' => $total,
        'calculation' => 'floor(amount / 10)',
        'rule' => '1 Tamalbit por cada $10 gastados en productos con gives_tamalbits=true',
        'products_that_give' => $productsThatGive,
    ];
}