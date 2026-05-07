<?php
declare(strict_types=1);

/**
 * AccountService - Business logic for account operations
 */
class AccountService
{
    private BankApiClient $bankApi;

    public function __construct(BankApiClient $bankApi)
    {
        $this->bankApi = $bankApi;
    }

    public function getBalance(string $personId): array
    {
        try {
            return $this->bankApi->getAccountBalance($personId);
        } catch (ExternalApiException $e) {
            throw new ExternalApiException('Failed to get balance: ' . $e->getMessage(), $e);
        }
    }

    public function deduct(string $personId, float $amount, string $reason): array
    {
        if ($amount <= 0) {
            throw new ValidationException('Amount must be greater than 0');
        }

        if (empty(trim($reason))) {
            throw new ValidationException('Reason is required');
        }

        try {
            $result = $this->bankApi->deductFromAccount($personId, $amount, $reason);
            
            if (!$result['success']) {
                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Failed to deduct',
                    'status' => $result['status'] ?? 422,
                ];
            }

            return $result;
        } catch (ExternalApiException $e) {
            throw new ExternalApiException('Failed to deduct from account: ' . $e->getMessage(), $e);
        }
    }
}