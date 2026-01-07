<?php
require __DIR__ . '/../middleware/cors.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/admin.php';

header('Content-Type: application/json; charset=utf-8');

$headers = array_change_key_case(getallheaders(), CASE_UPPER);

// Validar ADMIN KEY
if (!isset($headers['X-ADMIN-KEY']) || trim($headers['X-ADMIN-KEY']) !== trim(ADMIN_KEY)) {
    http_response_code(403);
    echo json_encode(["error" => "Acceso denegado"], JSON_UNESCAPED_UNICODE);
    exit;
}

// Obtener ID desde query param
$id = $_GET['id_campaña'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(["error" => "ID de campaña requerido"], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $stmt = $pdo->prepare("
      SELECT id_campaña, titulo, url_etsy, banner_escritorio, banner_tablet, banner_movil, dirigido, fecha_inicio, fecha_final, created_at
      FROM public.campañas
      WHERE id_campaña = :id
      LIMIT 1
    ");
    $stmt->execute(["id" => $id]);
    $campaña = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$campaña) {
        http_response_code(404);
        echo json_encode(["error" => "Campaña no encontrada"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
      "status" => "ok",
      "campaign" => $campaña
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
