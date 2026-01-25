<?php
require __DIR__ . '/../middleware/cors.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/admin.php';
require __DIR__ . '/../config/supabase.php';

header('Content-Type: application/json; charset=utf-8');

$headers = array_change_key_case(getallheaders(), CASE_UPPER);

/**
 * 1. Validar ADMIN KEY
 */
if (
    !isset($headers['X-ADMIN-KEY']) ||
    trim($headers['X-ADMIN-KEY']) !== ADMIN_KEY
) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado (ADMIN_KEY inválida)'], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 2. Validar frase legible contra KEY_DELETE_USERS
 */
if (!isset($headers['DELETE-PHRASE'])) {
    http_response_code(403);
    echo json_encode(['error' => 'DELETE-PHRASE requerida'], JSON_UNESCAPED_UNICODE);
    exit;
}

$phraseFromHeader = trim($headers['DELETE-PHRASE']);


// Convertir frase legible a hash
$hashedPhrase = hash('sha256', $phraseFromHeader);

// Comparar contra hash del .env
if (!hash_equals(KEY_DELETE_USERS, $hashedPhrase)) {
    http_response_code(403);
    echo json_encode(
        ['error' => 'Contraseña incorrecta'],
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

/**
 * 3. Obtener ID del usuario
 */
$idUser = $_GET['id_user'] ?? null;

if (!$idUser || !is_numeric($idUser)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de usuario inválido o requerido'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    /**
     * 4. Verificar existencia
     */
    $stmt = $pdo->prepare("
        SELECT id_user
        FROM public.users
        WHERE id_user = :id
    ");
    $stmt->execute(['id' => $idUser]);

    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Usuario no encontrado'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * 5. Eliminar usuario
     */
    $stmt = $pdo->prepare("
        DELETE FROM public.users
        WHERE id_user = :id
    ");
    $stmt->execute(['id' => $idUser]);

    echo json_encode([
        'status' => 'ok',
        'message' => 'Usuario eliminado correctamente',
        'id_user' => (int)$idUser
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error interno',
        'detail' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
