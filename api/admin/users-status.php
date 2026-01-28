<?php

require __DIR__ . '/../middleware/cors.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/admin.php';

header('Content-Type: application/json; charset=utf-8');

/*
|--------------------------------------------------------------------------
| VALIDAR ADMIN KEY
|--------------------------------------------------------------------------
*/
$headers = array_change_key_case(getallheaders(), CASE_UPPER);

if (
    !isset($headers['X-ADMIN-KEY']) ||
    trim($headers['X-ADMIN-KEY']) !== trim(ADMIN_KEY)
) {
    http_response_code(403);
    echo json_encode([
        "error" => "Acceso denegado"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/*
|--------------------------------------------------------------------------
| LEER JSON
|--------------------------------------------------------------------------
*/
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data) {
    http_response_code(400);
    echo json_encode([
        "error" => "JSON inválido"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$id_user   = $data['id_user']   ?? null;
$is_active = $data['is_active'] ?? null;

/*
|--------------------------------------------------------------------------
| VALIDACIONES
|--------------------------------------------------------------------------
*/
if (!is_numeric($id_user) || !is_bool($is_active)) {
    http_response_code(400);
    echo json_encode([
        "error" => "id_user (int) e is_active (boolean) son obligatorios"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/*
|--------------------------------------------------------------------------
| UPDATE
|--------------------------------------------------------------------------
*/
try {

    $stmt = $pdo->prepare("
        UPDATE public.users
        SET is_active = :is_active
        WHERE id_user = :id_user
        RETURNING id_user, email, is_active
    ");

    $stmt->bindValue(':id_user', (int)$id_user, PDO::PARAM_INT);
    $stmt->bindValue(':is_active', $is_active, PDO::PARAM_BOOL);

    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode([
            "error" => "Usuario no encontrado"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        "status"  => "ok",
        "message" => $is_active
            ? "Usuario activado correctamente"
            : "Usuario bloqueado correctamente",
        "user"    => $user
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {

    http_response_code(500);
    echo json_encode([
        "error"   => "Error al actualizar el usuario",
        "detalle"=> $e->getMessage() // ⛔ quitar en producción
    ], JSON_UNESCAPED_UNICODE);
}
