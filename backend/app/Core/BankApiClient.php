<?php
declare(strict_types=1);

/**
 * BankApiClient - Consumes external Bank API
 */
class BankApiClient
{
    private string $baseUrl;

    public function __construct(?string $baseUrl = null)
    {
        $this->baseUrl = $baseUrl ?? getenv('BANK_API_URL') ?: 'http://host.docker.internal:8083';
    }

    public function request(string $method, string $endpoint, ?array $data = null): array
    {
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);

        $this->configureMethod($ch, $method, $data);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new ExternalApiException("Bank API Error: $error");
        }

        $decoded = json_decode($response, true);

        return [
            'status' => $httpCode,
            'data' => $decoded ?? [],
        ];
    }

    private function configureMethod($ch, string $method, ?array $data): void
    {
        switch (strtoupper($method)) {
            case 'GET':
                break;
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOM, 'PUT');
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOM, 'DELETE');
                break;
        }
    }

    public function getAccountBalance(string $personId): array
    {
        $response = $this->request('GET', "/api/account/$personId");

        if ($response['status'] === 200) {
            return [
                'person_id' => $personId,
                'balance' => (float) ($response['data']['balance'] ?? 0),
                'currency' => $response['data']['currency'] ?? 'USD',
            ];
        }

        if ($response['status'] === 404) {
            throw new NotFoundException("User not found: $personId");
        }

        throw new ExternalApiException($response['data']['message'] ?? 'Unknown API error');
    }

    public function deductFromAccount(string $personId, float $amount, string $reason): array
    {
        $response = $this->request('POST', "/api/account/$personId/deduct", [
            'amount' => $amount,
            'reason' => $reason,
        ]);

        if ($response['status'] === 200) {
            return [
                'success' => true,
                'transaction_id' => $response['data']['transactionId'] ?? uniqid('txn_'),
                'previous_balance' => (float) ($response['data']['previousBalance'] ?? 0),
                'new_balance' => (float) ($response['data']['newBalance'] ?? 0),
                'amount_deducted' => $amount,
            ];
        }

        return [
            'success' => false,
            'error' => $response['data']['message'] ?? 'Failed to deduct',
            'status' => $response['status'],
        ];
    }
}