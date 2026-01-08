<?php
require __DIR__ . '/../config/jwt.php';

$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

if (!$authHeader || !str_starts_with($authHeader, "Bearer ")) {
    http_response_code(401);
    echo json_encode(["error" => "Token no enviado o formato incorrecto"]);
    exit;
}

// Extraer el JWT correctamente
$jwt = trim(str_replace("Bearer ", "", $authHeader));

try {
    // Decodificar con la función correcta que tienes en jwt.php
    $decoded = jwtDecode($jwt);

    // Guardar usuario autenticado globalmente sin roles
    $GLOBALS['auth_user'] = [
        "id_user" => $decoded->id_user ?? null,
        "email"   => $decoded->email ?? null
    ];

    // También lo ponemos en REQUEST para tus endpoints existentes
    $_REQUEST['user'] = [
        "id_user" => $decoded->id_user ?? null,
        "email"   => $decoded->email ?? null
    ];

    // Validar que el token tenga usuario
    if (!$GLOBALS['auth_user']['id_user']) {
        http_response_code(401);
        echo json_encode(["error" => "Token inválido o sin usuario"]);
        exit;
    }

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["error" => "Token inválido"]);
    exit;
}
