<?php

require __DIR__ . '/../middleware/auth.php';
require __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$user = $GLOBALS['auth_user'] ?? null;
$idUser = $user['id_user'] ?? null;

if (!$idUser) {
    http_response_code(401);
    echo json_encode([
        "error" => "Usuario no autenticado"
    ]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST' && $method !== 'PUT') {
    http_response_code(405);
    echo json_encode([
        "error" => "Método no permitido"
    ]);
    exit;
}

// =========================
// 📥 Leer body (JSON o form)
// =========================
$input = json_decode(file_get_contents('php://input'), true);

$passwordActual = $input['password_actual'] ?? $_POST['password_actual'] ?? null;
$passwordNueva  = $input['password_nueva']  ?? $_POST['password_nueva']  ?? null;

if (!$passwordActual || !$passwordNueva) {
    http_response_code(400);
    echo json_encode([
        "error" => "Debes enviar la contraseña actual y la nueva"
    ]);
    exit;
}

// =========================
// 🔎 Obtener password actual
// =========================
$stmt = $pdo->prepare("
    SELECT password_hash
    FROM users
    WHERE id_user = :id_user
    LIMIT 1
");

$stmt->execute([
    'id_user' => $idUser
]);

$userDb = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$userDb) {
    http_response_code(404);
    echo json_encode([
        "error" => "Usuario no encontrado"
    ]);
    exit;
}

// =========================
// 🔐 Verificar contraseña actual
// =========================
if (!password_verify($passwordActual, $userDb['password_hash'])) {
    http_response_code(401);
    echo json_encode([
        "error" => "La contraseña actual es incorrecta"
    ]);
    exit;
}

// =========================
// 🔐 VALIDACIÓN DE CONTRASEÑA
// =========================
if (
    strlen($passwordNueva) < 6 ||
    !preg_match('/[A-Z]/', $passwordNueva) ||
    !preg_match('/[0-9]/', $passwordNueva) ||
    !preg_match('/[\W_]/', $passwordNueva)
) {
    http_response_code(400);
    echo json_encode([
        'error' => 'La contraseña debe tener al menos 6 caracteres, una mayúscula, un número y un carácter especial'
    ]);
    exit;
}

// =========================
// 🔒 Hashear nueva contraseña
// =========================
$newHash = password_hash($passwordNueva, PASSWORD_DEFAULT);

// =========================
// 💾 Actualizar contraseña + invalidar tokens
// =========================
$stmt = $pdo->prepare("
    UPDATE users
    SET password_hash = :password_hash,
        token_version = token_version + 1
    WHERE id_user = :id_user
");

$stmt->execute([
    'password_hash' => $newHash,
    'id_user' => $idUser
]);


echo json_encode([
    "success" => true,
    "message" => "Contraseña actualizada correctamente"
], JSON_UNESCAPED_UNICODE);

exit;
