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

    $stmtUsers = $pdo->query("
        SELECT COUNT(*) AS total
        FROM public.users
    ");
    $totalUsers = (int) $stmtUsers->fetch(PDO::FETCH_ASSOC)['total'];

    $stmtCampaigns = $pdo->query("
        SELECT COUNT(*) AS total
        FROM public.\"campañas\"
        WHERE fecha_final >= NOW()
    ");
    $activeCampaigns = (int) $stmtCampaigns->fetch(PDO::FETCH_ASSOC)['total'];

    $stmtLatestUsers = $pdo->query("
        SELECT id_user, nombre, apellido, usuario, email
        FROM public.users
        ORDER BY fecha_creacion DESC
        LIMIT 10
    ");

    $latestUsers = $stmtLatestUsers->fetchAll(PDO::FETCH_ASSOC);

    /* ===============================
       RESPUESTA FINAL
       =============================== */
    echo json_encode([
        "status" => "ok",
        "stats" => [
            "total_users" => $totalUsers,
            "active_campaigns" => $activeCampaigns
        ],
        "latest_users" => $latestUsers
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "No se pudo obtener el dashboard"
    ], JSON_UNESCAPED_UNICODE);
}
