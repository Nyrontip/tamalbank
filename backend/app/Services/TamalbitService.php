<?php
declare(strict_types=1);

/**
 * TamalbitService - Business logic for Tamalbits
 */
class TamalbitService
{
    private MovementRepository $movementRepository;
    private ProductRepository $productRepository;

    public function __construct(MovementRepository $movementRepository, ProductRepository $productRepository)
    {
        $this->movementRepository = $movementRepository;
        $this->productRepository = $productRepository;
    }

    public function getTotal(string $personId): array
    {
        $total = $this->movementRepository->calculateTamalbitsByPersonId($personId);
        $productsThatGive = $this->productRepository->findAllThatGiveTamalbits();

        return [
            'tamalbits_total' => $total,
            'calculation' => 'floor(amount / 10)',
            'rule' => '1 Tamalbit por cada $10 gastados en productos con gives_tamalbits=true',
            'products_that_give' => $productsThatGive,
        ];
    }
}