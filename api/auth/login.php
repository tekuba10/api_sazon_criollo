<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/jwt.php';
require __DIR__ . '/../utils/helpers.php';

$data = json_decode(file_get_contents('php://input'), true);

$email = $data['email'] ?? null;
$password = $data['password'] ?? null;

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(['error' => 'Email y password requeridos']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id_user, email, password_hash
    FROM users
    WHERE email = :email
");

$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Credenciales inválidas']);
    exit;
}

$token = generateJWT([
    'id_user' => $user['id_user'],
    'email' => $user['email']
]);

echo json_encode([
    'token' => $token
]);
