<?php
require __DIR__ . '/../middleware/auth.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/supabase.php';

$user = $_REQUEST['user'];

// Obtener el ID desde la URL
$id = $_GET['id'] ?? null;
if (!$id) {
    http_response_code(400);
    echo json_encode(["error" => "ID de receta requerido"]);
    exit;
}

// 1️⃣ Verificar que la receta pertenece al usuario
$stmt = $pdo->prepare("
    SELECT id_receta, pdf_url, cover_image 
    FROM public.recetas 
    WHERE id_receta = :id AND id_user = :user
");
$stmt->execute([
    'id' => $id,
    'user' => $user['id_user']
]);
$receta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$receta) {
    http_response_code(403);
    echo json_encode(["error" => "No puedes eliminar esta receta"]);
    exit;
}

// 2️⃣ Borrar el archivo PDF de Storage si existe
if ($receta['pdf_url']) {
    $path = "users/{$user['id_user']}/pdf/" . basename($receta['pdf_url']);
    supabaseDelete("recipes", $path);
}

// 3️⃣ Eliminar el registro en PostgreSQL
$stmt = $pdo->prepare("
    DELETE FROM public.recetas 
    WHERE id_receta = :id AND id_user = :user
");
$stmt->execute([
    'id' => $id,
    'user' => $user['id_user']
]);

echo json_encode(["status" => "ok", "message" => "Receta eliminada"]);
