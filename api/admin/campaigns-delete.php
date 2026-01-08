<?php
require __DIR__ . '/../middleware/cors.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/admin.php';
require __DIR__ . '/../config/supabase.php';

header('Content-Type: application/json; charset=utf-8');

$headers = array_change_key_case(getallheaders(), CASE_UPPER);

// 1. Validar Admin Key
if (!isset($headers['X-ADMIN-KEY']) || trim($headers['X-ADMIN-KEY']) !== trim(ADMIN_KEY)) {
    http_response_code(403);
    echo json_encode(["error" => "Acceso denegado"], JSON_UNESCAPED_UNICODE);
    exit;
}

// 2. Obtener ID por query param
$id = $_GET['id_campaña'] ?? null;
if (!$id) {
    http_response_code(400);
    echo json_encode(["error" => "ID de campaña requerido"], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // 3. Obtener campaña y banners (con alias correctos)
    $stmt = $pdo->prepare("
      SELECT banner_escritorio, banner_tablet, banner_movil
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

    // 4. Determinar banner a borrar
    $bannerUrl = $campaña['banner_tablet'] ?? $campaña['banner_escritorio'] ?? $campaña['banner_movil'] ?? null;

    if ($bannerUrl) {
        // 5. Extraer path real dentro del bucket
        $parts = explode("/public/campaigns/", $bannerUrl);
        $filePath = end($parts);

        // 6. Borrar del bucket en Supabase Storage
        $ch = curl_init("https://" . SUPABASE_URL . "/storage/v1/object/public/campaigns/" . $filePath);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . SUPABASE_KEY
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    // 7. Borrar la campaña de Postgres
    $stmt = $pdo->prepare("DELETE FROM public.campañas WHERE id_campaña = :id");
    $stmt->execute(["id" => $id]);

    echo json_encode(["status" => "ok", "message" => "Campaña y banner eliminados correctamente"], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
