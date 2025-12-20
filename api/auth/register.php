<?php

require __DIR__ . '/../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

$token    = $data['token'] ?? null;
$email    = $data['email'] ?? null;
$password = $data['password'] ?? null;
$nombre   = $data['nombre'] ?? null;

// Validación básica
if (!$token || !$email || !$password) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos']);
    exit;
}

// 1️⃣ Validar link
$stmt = $pdo->prepare("
    SELECT id_link
    FROM registration_links
    WHERE token = :token
      AND used = false
      AND expires_at > NOW()
");

$stmt->execute(['token' => $token]);
$link = $stmt->fetch();

if (!$link) {
    http_response_code(400);
    echo json_encode(['error' => 'Link inválido o expirado']);
    exit;
}

// 2️⃣ Crear usuario
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("
        INSERT INTO users (email, password_hash, nombre)
        VALUES (:email, :password_hash, :nombre)
    ");

    $stmt->execute([
        'email' => $email,
        'password_hash' => $passwordHash,
        'nombre' => $nombre
    ]);
} catch (PDOException $e) {
    http_response_code(400);
    echo json_encode(['error' => 'El usuario ya existe']);
    exit;
}

// 3️⃣ Marcar link como usado
$stmt = $pdo->prepare("
    UPDATE registration_links
    SET used = true
    WHERE id_link = :id_link
");

$stmt->execute([
    'id_link' => $link['id_link']
]);

echo json_encode([
    'message' => 'Usuario registrado correctamente'
]);
