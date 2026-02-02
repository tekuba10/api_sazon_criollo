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
// VALIDAR ID
// =====================
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Parámetro requerido: id"]);
    exit;
}

$id = (int) $_GET['id'];

// =====================
// OBTENER RECOMENDADO
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
        WHERE id_recomendado = :id
        LIMIT 1
    ");

    $stmt->execute([':id' => $id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        http_response_code(404);
        echo json_encode(["error" => "Recomendado no encontrado"]);
        exit;
    }

    echo json_encode([
        "status" => "ok",
        "data" => $data
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Error al obtener el recomendado"
    ]);
    exit;
}
