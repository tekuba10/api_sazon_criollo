<?php
require __DIR__ . '/../middleware/cors.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/admin.php';

header('Content-Type: application/json');

// =====================
// AUTH
// =====================
$headers = array_change_key_case(getallheaders(), CASE_UPPER);

if (
    !isset($headers['X-ADMIN-KEY']) ||
    trim($headers['X-ADMIN-KEY']) !== trim(ADMIN_KEY)
) {
    http_response_code(403);
    echo json_encode(["error" => "Acceso denegado"]);
    exit;
}

// =====================
// LISTAR RECOMENDADOS
// =====================
try {
    $stmt = $pdo->prepare("
        SELECT
            id_recomendado,
            titulo,
            url,
            poster,
            fecha_creacion
        FROM public.recomendado
        ORDER BY fecha_creacion DESC
    ");

    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "ok",
        "total" => count($data),
        "data" => $data
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Error al obtener los recomendados"
    ]);
    exit;
}
