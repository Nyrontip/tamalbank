<?php
declare(strict_types=1);

/**
 * ProductRepository - Data access for products
 */
class ProductRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findAll(?string $type = null): array
    {
        if ($type) {
            $stmt = $this->db->query(
                'SELECT id, name, type, balance, gives_tamalbits, created_at FROM products WHERE type = :type',
                ['type' => $type]
            );
        } else {
            $stmt = $this->db->query('SELECT id, name, type, balance, gives_tamalbits, created_at FROM products');
        }

        $products = [];
        while ($row = $stmt->fetch()) {
            $products[] = $this->mapToDto($row);
        }

        return $products;
    }

    public function findById(int $id): ?array
    {
        $row = $this->db->fetch(
            'SELECT id, name, type, balance, gives_tamalbits FROM products WHERE id = :id',
            ['id' => $id]
        );

        return $row ? $this->mapToDto($row) : null;
    }

    public function findByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $placeholders = array_map(fn($i) => ":id$i", array_keys($ids));
        $sql = "SELECT id, name, type, balance, gives_tamalbits FROM products WHERE id IN (" . implode(',', $placeholders) . ")";
        
        $params = [];
        foreach ($ids as $i => $id) {
            $params["id$i"] = $id;
        }

        $stmt = $this->db->query($sql, $params);
        $products = [];
        while ($row = $stmt->fetch()) {
            $products[] = $this->mapToDto($row);
        }

        return $products;
    }

    public function findAllThatGiveTamalbits(): array
    {
        $stmt = $this->db->query("SELECT name FROM products WHERE gives_tamalbits = true");
        $products = [];
        while ($row = $stmt->fetch()) {
            $products[] = $row['name'];
        }
        return $products;
    }

    private function mapToDto(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'type' => $row['type'],
            'balance' => (float) $row['balance'],
            'gives_tamalbits' => (bool) $row['gives_tamalbits'],
        ];
    }
}