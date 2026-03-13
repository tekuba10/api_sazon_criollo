<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../utils/helpers.php';


$data = json_decode(file_get_contents('php://input'), true);

$login = $data['login'] ?? null; // email o usuario
$password = $data['password'] ?? null;

if (!$login || !$password) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Usuario/email y password requeridos'
    ]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id_user, usuario, email, password_hash, is_active, token_version
    FROM users
    WHERE email = :login
       OR usuario = :login
    LIMIT 1
");

$stmt->execute([
    'login' => $login
]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Usuario no existe o password incorrecto
if (!$user || !password_verify($password, $user['password_hash'])) {
    http_response_code(401);
    echo json_encode([
        'error' => 'Credenciales inválidas'
    ]);
    exit;
}

// 👉 VALIDACIÓN DE CUENTA ACTIVA
if (!$user['is_active']) {
    http_response_code(403); // Forbidden
    echo json_encode([
        'error' => 'Tu cuenta está desactivada. Contacta con administración.'
    ]);
    exit;
}

// Generar token si todo está OK
$token = generateJWT([
    'id_user'       => $user['id_user'],
    'email'         => $user['email'],
    'usuario'       => $user['usuario'],
    'token_version' => $user['token_version']
]);

echo json_encode([
    'token' => $token,
    'user' => [
        'id_user' => $user['id_user'],
        'email'   => $user['email'],
        'usuario' => $user['usuario']
    ]
]);
