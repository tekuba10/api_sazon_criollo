<?php
require __DIR__ . '/../middleware/auth.php';
require __DIR__ . '/../config/database.php';

$user = $_REQUEST['user'] ?? null;
$id = $_GET['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(["error" => "ID de receta requerido"]);
    exit;
}

// 1️⃣ Validar que la receta es del dueño autenticado
$stmt = $pdo->prepare("
    SELECT id_receta, titulo, descripcion, pdf_url, cover_image, created_at
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
    echo json_encode(["error" => "No tienes acceso a esta receta"]);
    exit;
}

// 2️⃣ Devolver receta
echo json_encode([
    "status" => "ok",
    "receta" => $receta
]);
