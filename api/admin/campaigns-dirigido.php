<?php
require __DIR__ . '/../middleware/cors.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/admin.php';

header('Content-Type: application/json; charset=utf-8');

// ============================
// Validar ADMIN KEY
// ============================
$headers = array_change_key_case(getallheaders(), CASE_UPPER);

if (!isset($headers['X-ADMIN-KEY']) || trim($headers['X-ADMIN-KEY']) !== trim(ADMIN_KEY)) {
    http_response_code(403);
    echo json_encode(["error" => "Acceso denegado"], JSON_UNESCAPED_UNICODE);
    exit;
}

try {

    // ============================
    // Consultar campañas dirigidas a usuarios
    // ============================
    $stmt = $pdo->query("
        SELECT
            cu.id_campaña,
            u.id_user,
            CONCAT(u.nombre, ' ', u.apellido) AS nombre_completo,
            u.usuario,
            u.email
        FROM public.campaña_usuarios cu
        INNER JOIN public.users u
            ON u.id_user = cu.id_user
        ORDER BY cu.id_campaña ASC, u.nombre ASC
    ");

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ============================
    // Agrupar por id_campaña
    // ============================
    $campaigns = [];

    foreach ($rows as $row) {
        $idCampaña = $row['id_campaña'];

        if (!isset($campaigns[$idCampaña])) {
            $campaigns[$idCampaña] = [
                "id_campaña" => $idCampaña,
                "usuarios"   => []
            ];
        }

        $campaigns[$idCampaña]["usuarios"][] = [
            "id_user"         => $row['id_user'],
            "nombre_completo" => $row['nombre_completo'],
            "usuario"         => $row['usuario'],
            "email"           => $row['email']
        ];
    }

    echo json_encode([
        "status"    => "ok",
        "campaigns" => array_values($campaigns)
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "No se pudieron obtener las campañas dirigidas"
    ], JSON_UNESCAPED_UNICODE);
}
