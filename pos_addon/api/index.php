<?php
/**
 * Over Chef POS - API Entry Point
 * Simple PHP Router for REST API
 */

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=UTF-8');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Autoload
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/greenter.php';
require_once __DIR__ . '/helpers/Response.php';
require_once __DIR__ . '/helpers/JWT.php';

// Get request info
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Remove query string and base path
// Cambiar a '/pos_addon/api' si usas XAMPP o a '' para php -S localhost:8000
$basePath = '';
$uri = parse_url($uri, PHP_URL_PATH);
$uri = str_replace($basePath, '', $uri);
$uri = trim($uri, '/');

// Split URI into segments
$segments = $uri ? explode('/', $uri) : [];
$resource = $segments[0] ?? '';
$id = $segments[1] ?? null;
$action = $segments[2] ?? null;

// Route to appropriate controller
try {
    switch ($resource) {
        case '':
        case 'health':
            Response::json([
                'status' => 'ok',
                'api' => API_NAME,
                'version' => API_VERSION,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;
            
        case 'auth':
            require_once __DIR__ . '/controllers/AuthController.php';
            $controller = new AuthController();
            $controller->handle($method, $id);
            break;
            
        case 'productos':
            require_once __DIR__ . '/controllers/ProductosController.php';
            $controller = new ProductosController();
            $controller->handle($method, $id, $action);
            break;
            
        case 'categorias':
            require_once __DIR__ . '/controllers/CategoriasController.php';
            $controller = new CategoriasController();
            $controller->handle($method, $id);
            break;
            
        case 'clientes':
            require_once __DIR__ . '/controllers/ClientesController.php';
            $controller = new ClientesController();
            $controller->handle($method, $id, $action);
            break;
            
        case 'mesas':
            require_once __DIR__ . '/controllers/MesasController.php';
            $controller = new MesasController();
            $controller->handle($method, $id, $action);
            break;
            
        case 'zonas':
            require_once __DIR__ . '/controllers/ZonasController.php';
            $controller = new ZonasController();
            $controller->handle($method, $id);
            break;
            
        case 'comandas':
            require_once __DIR__ . '/controllers/ComandasController.php';
            $controller = new ComandasController();
            $controller->handle($method, $id, $action);
            break;
            
        case 'ventas':
            require_once __DIR__ . '/controllers/VentasController.php';
            $controller = new VentasController();
            $controller->handle($method, $id, $action);
            break;
            
        case 'caja':
            require_once __DIR__ . '/controllers/CajaController.php';
            $controller = new CajaController();
            $controller->handle($method, $id, $action);
            break;
            
        case 'configuracion':
            require_once __DIR__ . '/controllers/ConfiguracionController.php';
            $controller = new ConfiguracionController();
            $controller->handle($method, $id);
            break;
            
        case 'dashboard':
            require_once __DIR__ . '/controllers/DashboardController.php';
            $controller = new DashboardController();
            $controller->handle($method, $action);
            break;
            
        default:
            Response::error('Endpoint no encontrado', 404);
    }
} catch (Exception $e) {
    Response::error($e->getMessage(), 500);
}
