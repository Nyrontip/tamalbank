<?php
declare(strict_types=1);

/**
 * AuthMiddleware - Validates user authentication
 */
class AuthMiddleware
{
    private BankApiClient $bankApi;

    public function __construct(BankApiClient $bankApi)
    {
        $this->bankApi = $bankApi;
    }

    /**
     * Validate that person_id exists in the system
     * @return string The validated person_id
     * @throws UnauthorizedException If person_id is missing or invalid
     */
    public function validate(Request $request): string
    {
        // Try to get from path param first, then header
        $personId = $request->getQueryParam('person_id') ?: $request->getHeader('X-Person-Id');

        if (empty($personId)) {
            throw new UnauthorizedException('person_id in path or X-Person-Id header required');
        }

        // Verify user exists in external API
        try {
            $this->bankApi->getAccountBalance($personId);
        } catch (NotFoundException $e) {
            throw new NotFoundException("Usuario no encontrado: $personId");
        } catch (ExternalApiException $e) {
            throw new ExternalApiException('Failed to validate user');
        }

        return $personId;
    }

    /**
     * Check if endpoint requires authentication
     */
    public static function requiresAuth(string $endpoint): bool
    {
        return in_array($endpoint, ['expenses', 'tamalbits', 'account']);
    }
}