<?php
require __DIR__ . '/../config/jwt.php';

$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

if (!$authHeader || !str_starts_with($authHeader, "Bearer ")) {
    http_response_code(401);
    echo json_encode(["error" => "Token no enviado"]);
    exit;
}

$jwt = trim(str_replace("Bearer", "", $authHeader));

try {
    $decoded = jwtDecode($jwt);

    $GLOBALS['auth_user'] = [
        "id_user" => $decoded->idf_user ?? $decoded->id_user ?? null,
        "email"   => $decoded->email ?? null
    ];

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["error" => "Token inválido"]);
    exit;
}
