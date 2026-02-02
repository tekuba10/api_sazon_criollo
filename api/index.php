<?php
header('Content-Type: application/json');

require __DIR__ . '/middleware/cors.php';
require __DIR__ . '/config/database.php';
require __DIR__ . '/config/admin.php';


$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Limpia barras finales
$uri = rtrim($uri, '/');

switch ($uri) {

    case '/ping':
        echo json_encode([
            'status' => 'ok',
            'message' => 'API running 🚀'
        ]);
        break;

    case '/db-check':
        require __DIR__ . '/config/database.php';
        echo json_encode([
            'status' => 'ok',
            'db' => 'connected'
        ]);
        break;

    case '/auth/login':
        require __DIR__ . '/auth/login.php';
        break;

    case '/auth/register':
        require __DIR__ . '/auth/register.php';
        break;

    case '/users/profile':
        require __DIR__ . '/users/profile.php';
        break;

    // RECETAS

    case '/recipes/create':
        require __DIR__ . '/recipes/create.php';
        break;

    case '/recipes/list':
        require __DIR__ . '/recipes/list.php';
        break;
    
    case '/recipes/delete':
        require __DIR__ . '/recipes/delete.php';
        break;
    
    case '/recipes/edit':
        require __DIR__ . '/recipes/edit.php';
        break;

    case '/recipes/view':
        require __DIR__ . '/recipes/view.php';
        break;
    
    case '/recipes/upload':
        require __DIR__ . '/recipes/upload.php';
        break;


    // ADMINISTRADOR 

    // CAMPAÑAS

    case '/admin/campaigns/create':
        require __DIR__ . '/admin/campaigns-create.php';
        break;

    case '/campaigns/active':
        require __DIR__ . '/campaigns/active.php';
        break;

    case '/admin/campaigns/edit':
        require __DIR__ . '/admin/campaigns-edit.php';
        break;

    case '/admin/campaigns/delete':
        require __DIR__ . '/admin/campaigns-delete.php';
        break;
    
    case '/admin/campaigns/list':
        require __DIR__ . '/admin/campaigns-list.php';
        break;

    case '/admin/campaigns/view':
        require __DIR__ . '/admin/campaigns-view.php';
        break;

    case '/admin/campaigns/deactivate':
        require __DIR__ . '/admin/campaigns-deactivate.php';
        break;
    
    case '/admin/campaigns/dirigido':
        require __DIR__ . '/admin/campaigns-dirigido.php';
        break;

    case '/admin/campaigns/status':
        require __DIR__ . '/admin/campaigns-status.php';
        break;

    // DASHBOARD

    case '/admin/dashboard':
        require __DIR__ . '/admin/dashboard.php';
        break;
    
    case '/admin/create-registration-link':
        require __DIR__ . '/admin/create-registration-link.php';
        break;

    // USUARIO

    case '/admin/users':
        require __DIR__ . '/admin/users.php';
        break;

    case '/admin/users/delete':
        require __DIR__ . '/admin/users-delete.php';
        break;

    case '/admin/users/status':
        require __DIR__ . '/admin/users-status.php';
        break;

    // RECOMENDADO

    case '/admin/recommended/create':
        require __DIR__ . '/admin/recommended-create.php';
        break;
    
    case '/admin/recommended/list':
        require __DIR__ . '/admin/recommended-list.php';
        break;

    case '/admin/recommended/view':
        require __DIR__ . '/admin/recommended-view.php';
        break;
    
    case '/admin/recommended/delete':
        require __DIR__ . '/admin/recommended-delete.php';
        break;

    case '/admin/recommended/edit':
        require __DIR__ . '/admin/recommended-edit.php';
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
}
