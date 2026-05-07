<?php
declare(strict_types=1);

/**
 * Application - Bootstrap and DI Container setup
 */
class Application
{
    private Container $container;
    private Request $request;

    public function __construct()
    {
        $this->container = new Container();
        $this->request = new Request();
        $this->registerServices();
    }

    private function registerServices(): void
    {
        // Core - factories
        $this->container->set('db', fn($c) => new Database());
        $this->container->set('bankApi', fn($c) => new BankApiClient());

        // Repositories - use make() to get fresh DB instance per request
        $this->container->set('productRepository', fn($c) => new ProductRepository($c->make('db')));
        $this->container->set('movementRepository', fn($c) => new MovementRepository($c->make('db')));

        // Services
        $this->container->set('productService', fn($c) => new ProductService($c->get('productRepository')));
        $this->container->set('authService', fn($c) => new AuthService($c->get('bankApi')));
        $this->container->set('accountService', fn($c) => new AccountService($c->get('bankApi')));
        $this->container->set('tamalbitService', fn($c) => new TamalbitService(
            $c->get('movementRepository'),
            $c->get('productRepository')
        ));
        $this->container->set('expenseService', fn($c) => new ExpenseService(
            $c->make('db'),
            $c->get('productRepository'),
            $c->get('movementRepository'),
            $c->get('accountService')
        ));

        // Middleware
        $this->container->set('authMiddleware', fn($c) => new AuthMiddleware($c->get('bankApi')));
        $this->container->set('errorHandler', fn($c) => new ErrorHandlerMiddleware());

        // Controllers
        $this->container->set('authController', fn($c) => new AuthController($c->get('authService')));
        $this->container->set('accountController', fn($c) => new AccountController($c->get('accountService')));
        $this->container->set('expenseController', fn($c) => new ExpenseController($c->get('expenseService')));
        $this->container->set('productController', fn($c) => new ProductController($c->get('productService')));
        $this->container->set('tamalbitController', fn($c) => new TamalbitController($c->get('tamalbitService')));
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function run(): void
    {
        // Activate error handling
        $this->container->get('errorHandler')->handle();

        // Parse route
        $path = $this->request->getPath();
        $path = str_replace('/api', '', $path);
        $path = rtrim($path, '/');
        $parts = explode('/', trim($path, '/'));
        
        $endpoint = $parts[0] ?? '';
        $id = $parts[1] ?? null;
        $action = $parts[2] ?? null; // e.g., "deduct" in /account/{id}/deduct
        $httpMethod = $this->request->getMethod();

        // Route to appropriate controller
        $this->dispatch($endpoint, $id, $action, $httpMethod);
    }

    private function dispatch(string $endpoint, ?string $id, ?string $action, string $httpMethod): void
    {
        // Health check endpoint (no auth required)
        if ($endpoint === 'status') {
            $this->handleStatus();
            return;
        }

        // Public endpoints (no auth required)
        $publicEndpoints = ['auth', 'products'];
        
        // Protected endpoints (auth required) - take ID from URL path
        if (!in_array($endpoint, $publicEndpoints)) {
            $personId = $id;
            if (empty($personId)) {
                Response::error('person_id required in URL path', 401);
                return;
            }
            // Verify user exists in external API
            try {
                $bankApi = $this->container->get('bankApi');
                $bankApi->getAccountBalance($personId);
            } catch (NotFoundException $e) {
                Response::error("Usuario no encontrado: $personId", 404);
                return;
            } catch (ExternalApiException $e) {
                Response::error('Failed to validate user', 502);
                return;
            }
        } else {
            $personId = null;
        }

        // Route to controller
        switch ($endpoint) {
            case 'auth':
                $this->handleAuth($httpMethod);
                break;
            case 'products':
                $this->handleProducts($httpMethod, $id);
                break;
            case 'account':
                $this->handleAccount($httpMethod, $personId, $action);
                break;
            case 'expenses':
                $this->handleExpenses($httpMethod, $personId);
                break;
            case 'tamalbits':
                $this->handleTamalbits($httpMethod, $personId);
                break;
            default:
                Response::error('Not Found', 404);
        }
    }

    private function handleAuth(string $method): void
    {
        if ($method !== 'POST') {
            Response::error('Method Not Allowed', 405);
            return;
        }
        $this->container->get('authController')->login($this->request);
    }

    private function handleProducts(string $method, ?string $id): void
    {
        if ($method !== 'GET') {
            Response::error('Method Not Allowed', 405);
            return;
        }
        
        if ($id) {
            $this->container->get('productController')->get($this->request, (int) $id);
        } else {
            $this->container->get('productController')->list($this->request);
        }
    }

    private function handleAccount(string $method, ?string $personId, ?string $action = null): void
    {
        if (!$personId) {
            Response::error('person_id required in URL', 401);
            return;
        }

        $controller = $this->container->get('accountController');

        // Route based on action or HTTP method
        if ($action === 'deduct' && $method === 'POST') {
            $controller->deduct($this->request, $personId);
        } elseif ($method === 'GET' && !$action) {
            $controller->getBalance($this->request, $personId);
        } else {
            Response::error('Method Not Allowed', 405);
        }
    }

    private function handleExpenses(string $method, ?string $personId): void
    {
        if ($method !== 'GET' && $method !== 'POST') {
            Response::error('Method Not Allowed', 405);
            return;
        }

        if (!$personId) {
            Response::error('person_id required', 401);
            return;
        }

        $controller = $this->container->get('expenseController');
        
        if ($method === 'GET') {
            $controller->list($this->request, $personId);
        } else {
            $controller->create($this->request, $personId);
        }
    }

    private function handleTamalbits(string $method, ?string $personId): void
    {
        if ($method !== 'GET') {
            Response::error('Method Not Allowed', 405);
            return;
        }

        if (!$personId) {
            Response::error('person_id required', 401);
            return;
        }

        $this->container->get('tamalbitController')->getTotal($this->request, $personId);
    }

    private function handleStatus(): void
    {
        $dbOk = false;
        $apiOk = false;

        try {
            $db = $this->container->make('db');
            $db->query('SELECT 1');
            $dbOk = true;
        } catch (Exception $e) {}

        try {
            $api = $this->container->get('bankApi');
            $resp = $api->request('GET', '/api/account/240420241036');
            $apiOk = ($resp['status'] === 200);
        } catch (Exception $e) {}

        $overallStatus = ($dbOk && $apiOk) ? 'ok' : 'degraded';
        $httpCode = ($dbOk && $apiOk) ? 200 : 503;

        Response::json([
            'status' => $overallStatus,
            'timestamp' => date('c'),
            'version' => '1.0.0',
            'checks' => [
                'database' => $dbOk ? 'ok' : 'error',
                'external_api' => $apiOk ? 'ok' : 'error',
            ],
        ], $httpCode);
    }
}