<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../utils/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

if (!$authHeader || !str_starts_with($authHeader, "Bearer ")) {
    http_response_code(401);
    echo json_encode(["error" => "Token no enviado o formato incorrecto"]);
    exit;
}

$jwt = trim(str_replace("Bearer ", "", $authHeader));

// 🔥 AHORA usamos validateJWT (que verifica token_version)
$payload = validateJWT($jwt, $pdo);

if (!$payload) {
    http_response_code(401);
    echo json_encode(["error" => "Token inválido o expirado"]);
    exit;
}

// Guardar usuario autenticado
$GLOBALS['auth_user'] = [
    "id_user" => $payload['id_user'] ?? null,
    "email"   => $payload['email'] ?? null,
    "usuario" => $payload['usuario'] ?? null
];

// También opcionalmente en REQUEST
$_REQUEST['user'] = $GLOBALS['auth_user'];

if (!$GLOBALS['auth_user']['id_user']) {
    http_response_code(401);
    echo json_encode(["error" => "Token inválido"]);
    exit;
}
