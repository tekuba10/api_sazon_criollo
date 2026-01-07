<?php
require __DIR__ . '/../middleware/cors.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/admin.php';
require __DIR__ . '/../config/supabase.php';

$headers = array_change_key_case(getallheaders(), CASE_UPPER);

if (!isset($headers['X-ADMIN-KEY']) || trim($headers['X-ADMIN-KEY']) !== trim(ADMIN_KEY)) {
    http_response_code(403);
    echo json_encode(["error" => "Acceso denegado"]);
    exit;
}

$id = $_GET['id_campaña'] ?? null;
if (!$id) {
    http_response_code(400);
    echo json_encode(["error" => "ID de campaña requerido"]);
    exit;
}

// 1. Obtener campaña
$stmt = $pdo->prepare("SELECT banner_escritorio, banner_tablet, banner_movil FROM public.campañas WHERE id_campaña = :id");
$stmt->execute(["id" => $id]);
$campaña = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$campaña) {
    http_response_code(404);
    echo json_encode(["error" => "Campaña no encontrada"]);
    exit;
}

// 2. Determinar qué banner eliminar (priorizamos el que exista)
$bannerUrl = $campaña['banner_movil'] ?? $campaña['banner_tablet'] ?? $campaña['banner_escritorio'] ?? null;

if ($bannerUrl) {
    // 3. Convertir URL a path del bucket
    // Ej URL: https://xxxxx.supabase.co/storage/v1/object/public/campaigns/banners/mobile/banner.jpeg
    $parts = explode("/public/campaigns/", $bannerUrl);
    $filePath = end($parts); // banners/mobile/banner.jpeg

    // 4. Eliminar archivo del Storage
    $storageUrl = SUPABASE_URL . "/storage/v1/object/public/campaigns/" . $filePath;
    $ch = curl_init($storageUrl);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $_ENV['SUPABASE_KEY']
    ]);
    curl_exec($ch);
    curl_close($ch);
}

// 5. Eliminar la campaña de Postgres
$stmt = $pdo->prepare("DELETE FROM public.campañas WHERE id_campaña = :id");
$stmt->execute(["id" => $id]);

echo json_encode(["status" => "ok", "message" => "Campaña y banner eliminados correctamente"]);
