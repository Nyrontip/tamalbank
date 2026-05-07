<?php
/**
 * Auth - Login / Verify User
 */

require_once __DIR__ . '/../lib/api-client.php';

function handleLogin(): array {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || empty($input['person_id'])) {
        return ['success' => false, 'message' => 'person_id is required'];
    }
    
    $personId = trim($input['person_id']);
    
    // Verify user exists via Bank API
    try {
        $account = getAccountBalance($personId);
        
        return [
            'success' => true,
            'user_exists' => true,
            'person_id' => $personId,
            'message' => 'Usuario validado correctamente',
        ];
    } catch (Exception $e) {
        http_response_code(404);
        return [
            'success' => false,
            'user_exists' => false,
            'person_id' => $personId,
            'message' => 'Usuario no encontrado en el sistema',
        ];
    }
}