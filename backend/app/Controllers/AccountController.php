<?php
declare(strict_types=1);

/**
 * AccountController - Handles account endpoints
 */
class AccountController
{
    private AccountService $service;

    public function __construct(AccountService $service)
    {
        $this->service = $service;
    }

    public function getBalance(Request $request, string $personId): void
    {
        try {
            $result = $this->service->getBalance($personId);
            Response::json($result);
        } catch (ExternalApiException $e) {
            Response::error($e->getMessage(), 502);
        }
    }

    public function deduct(Request $request, string $personId): void
    {
        try {
            $amount = $request->getInput('amount');
            $reason = $request->getInput('reason');

            $result = $this->service->deduct($personId, (float) $amount, $reason);

            if (!$result['success']) {
                Response::error($result['error'], $result['status'] ?? 422);
                return;
            }

            Response::json($result);
        } catch (ValidationException $e) {
            Response::error($e->getMessage(), 400);
        } catch (ExternalApiException $e) {
            Response::error($e->getMessage(), 502);
        }
    }
}