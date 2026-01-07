<?php
require __DIR__ . '/../middleware/cors.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/admin.php';

$headers = array_change_key_case(getallheaders(), CASE_UPPER);
header('Content-Type: application/json; charset=utf-8');

if (!isset($headers['X-ADMIN-KEY']) || trim($headers['X-ADMIN-KEY']) !== trim(ADMIN_KEY)) {
    http_response_code(403);
    echo json_encode(["error" => "Acceso denegado"]);
    exit;
}

try {
    $stmt = $pdo->query("
      SELECT id_campaña, titulo, url_etsy, banner_escritorio, banner_tablet, banner_movil, dirigido, fecha_inicio, fecha_final, created_at
      FROM public.campañas
      ORDER BY created_at DESC
    ");


    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
      "status" => "ok",
      "campaigns" => $campaigns
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
