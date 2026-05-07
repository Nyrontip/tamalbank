<?php
declare(strict_types=1);

/**
 * ExpenseService - Business logic for expenses
 * IMPORTANTE: Usa transacciones para garantizar consistencia atómica
 */
class ExpenseService
{
    private Database $db;
    private ProductRepository $productRepository;
    private MovementRepository $movementRepository;
    private AccountService $accountService;

    public function __construct(
        Database $db,
        ProductRepository $productRepository,
        MovementRepository $movementRepository,
        AccountService $accountService
    ) {
        $this->db = $db;
        $this->productRepository = $productRepository;
        $this->movementRepository = $movementRepository;
        $this->accountService = $accountService;
    }

    public function create(string $personId, int $productId, string $type, string $description = ''): array
    {
        // Validaciones
        $this->validateCreate($productId, $type);

        // Obtener producto
        $product = $this->productRepository->findById($productId);
        if (!$product) {
            throw new NotFoundException('Product not found');
        }

        $amount = $product['balance'];
        $productName = $product['name'];
        $givesTamalbits = $product['gives_tamalbits'];

        // Obtener balance antes de la operación
        $balanceBefore = $this->accountService->getBalance($personId);
        $previousBalance = $balanceBefore['balance'];

        // Verificar fondos suficientes
        if ($previousBalance < $amount) {
            throw new ValidationException('Insufficient balance');
        }

        // ========== TRANSACCIÓN ATÓMICA ==========
        $this->db->begin();
        
        try {
            // 1. Deduct from external API
            $deductResult = $this->accountService->deduct(
                $personId, 
                $amount, 
                $description ?: "Compra: $productName"
            );

            if (!$deductResult['success']) {
                $this->db->rollback();
                return [
                    'success' => false,
                    'error' => $deductResult['error'] ?? 'Failed to deduct',
                ];
            }

            // 2. Guardar movement en DB (solo si el deduction fue exitoso)
            $movementId = $this->movementRepository->create([
                'product_id' => $productId,
                'person_id' => $personId,
                'amount' => -$amount, // negativo para expense
                'type' => $type,
                'description' => $description,
                'api_transaction_id' => $deductResult['transaction_id'],
                'api_deducted' => true,
            ]);

            // Commit de la transacción
            $this->db->commit();

            // Calcular Tamalbits
            $tamalbitsEarned = $givesTamalbits ? floor($amount / 10) : 0;

            return [
                'success' => true,
                'expense' => [
                    'id' => $movementId,
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

        } catch (Exception $e) {
            // Rollback en caso de cualquier error
            $this->db->rollback();
            throw new AppException('Failed to create expense: ' . $e->getMessage(), 500, $e);
        }
    }

    public function getAll(string $personId, int $limit = 20, int $offset = 0, ?string $type = null): array
    {
        if ($limit > 100) {
            $limit = 100;
        }

        $expenses = $this->movementRepository->findByPersonId($personId, $limit, $offset, $type);
        $total = $this->movementRepository->countByPersonId($personId, $type);
        
        // Calcular total tamalbits de los resultados
        $totalTamalbits = array_sum(array_map(fn($e) => $e['tamalbits_earned'], $expenses));

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

    private function validateCreate(int $productId, string $type): void
    {
        if (!$productId) {
            throw new ValidationException('product_id is required');
        }

        if (empty(trim($type))) {
            throw new ValidationException('type is required');
        }
    }
}