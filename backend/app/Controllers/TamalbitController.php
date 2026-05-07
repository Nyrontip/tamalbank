<?php
declare(strict_types=1);

/**
 * TamalbitController - Handles tamalbits endpoints
 */
class TamalbitController
{
    private TamalbitService $service;

    public function __construct(TamalbitService $service)
    {
        $this->service = $service;
    }

    public function getTotal(Request $request, string $personId): void
    {
        $result = $this->service->getTotal($personId);
        Response::json($result);
    }
}