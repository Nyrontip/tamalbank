<?php
declare(strict_types=1);

/**
 * ExpenseController - Handles expense endpoints
 */
class ExpenseController
{
    private ExpenseService $service;

    public function __construct(ExpenseService $service)
    {
        $this->service = $service;
    }

    public function create(Request $request, string $personId): void
    {
        $productId = $request->getInput('product_id');
        $type = $request->getInput('type');
        $description = $request->getInput('description') ?? '';

        try {
            $result = $this->service->create($personId, (int) $productId, $type, $description);
            
            if (!$result['success']) {
                Response::error($result['error'], 422);
                return;
            }

            Response::json($result);
        } catch (ValidationException $e) {
            Response::error($e->getMessage(), 400);
        } catch (NotFoundException $e) {
            Response::error($e->getMessage(), 404);
        } catch (AppException $e) {
            Response::error($e->getMessage(), $e->getStatusCode());
        }
    }

    public function list(Request $request, string $personId): void
    {
        $limit = (int) $request->getQueryParam('limit', 20);
        $offset = (int) $request->getQueryParam('offset', 0);
        $type = $request->getQueryParam('type');

        $result = $this->service->getAll($personId, $limit, $offset, $type);
        Response::json($result);
    }
}