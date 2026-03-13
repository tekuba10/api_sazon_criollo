<?php
require __DIR__ . '/../middleware/auth.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/supabase.php';

header("Content-Type: application/json");


$data = $_POST;
$user = $_REQUEST['user'] ?? null;

if (!$user) {
    http_response_code(401);
    echo json_encode(["error" => "No autenticado"]);
    exit;
}

/* =========================
   VALIDAR DESCRIPCIÓN
========================= */

if (empty($data['descripcion']) || trim($data['descripcion']) === '') {
    http_response_code(400);
    echo json_encode(["error" => "Descripción requerida"]);
    exit;
}

/* =========================
   VALIDAR PDF
========================= */

if (!isset($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(["error" => "PDF requerido"]);
    exit;
}

$pdf = $_FILES['pdf'];

$finfo = new finfo(FILEINFO_MIME_TYPE);
$pdfMime = $finfo->file($pdf['tmp_name']);

if ($pdfMime !== 'application/pdf') {
    http_response_code(400);
    echo json_encode(["error" => "El archivo debe ser un PDF válido"]);
    exit;
}

/* =========================
   GENERAR RUTAS
========================= */

$userId   = $user['id_user'];
$uniqueId = bin2hex(random_bytes(8));

$pdfPath   = "users/{$userId}/pdf/{$uniqueId}.pdf";
$coverPath = "users/{$userId}/cover/{$uniqueId}.jpg";

/* =========================
   SUBIR PDF
========================= */

$pdfUpload = supabaseUpload("recipes", $pdfPath, $pdf['tmp_name'], $pdfMime);

if (!$pdfUpload) {
    http_response_code(500);
    echo json_encode(["error" => "No se pudo subir el PDF"]);
    exit;
}

/* =========================
   GENERAR PORTADA DESDE PDF
========================= */

$tempCoverPath = sys_get_temp_dir() . "/cover_{$uniqueId}.jpg";

try {
    $imagick = new \Imagick();
    $imagick->setResolution(150, 150);
    $imagick->readImage($pdf['tmp_name'] . "[0]");
    $imagick->setImageFormat("jpeg");
    $imagick->setImageCompressionQuality(85);
    $imagick->writeImage($tempCoverPath);
    $imagick->clear();
    $imagick->destroy();

} catch (Exception $e) {

    supabaseDelete("recipes", $pdfPath);

    http_response_code(500);
    echo json_encode(["error" => "No se pudo generar la portada automáticamente"]);
    exit;
}

/* =========================
   SUBIR PORTADA
========================= */

$coverUpload = supabaseUpload(
    "recipes",
    $coverPath,
    $tempCoverPath,
    "image/jpeg"
);

unlink($tempCoverPath);

if (!$coverUpload) {

    supabaseDelete("recipes", $pdfPath);

    http_response_code(500);
    echo json_encode(["error" => "No se pudo subir la portada generada"]);
    exit;
}

/* =========================
   INSERT EN BD (GUARDANDO SOLO PATH)
========================= */

try {

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO public.recetas 
        (id_user, descripcion, pdf_url, cover_image)
        VALUES 
        (:id_user, :descripcion, :pdf_url, :cover_image)
        RETURNING id_receta, created_at
    ");

    $stmt->execute([
        'id_user'     => $userId,
        'descripcion' => trim($data['descripcion']),
        'pdf_url'     => $pdfPath,    // 👈 Guardamos SOLO path
        'cover_image' => $coverPath   // 👈 Guardamos SOLO path
    ]);

    $res = $stmt->fetch(PDO::FETCH_ASSOC);

    $pdo->commit();

} catch (Exception $e) {

    $pdo->rollBack();

    supabaseDelete("recipes", $pdfPath);
    supabaseDelete("recipes", $coverPath);

    http_response_code(500);
    echo json_encode(["error" => "Error al guardar en base de datos"]);
    exit;
}

/* =========================
   RESPUESTA
========================= */

echo json_encode([
    "status"      => "ok",
    "id_receta"   => $res['id_receta'],
    "pdf_path"    => $pdfPath,
    "cover_path"  => $coverPath,
    "created_at"  => $res['created_at']
]);
