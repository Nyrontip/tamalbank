<?php
declare(strict_types=1);

/**
 * AuthController - Handles authentication endpoints
 */
class AuthController
{
    private AuthService $service;

    public function __construct(AuthService $service)
    {
        $this->service = $service;
    }

    public function login(Request $request): void
    {
        $personId = $request->getInput('person_id');

        if (!$personId) {
            Response::error('person_id is required', 400);
            return;
        }

        $result = $this->service->login($personId);
        
        $statusCode = $result['user_exists'] ? 200 : 404;
        Response::json($result, $statusCode);
    }
}