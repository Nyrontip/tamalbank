<?php
declare(strict_types=1);

/**
 * ProductController - Handles product endpoints
 */
class ProductController
{
    private ProductService $service;

    public function __construct(ProductService $service)
    {
        $this->service = $service;
    }

    public function list(Request $request): void
    {
        $type = $request->getQueryParam('type');
        $products = $this->service->getAll($type);
        Response::json(['products' => $products]);
    }

    public function get(Request $request, int $id): void
    {
        try {
            $product = $this->service->getById($id);
            Response::json($product);
        } catch (NotFoundException $e) {
            Response::error($e->getMessage(), 404);
        }
    }
}