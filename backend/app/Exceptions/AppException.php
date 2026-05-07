<?php
declare(strict_types=1);

/**
 * Base Application Exception
 */
class AppException extends Exception
{
    private int $statusCode;

    public function __construct(string $message, int $statusCode = 500, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->statusCode = $statusCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}

/**
 * Validation Exception - 400 Bad Request
 */
class ValidationException extends AppException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 400);
    }
}

/**
 * Not Found Exception - 404 Not Found
 */
class NotFoundException extends AppException
{
    public function __construct(string $message = 'Resource not found')
    {
        parent::__construct($message, 404);
    }
}

/**
 * Unauthorized Exception - 401 Unauthorized
 */
class UnauthorizedException extends AppException
{
    public function __construct(string $message = 'Unauthorized')
    {
        parent::__construct($message, 401);
    }
}

/**
 * Forbidden Exception - 403 Forbidden
 */
class ForbiddenException extends AppException
{
    public function __construct(string $message = 'Forbidden')
    {
        parent::__construct($message, 403);
    }
}

/**
 * External API Exception - 502 Bad Gateway
 */
class ExternalApiException extends AppException
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 502, $previous);
    }
}