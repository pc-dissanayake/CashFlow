<?php
declare(strict_types=1);

class Router {
    private array $routes = [];
    private ?string $matchedRoute = null;
    
    public function addRoute(string $method, string $path, array $handler): void {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }
    
    public function dispatch(): void {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        foreach ($this->routes as $route) {
            $params = [];
            if ($route['method'] === $method && $this->matchRoute($route['path'], $path, $params)) {
                $this->matchedRoute = $route['path'];
                [$controller, $action] = $route['handler'];
                $instance = new $controller();
                // Cast numeric params to int
                foreach ($params as &$param) {
                    if (is_numeric($param)) {
                        $param = (int)$param;
                    }
                }
                unset($param);
                call_user_func_array([$instance, $action], $params);
                return;
            }
        }
        
        // No route found
        http_response_code(404);
        require BASE_PATH . '/src/Views/404.php';
    }
    
    private function matchRoute(string $routePath, string $requestPath, array &$params = []): bool {
        $routeParts = explode('/', trim($routePath, '/'));
        $requestParts = explode('/', trim($requestPath, '/'));
        
        if (count($routeParts) !== count($requestParts)) {
            return false;
        }
        
        $params = [];
        for ($i = 0; $i < count($routeParts); $i++) {
            if (strpos($routeParts[$i], ':') === 0) {
                $params[] = $requestParts[$i]; // Use numeric array for call_user_func_array
                continue;
            }
            
            if ($routeParts[$i] !== $requestParts[$i]) {
                return false;
            }
        }
        
        return true;
    }
}

// Initialize router
$router = new Router();

// Define routes
$router->addRoute('GET', '/', ['Controllers\HomeController', 'index']);
$router->addRoute('GET', '/login', ['Controllers\AuthController', 'loginForm']);
$router->addRoute('POST', '/login', ['Controllers\AuthController', 'login']);
$router->addRoute('GET', '/register', ['Controllers\AuthController', 'registerForm']);
$router->addRoute('POST', '/register', ['Controllers\AuthController', 'register']);
$router->addRoute('GET', '/logout', ['Controllers\AuthController', 'logout']);

// User routes
$router->addRoute('GET', '/users', ['Controllers\UserController', 'index']);
$router->addRoute('GET', '/users/:id/edit', ['Controllers\UserController', 'edit']);
$router->addRoute('POST', '/users/:id', ['Controllers\UserController', 'update']);
$router->addRoute('POST', '/users/:id/delete', ['Controllers\UserController', 'delete']);

// Transaction routes
$router->addRoute('GET', '/transactions', ['Controllers\TransactionController', 'index']);
$router->addRoute('GET', '/transactions/create', ['Controllers\TransactionController', 'create']);
$router->addRoute('POST', '/transactions', ['Controllers\TransactionController', 'store']);
$router->addRoute('GET', '/transactions/:id', ['Controllers\TransactionController', 'show']);
$router->addRoute('GET', '/transactions/:id/edit', ['Controllers\TransactionController', 'edit']);
$router->addRoute('POST', '/transactions/:id', ['Controllers\TransactionController', 'update']);
$router->addRoute('POST', '/transactions/:id/delete', ['Controllers\TransactionController', 'delete']);

// Entity routes
$router->addRoute('GET', '/entities', ['Controllers\EntityController', 'index']);
$router->addRoute('GET', '/entities/create', ['Controllers\EntityController', 'create']);
$router->addRoute('POST', '/entities', ['Controllers\EntityController', 'store']);
$router->addRoute('GET', '/entities/:id/edit', ['Controllers\EntityController', 'edit']);
$router->addRoute('POST', '/entities/:id', ['Controllers\EntityController', 'update']);
$router->addRoute('POST', '/entities/:id/delete', ['Controllers\EntityController', 'delete']);

// Currency routes
$router->addRoute('GET', '/currencies', ['Controllers\CurrencyController', 'index']);
$router->addRoute('GET', '/currencies/create', ['Controllers\CurrencyController', 'create']);
$router->addRoute('POST', '/currencies', ['Controllers\CurrencyController', 'store']);
$router->addRoute('GET', '/currencies/:id/edit', ['Controllers\CurrencyController', 'edit']);
$router->addRoute('POST', '/currencies/:id', ['Controllers\CurrencyController', 'update']);
$router->addRoute('POST', '/currencies/:id/delete', ['Controllers\CurrencyController', 'delete']);

// Purpose routes
$router->addRoute('GET', '/purposes', ['Controllers\PurposeController', 'index']);
$router->addRoute('GET', '/purposes/create', ['Controllers\PurposeController', 'create']);
$router->addRoute('POST', '/purposes', ['Controllers\PurposeController', 'store']);
$router->addRoute('GET', '/purposes/:id/edit', ['Controllers\PurposeController', 'edit']);
$router->addRoute('POST', '/purposes/:id', ['Controllers\PurposeController', 'update']);
$router->addRoute('POST', '/purposes/:id/delete', ['Controllers\PurposeController', 'delete']);

