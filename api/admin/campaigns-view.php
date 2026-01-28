<?php
require __DIR__ . '/../middleware/cors.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/admin.php';

header('Content-Type: application/json; charset=utf-8');

$headers = array_change_key_case(getallheaders(), CASE_UPPER);

// ============================
// Validar ADMIN KEY
// ============================
if (($headers['X-ADMIN-KEY'] ?? '') !== trim(ADMIN_KEY)) {
    http_response_code(403);
    echo json_encode(["error" => "Acceso denegado"], JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================
// Obtener ID campaña
// ============================
$id = $_GET['id_campaña'] ?? $_GET['id_campana'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(["error" => "ID de campaña requerido"], JSON_UNESCAPED_UNICODE);
    exit;
}

try {

    // ============================
    // Obtener campaña (SOLO campañas)
    // ============================
    $stmt = $pdo->prepare("
        SELECT
            id_campaña,
            titulo,
            url_etsy,
            banner_escritorio,
            banner_tablet,
            banner_movil,
            fecha_inicio,
            fecha_final,
            created_at
        FROM campañas
        WHERE id_campaña = :id
        LIMIT 1
    ");

    $stmt->execute(["id" => $id]);
    $campaign = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$campaign) {
        http_response_code(404);
        echo json_encode(["error" => "Campaña no encontrada"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ============================
    // Respuesta limpia
    // ============================
    echo json_encode([
        "status" => "ok",
        "campaign" => [
            "id_campaña" => $campaign['id_campaña'],
            "titulo" => $campaign['titulo'],
            "url_etsy" => $campaign['url_etsy'],
            "fecha_inicio" => $campaign['fecha_inicio'],
            "fecha_final" => $campaign['fecha_final'],
            "created_at" => $campaign['created_at'],
            "banners" => [
                "desktop" => $campaign['banner_escritorio'],
                "tablet" => $campaign['banner_tablet'],
                "mobile" => $campaign['banner_movil']
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Error interno del servidor"
    ], JSON_UNESCAPED_UNICODE);
}
