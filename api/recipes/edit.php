<?php
require __DIR__ . '/../middleware/auth.php';
require __DIR__ . '/../config/database.php';

// Leer JSON del body
$data = json_decode(file_get_contents('php://input'), true);
$user = $_REQUEST['user'] ?? null;

$id = $data['id'] ?? null;
if (!$id) {
    http_response_code(400);
    echo json_encode(["error" => "ID de receta requerido"]);
    exit;
}

// 1️⃣ Verificar propiedad
$stmt = $pdo->prepare("
    SELECT id_receta 
    FROM public.recetas 
    WHERE id_receta = :id AND id_user = :user
");
$stmt->execute([
    'id' => $id,
    'user' => $user['id_user']
]);
$receta = $stmt->fetch();

if (!$receta) {
    http_response_code(403);
    echo json_encode(["error" => "No puedes editar esta receta"]);
    exit;
}

// 2️⃣ Actualizar metadata
$stmt = $pdo->prepare("
    UPDATE public.recetas
    SET titulo = :titulo,
        descripcion = :descripcion,
        cover_image = :cover_image
    WHERE id_receta = :id AND id_user = :user
    RETURNING id_receta, titulo, descripcion, cover_image, created_at
");

$stmt->execute([
    'id' => $id,
    'user' => $user['id_user'],
    'titulo' => $data['titulo'] ?? $receta['titulo'],
    'descripcion' => $data['descripcion'] ?? $receta['descripcion'],
    'cover_image' => $data['cover_image'] ?? $receta['cover_image']
]);

echo json_encode([
    "status" => "ok",
    "message" => "Receta actualizada",
    "receta" => $stmt->fetch(PDO::FETCH_ASSOC)
]);