// Mode routes
$router->addRoute('GET', '/modes', ['Controllers\ModeController', 'index']);
$router->addRoute('GET', '/modes/create', ['Controllers\ModeController', 'create']);
$router->addRoute('POST', '/modes', ['Controllers\ModeController', 'store']);
$router->addRoute('GET', '/modes/:id/edit', ['Controllers\ModeController', 'edit']);
$router->addRoute('POST', '/modes/:id', ['Controllers\ModeController', 'update']);
$router->addRoute('POST', '/modes/:id/delete', ['Controllers\ModeController', 'delete']);

// Analytics routes
$router->addRoute('GET', '/analytics', ['Controllers\AnalyticsController', 'index']);
$router->addRoute('GET', '/analytics/expenditure', ['Controllers\AnalyticsController', 'expenditure']);
$router->addRoute('GET', '/analytics/crypto', ['Controllers\AnalyticsController', 'crypto']);

// Task routes
$router->addRoute('GET', '/tasks', ['Controllers\TaskController', 'index']);
$router->addRoute('POST', '/tasks', ['Controllers\TaskController', 'store']);
$router->addRoute('GET', '/tasks/:id', ['Controllers\TaskController', 'show']);
$router->addRoute('POST', '/tasks/:id/success', ['Controllers\TaskController', 'markSuccessful']);
$router->addRoute('POST', '/tasks/:id/fail', ['Controllers\TaskController', 'markFailed']);

// ── Invoice & Quotation System ────────────────────────

// Company Profile routes
$router->addRoute('GET', '/company-profiles', ['Controllers\CompanyProfileController', 'index']);
$router->addRoute('GET', '/company-profiles/create', ['Controllers\CompanyProfileController', 'create']);
$router->addRoute('POST', '/company-profiles', ['Controllers\CompanyProfileController', 'store']);
$router->addRoute('GET', '/company-profiles/:id/edit', ['Controllers\CompanyProfileController', 'edit']);
$router->addRoute('POST', '/company-profiles/:id', ['Controllers\CompanyProfileController', 'update']);
$router->addRoute('POST', '/company-profiles/:id/delete', ['Controllers\CompanyProfileController', 'delete']);

// Client Profile routes
$router->addRoute('GET', '/client-profiles', ['Controllers\ClientProfileController', 'index']);
$router->addRoute('GET', '/client-profiles/create', ['Controllers\ClientProfileController', 'create']);
$router->addRoute('POST', '/client-profiles', ['Controllers\ClientProfileController', 'store']);
$router->addRoute('GET', '/client-profiles/:id/edit', ['Controllers\ClientProfileController', 'edit']);
$router->addRoute('POST', '/client-profiles/:id', ['Controllers\ClientProfileController', 'update']);
$router->addRoute('POST', '/client-profiles/:id/delete', ['Controllers\ClientProfileController', 'delete']);

// Programme routes
$router->addRoute('GET', '/programmes', ['Controllers\ProgrammeController', 'index']);
$router->addRoute('GET', '/programmes/create', ['Controllers\ProgrammeController', 'create']);
$router->addRoute('POST', '/programmes', ['Controllers\ProgrammeController', 'store']);
$router->addRoute('GET', '/programmes/:id/edit', ['Controllers\ProgrammeController', 'edit']);
$router->addRoute('POST', '/programmes/:id', ['Controllers\ProgrammeController', 'update']);
$router->addRoute('POST', '/programmes/:id/delete', ['Controllers\ProgrammeController', 'delete']);

// Invoice/Quotation routes
$router->addRoute('GET', '/invoices/next-number', ['Controllers\InvoiceController', 'nextNumber']);
$router->addRoute('GET', '/invoices', ['Controllers\InvoiceController', 'index']);
$router->addRoute('GET', '/invoices/create', ['Controllers\InvoiceController', 'create']);
$router->addRoute('POST', '/invoices', ['Controllers\InvoiceController', 'store']);
$router->addRoute('GET', '/invoices/:id', ['Controllers\InvoiceController', 'show']);
$router->addRoute('GET', '/invoices/:id/edit', ['Controllers\InvoiceController', 'edit']);
$router->addRoute('POST', '/invoices/:id', ['Controllers\InvoiceController', 'update']);
$router->addRoute('POST', '/invoices/:id/delete', ['Controllers\InvoiceController', 'delete']);
$router->addRoute('POST', '/invoices/:id/toggle-paid', ['Controllers\InvoiceController', 'togglePaid']);
$router->addRoute('GET', '/invoices/:id/pdf', ['Controllers\InvoiceController', 'pdf']);
$router->addRoute('GET', '/invoices/:id/download-pdf', ['Controllers\InvoiceController', 'downloadPdf']);
$router->addRoute('POST', '/invoices/:id/generate-invoice', ['Controllers\InvoiceController', 'generateInvoice']);
$router->addRoute('POST', '/invoices/:id/duplicate', ['Controllers\InvoiceController', 'duplicate']);

// Dispatch the request
$router->dispatch();
