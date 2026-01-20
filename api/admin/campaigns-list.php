<?php
require __DIR__ . '/../middleware/cors.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/admin.php';

header('Content-Type: application/json; charset=utf-8');

// =====================
// AUTH
// =====================
$headers = array_change_key_case(getallheaders(), CASE_UPPER);

if (!isset($headers['X-ADMIN-KEY']) || trim($headers['X-ADMIN-KEY']) !== trim(ADMIN_KEY)) {
    http_response_code(403);
    echo json_encode(["error" => "Acceso denegado"]);
    exit;
}

try {

    $stmt = $pdo->query("
        SELECT
            c.id_campaña,
            c.titulo,
            c.url_etsy,
            c.banner_escritorio,
            c.banner_tablet,
            c.banner_movil,
            c.fecha_inicio,
            c.fecha_final,
            c.dirigido_todos,
            c.activa,
            c.created_at,
            COALESCE(
                json_agg(cu.id_user) FILTER (WHERE cu.id_user IS NOT NULL),
                '[]'
            ) AS usuarios
        FROM campañas c
        LEFT JOIN campaña_usuarios cu ON cu.id_campaña = c.id_campaña
        GROUP BY c.id_campaña
        ORDER BY c.created_at DESC
    ");

    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "ok",
        "campaigns" => $campaigns
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Error al obtener campañas",
        "details" => $e->getMessage()
    ]);
}
