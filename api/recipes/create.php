<?php
require __DIR__ . '/../middleware/auth.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/supabase.php';



$data = $_POST;
$user = $_REQUEST['user'] ?? null;

if (!$user) {
    http_response_code(401);
    echo json_encode(["error" => "No autenticado"]);
    exit;
}

if (!isset($_FILES['pdf'])) {
    http_response_code(400);
    echo json_encode(["error" => "PDF requerido"]);
    exit;
}

$pdf = $_FILES['pdf'];
$cover = $_FILES['cover'] ?? null;

// Subir PDF al bucket privado recipes
$pathPdf = "users/{$user['id_user']}/pdf/" . basename($pdf['name']);

$pdfUrl = supabaseUpload(
    "recipes", 
    $pathPdf, 
    $pdf['tmp_name'], 
    $pdf['type']
);

if (!$pdfUrl) {
    http_response_code(500);
    echo json_encode(["error" => "No se pudo subir el PDF"]);
    exit;
}

// Subir portada si existe
$coverUrl = null;
if ($cover) {
    $pathCover = "users/{$user['id_user']}/cover/" . basename($cover['name']);
    $coverUrl = supabaseUpload("recipes", $pathCover, $cover['tmp_name'], $cover['type']);
}

// Guardar metadata en PostgreSQL
$stmt = $pdo->prepare("
    INSERT INTO public.recetas (id_user, titulo, descripcion, pdf_url, cover_image)
    VALUES (:id_user, :titulo, :descripcion, :pdf_url, :cover_image)
    RETURNING id_receta, created_at
");

$stmt->execute([
    'id_user' => $user['id_user'],
    'titulo' => $data['titulo'] ?? $pdf['name'],
    'descripcion' => $data['descripcion'] ?? null,
    'pdf_url' => $pdfUrl,
    'cover_image' => $coverUrl
]);

$res = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    "status" => "ok",
    "id_receta" => $res['id_receta'],
    "pdf_url" => $pdfUrl,      // 👈 usamos la variable que sí existe
    "cover_image" => $coverUrl, // 👈 usamos la variable que sí existe
    "created_at" => $res['created_at']
]);
