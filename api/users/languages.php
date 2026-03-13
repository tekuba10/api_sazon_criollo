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

/*
|--------------------------------------------------------------------------
| GET → Obtener idioma del usuario
|--------------------------------------------------------------------------
*/
if ($method === 'GET') {

    $stmt = $pdo->prepare("
        SELECT idioma
        FROM users
        WHERE id_user = :id_user
        LIMIT 1
    ");

    $stmt->execute([
        'id_user' => $idUser
    ]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        http_response_code(404);
        echo json_encode([
            "error" => "Usuario no encontrado"
        ]);
        exit;
    }

    echo json_encode([
        "idioma" => $result['idioma'] ?? 'es'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

/*
|--------------------------------------------------------------------------
| PUT / POST → Actualizar idioma
|--------------------------------------------------------------------------
*/
if ($method === 'PUT' || $method === 'POST') {

    // Leer body (JSON o form-data)
    $input = json_decode(file_get_contents('php://input'), true);
    $idioma = $input['idioma'] ?? $_POST['idioma'] ?? null;

    $allowed = ['es', 'en', 'pt'];

    if (!$idioma || !in_array($idioma, $allowed, true)) {
        http_response_code(400);
        echo json_encode([
            "error" => "Idioma inválido. Valores permitidos: es, en, pt"
        ]);
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE users
        SET idioma = :idioma
        WHERE id_user = :id_user
    ");

    $stmt->execute([
        'idioma' => $idioma,
        'id_user' => $idUser
    ]);

    echo json_encode([
        "success" => true,
        "idioma" => $idioma
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

/*
|--------------------------------------------------------------------------
| Método no permitido
|--------------------------------------------------------------------------
*/
http_response_code(405);
echo json_encode([
    "error" => "Método no permitido"
]);
