<?php
require __DIR__ . '/../middleware/auth.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/supabase.php';

header("Content-Type: application/json");

$user = $_REQUEST['user'] ?? null;
$id   = $_GET['id'] ?? null;

if (!$user) {
    http_response_code(401);
    echo json_encode(["error" => "No autenticado"]);
    exit;
}

if (!$id) {
    http_response_code(400);
    echo json_encode(["error" => "ID de receta requerido"]);
    exit;
}

try {

    /* =========================
       1️⃣ Verificar propiedad
    ========================= */

    $stmt = $pdo->prepare("
        SELECT id_receta, pdf_url, cover_image
        FROM public.recetas
        WHERE id_receta = :id AND id_user = :user
    ");
    $stmt->execute([
        'id'   => $id,
        'user' => $user['id_user']
    ]);

    $receta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$receta) {
        http_response_code(403);
        echo json_encode(["error" => "No puedes eliminar esta receta"]);
        exit;
    }

    /* =========================
       2️⃣ Iniciar transacción
    ========================= */

    $pdo->beginTransaction();

    /* =========================
       3️⃣ Eliminar de Storage
    ========================= */

    if (!empty($receta['pdf_url'])) {
        supabaseDelete("recipes", $receta['pdf_url']);
    }

    if (!empty($receta['cover_image'])) {
        supabaseDelete("recipes", $receta['cover_image']);
    }

    /* =========================
       4️⃣ Eliminar de BD
    ========================= */

    $stmt = $pdo->prepare("
        DELETE FROM public.recetas
        WHERE id_receta = :id AND id_user = :user
    ");
    $stmt->execute([
        'id'   => $id,
        'user' => $user['id_user']
    ]);

    $pdo->commit();

    echo json_encode([
        "status"  => "ok",
        "message" => "Receta eliminada correctamente"
    ]);

} catch (Exception $e) {

    $pdo->rollBack();

    http_response_code(500);
    echo json_encode(["error" => "Error al eliminar la receta"]);
}
