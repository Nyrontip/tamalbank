<?php
declare(strict_types=1);

/**
 * MovementRepository - Data access for movements
 */
class MovementRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function create(array $movement): int
    {
        $stmt = $this->db->query(
            'INSERT INTO movements (product_id, person_id, amount, type, description, api_transaction_id, api_deducted)
             VALUES (:product_id, :person_id, :amount, :type, :description, :api_transaction_id, :api_deducted)',
            [
                'product_id' => $movement['product_id'],
                'person_id' => $movement['person_id'],
                'amount' => $movement['amount'],
                'type' => $movement['type'],
                'description' => $movement['description'] ?? '',
                'api_transaction_id' => $movement['api_transaction_id'],
                'api_deducted' => $movement['api_deducted'],
            ]
        );

        return (int) $this->db->lastInsertId('movements_id_seq');
    }

    public function findByPersonId(string $personId, int $limit = 20, int $offset = 0, ?string $type = null): array
    {
        $where = 'm.person_id = :person_id';
        $params = ['person_id' => $personId, 'limit' => $limit, 'offset' => $offset];

        if ($type) {
            $where .= ' AND m.type = :type';
            $params['type'] = $type;
        }

        $stmt = $this->db->query(
            "SELECT m.id, m.product_id, m.amount, m.type, m.description, m.api_transaction_id, 
                    m.api_deducted, m.created_at, p.name as product_name, p.gives_tamalbits
             FROM movements m
             LEFT JOIN products p ON m.product_id = p.id
             WHERE $where
             ORDER BY m.created_at DESC
             LIMIT :limit OFFSET :offset",
            $params
        );

        $movements = [];
        while ($row = $stmt->fetch()) {
            $movements[] = $this->mapToDto($row);
        }

        return $movements;
    }

    public function countByPersonId(string $personId, ?string $type = null): int
    {
        $sql = 'SELECT COUNT(*) as total FROM movements WHERE person_id = :person_id';
        $params = ['person_id' => $personId];

        if ($type) {
            $sql .= ' AND type = :type';
            $params['type'] = $type;
        }

        $result = $this->db->fetch($sql, $params);
        return (int) ($result['total'] ?? 0);
    }

    public function calculateTamalbitsByPersonId(string $personId): int
    {
        $result = $this->db->fetch(
            "SELECT SUM(ABS(m.amount) / 10) as total
             FROM movements m
             JOIN products p ON m.product_id = p.id
             WHERE m.person_id = :person_id AND p.gives_tamalbits = true AND m.api_deducted = true",
            ['person_id' => $personId]
        );

        return $result && $result['total'] ? (int) floor((float) $result['total']) : 0;
    }

    private function mapToDto(array $row): array
    {
        $amount = abs((float) $row['amount']);
        $givesTamalbits = (bool) $row['gives_tamalbits'];
        $tamalbits = $givesTamalbits ? floor($amount / 10) : 0;

        return [
            'id' => (int) $row['id'],
            'product_id' => (int) $row['product_id'],
            'product_name' => $row['product_name'] ?? null,
            'amount' => $amount,
            'type' => $row['type'],
            'description' => $row['description'],
            'api_deducted' => (bool) $row['api_deducted'],
            'tamalbits_earned' => $tamalbits,
            'created_at' => $row['created_at'],
        ];
    }
}