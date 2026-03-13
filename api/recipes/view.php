<?php
require __DIR__ . '/../middleware/auth.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/supabase.php';

header('Content-Type: application/json; charset=utf-8');

$user = $_REQUEST['user'] ?? null;
$id = $_GET['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(["error" => "ID de receta requerido"]);
    exit;
}

if (!$user) {
    http_response_code(401);
    echo json_encode(["error" => "No autenticado"]);
    exit;
}

// 1️⃣ Validar que la receta es del dueño autenticado
$stmt = $pdo->prepare("
    SELECT id_receta, descripcion, pdf_url, cover_image, created_at
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

// 2️⃣ Generar signed URLs

$signedCover = supabaseCreateSignedUrl(
    "recipes",
    $receta['cover_image'],
    86400 // 24 horas
);

$signedPdf = supabaseCreateSignedUrl(
    "recipes",
    $receta['pdf_url'],
    86400 // 30 minutos
);

// 3️⃣ Devolver receta con URLs firmadas
echo json_encode([
    "status" => "ok",
    "receta" => [
        "id_receta"   => $receta['id_receta'],
        "descripcion" => $receta['descripcion'],
        "cover_image" => $signedCover,
        "pdf_url"     => $signedPdf,
        "created_at"  => $receta['created_at']
    ]
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

