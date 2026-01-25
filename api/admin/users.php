<?php
require __DIR__ . '/../middleware/cors.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/admin.php';

header('Content-Type: application/json; charset=utf-8');

// Validar ADMIN KEY
$headers = array_change_key_case(getallheaders(), CASE_UPPER);

if (!isset($headers['X-ADMIN-KEY']) || trim($headers['X-ADMIN-KEY']) !== trim(ADMIN_KEY)) {
    http_response_code(403);
    echo json_encode(["error" => "Acceso denegado"], JSON_UNESCAPED_UNICODE);
    exit;
}

try {

    // Obtener usuarios (SIN datos sensibles)
    $stmt = $pdo->query("
        SELECT
            id_user,
            nombre,
            apellido,
            usuario,
            email,
            fecha_nacimiento,
            idioma,
            marketing_opt_in,
            fecha_creacion,
            is_active
        FROM public.users
        ORDER BY fecha_creacion DESC
    ");

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "ok",
        "users" => $users
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "No se pudieron obtener los usuarios"
    ], JSON_UNESCAPED_UNICODE);
}
