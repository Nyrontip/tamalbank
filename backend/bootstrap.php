<?php
/**
 * Bootstrap - Autoloads all application classes
 */

spl_autoload_register(function ($class) {
    $basePath = __DIR__ . '/app/';
    
    // Map class names to file paths
    $classMap = [
        // Core
        'Request' => 'Core/Request.php',
        'Response' => 'Core/Response.php',
        'Container' => 'Core/Container.php',
        'Database' => 'Core/Database.php',
        'BankApiClient' => 'Core/BankApiClient.php',
        
        // Exceptions
        'AppException' => 'Exceptions/AppException.php',
        'ValidationException' => 'Exceptions/AppException.php',
        'NotFoundException' => 'Exceptions/AppException.php',
        'UnauthorizedException' => 'Exceptions/AppException.php',
        'ForbiddenException' => 'Exceptions/AppException.php',
        'ExternalApiException' => 'Exceptions/AppException.php',
        
        // Repositories
        'ProductRepository' => 'Repositories/ProductRepository.php',
        'MovementRepository' => 'Repositories/MovementRepository.php',
        
        // Services
        'ProductService' => 'Services/ProductService.php',
        'AuthService' => 'Services/AuthService.php',
        'AccountService' => 'Services/AccountService.php',
        'TamalbitService' => 'Services/TamalbitService.php',
        'ExpenseService' => 'Services/ExpenseService.php',
        
        // Middleware
        'ErrorHandlerMiddleware' => 'Middleware/ErrorHandlerMiddleware.php',
        'AuthMiddleware' => 'Middleware/AuthMiddleware.php',
        
        // Controllers
        'AuthController' => 'Controllers/AuthController.php',
        'AccountController' => 'Controllers/AccountController.php',
        'ExpenseController' => 'Controllers/ExpenseController.php',
        'ProductController' => 'Controllers/ProductController.php',
        'TamalbitController' => 'Controllers/TamalbitController.php',
        
        // Application
        'Application' => 'Application.php',
    ];
    
    if (isset($classMap[$class])) {
        require_once $basePath . $classMap[$class];
    }
});