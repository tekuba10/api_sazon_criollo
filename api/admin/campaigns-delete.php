<?php
require __DIR__ . '/../middleware/cors.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/admin.php';
require __DIR__ . '/../config/supabase.php';

header('Content-Type: application/json; charset=utf-8');

function getStoragePathFromUrl(string $url): ?string
{
    $marker = '/storage/v1/object/public/campaigns/';
    if (strpos($url, $marker) === false) {
        return null;
    }

    return substr($url, strpos($url, $marker) + strlen($marker));
}


$headers = array_change_key_case(getallheaders(), CASE_UPPER);

// ============================
// 1. Validar ADMIN KEY
// ============================
if (!isset($headers['X-ADMIN-KEY']) || trim($headers['X-ADMIN-KEY']) !== trim(ADMIN_KEY)) {
    http_response_code(403);
    echo json_encode(["error" => "Acceso denegado"], JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================
// 2. Validar ID campaña
// ============================
$id = $_GET['id_campana'] ?? null;

if (!$id || !is_numeric($id)) {
    http_response_code(400);
    echo json_encode(["error" => "id_campana inválido"], JSON_UNESCAPED_UNICODE);
    exit;
}



try {
    // ============================
    // 3. Iniciar transacción
    // ============================
    $pdo->beginTransaction();

    // ============================
    // 4. Obtener banners
    // ============================
    $stmt = $pdo->prepare("
        SELECT banner_escritorio, banner_tablet, banner_movil
        FROM public.campañas
        WHERE id_campaña = :id
        LIMIT 1
    ");
    $stmt->execute(["id" => $id]);
    $campaña = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$campaña) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(["error" => "Campaña no encontrada"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ============================
    // 5. Borrar usuarios dirigidos
    // ============================
    $stmt = $pdo->prepare("
        DELETE FROM public.campaña_usuarios
        WHERE id_campaña = :id
    ");
    $stmt->execute(["id" => $id]);

 // ============================
// 6. Borrar banners del storage
// ============================
    $banners = [
        $campaña['banner_escritorio'],
        $campaña['banner_tablet'],
        $campaña['banner_movil']
    ];

    foreach ($banners as $bannerUrl) {
        if (empty($bannerUrl)) {
            continue;
        }

        $filePath = getStoragePathFromUrl($bannerUrl);
        if (!$filePath) {
            continue;
        }

        // Usa la función centralizada de Supabase
        supabaseDelete('campaigns', $filePath);
    }



    // ============================
    // 7. Borrar campaña
    // ============================
    $stmt = $pdo->prepare("
        DELETE FROM public.campañas
        WHERE id_campaña = :id
    ");
    $stmt->execute(["id" => $id]);

    // ============================
    // 8. Commit
    // ============================
    $pdo->commit();

    echo json_encode([
        "status"  => "ok",
        "message" => "Campaña eliminada correctamente"
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        "error"   => "Error al eliminar la campaña",
        "detalle" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
