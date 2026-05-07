<?php
declare(strict_types=1);

/**
 * ProductService - Business logic for products
 */
class ProductService
{
    private ProductRepository $repository;

    public function __construct(ProductRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAll(?string $type = null): array
    {
        return $this->repository->findAll($type);
    }

    public function getById(int $id): array
    {
        $product = $this->repository->findById($id);
        
        if (!$product) {
            throw new NotFoundException('Product not found');
        }

        return $product;
    }
}