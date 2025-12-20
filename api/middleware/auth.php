<?php
require __DIR__ . '/../config/jwt.php';
require __DIR__ . '/../utils/helpers.php';

$headers = getallheaders();

if (!isset($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Token requerido']);
    exit;
}

$token = str_replace('Bearer ', '', $headers['Authorization']);

$payload = validateJWT($token);

if (!$payload) {
    http_response_code(401);
    echo json_encode(['error' => 'Token inválido o expirado']);
    exit;
}

// Usuario autenticado
$_REQUEST['user'] = $payload;
