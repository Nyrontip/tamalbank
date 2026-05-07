<?php
declare(strict_types=1);

/**
 * ErrorHandlerMiddleware - Centralized error handling
 */
class ErrorHandlerMiddleware
{
    public function handle(): void
    {
        set_error_handler([$this, 'errorHandler']);
        set_exception_handler([$this, 'exceptionHandler']);
    }

    public function errorHandler(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        // Log error silently in development, or send to logging service in production
        error_log("PHP Error [$errno]: $errstr in $errfile on line $errline");
        
        // Don't execute PHP internal error handler
        return true;
    }

    public function exceptionHandler(Throwable $exception): void
    {
        $statusCode = 500;
        $message = 'Internal Server Error';

        if ($exception instanceof AppException) {
            $statusCode = $exception->getStatusCode();
            $message = $exception->getMessage();
        } elseif ($exception instanceof PDOException) {
            // Don't expose DB errors to clients
            error_log("Database Error: " . $exception->getMessage());
            $message = 'Database error occurred';
            $statusCode = 500;
        }

        // Log full error for debugging
        error_log("Exception: " . get_class($exception) . " - " . $exception->getMessage());
        error_log("Stack trace: " . $exception->getTraceAsString());

        http_response_code($statusCode);
        header('Content-Type: application/json');
        
        // Include debug info temporarily
        $debug = [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => basename($exception->getFile()),
            'line' => $exception->getLine(),
        ];
        
        echo json_encode([
            'error' => $statusCode === 500 ? 'Internal Server Error' : 'Error',
            'message' => $message,
            'debug' => $debug,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}