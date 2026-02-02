<?php
require __DIR__ . '/../middleware/cors.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/admin.php';
require __DIR__ . '/../config/supabase.php';

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
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Campo requerido: id"]);
    exit;
}

$id = (int) $_POST['id'];

// =====================
// OBTENER POSTER
// =====================
$stmt = $pdo->prepare("
    SELECT poster
    FROM public.recomendado
    WHERE id_recomendado = :id
    LIMIT 1
");
$stmt->execute([':id' => $id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    http_response_code(404);
    echo json_encode(["error" => "Recomendado no encontrado"]);
    exit;
}

// =====================
// EXTRAER PATH STORAGE
// =====================
$posterUrl = $row['poster'];

// ejemplo:
// https://xxxx.supabase.co/storage/v1/object/public/poster/banner.png
$parsed = parse_url($posterUrl);
$path   = str_replace('/storage/v1/object/public/', '', $parsed['path']);
// resultado: poster/banner.png

// =====================
// DELETE DB + STORAGE
// =====================
try {
    $pdo->beginTransaction();

    $stmtDelete = $pdo->prepare("
        DELETE FROM public.recomendado
        WHERE id_recomendado = :id
    ");
    $stmtDelete->execute([':id' => $id]);

    $pdo->commit();

    // borrar archivo (fuera de transacción)
    supabaseDelete('poster', basename($path));

    echo json_encode([
        "status" => "ok",
        "deleted_id" => $id
    ]);
    exit;

} catch (Exception $e) {

    $pdo->rollBack();

    http_response_code(500);
    echo json_encode([
        "error" => "Error al eliminar el recomendado"
    ]);
    exit;
}
