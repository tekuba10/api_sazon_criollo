<?php
header('Content-Type: application/json');

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

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
}
