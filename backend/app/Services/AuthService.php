<?php
declare(strict_types=1);

/**
 * AuthService - Business logic for authentication
 */
class AuthService
{
    private BankApiClient $bankApi;

    public function __construct(BankApiClient $bankApi)
    {
        $this->bankApi = $bankApi;
    }

    public function login(string $personId): array
    {
        $personId = trim($personId);
        
        if (empty($personId)) {
            throw new ValidationException('person_id is required');
        }

        try {
            $account = $this->bankApi->getAccountBalance($personId);
            
            return [
                'success' => true,
                'user_exists' => true,
                'person_id' => $personId,
                'message' => 'Usuario validado correctamente',
            ];
        } catch (NotFoundException $e) {
            return [
                'success' => false,
                'user_exists' => false,
                'person_id' => $personId,
                'message' => 'Usuario no encontrado en el sistema',
            ];
        }
    }
}