<?php
require __DIR__ . '/../middleware/auth.php';
require __DIR__ . '/../config/database.php';

$user = $GLOBALS['auth_user'] ?? null;
$idUser = $user['id_user'] ?? null;

if (!$idUser) {
    http_response_code(401);
    echo json_encode(["error" => "Usuario no autenticado"]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id_user, email, nombre, fecha_creacion
    FROM users
    WHERE id_user = :id_user
");

$stmt->execute([
    'id_user' => $idUser
]);

$profile = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($profile, JSON_UNESCAPED_UNICODE);
