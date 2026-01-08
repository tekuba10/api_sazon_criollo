<?php
require __DIR__ . '/../middleware/cors.php';
require __DIR__ . '/../middleware/auth.php';  // Valida JWT y lo pone en $_REQUEST['user']
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/supabase.php';
require __DIR__ . '/../utils/helpers.php';
require __DIR__ . '/../utils/response.php';

header('Content-Type: application/json; charset=utf-8');

// 1. Obtener usuario autenticado desde JWT
$user = $_REQUEST['user'] ?? null;
$idUser = $user['id_user'] ?? null;

if (!$idUser) {
    respondError(401, "No autenticado o token inválido");
}

// 2. Validar que llegue el PDF
if (!isset($_FILES['pdf'])) {
    respondError(400, "PDF requerido");
}

$pdf = $_FILES['pdf'];
$titulo = $_POST['titulo'] ?? $pdf['name'];
$descripcion = $_POST['descripcion'] ?? null;

// 3. Sanitizar nombre y generar nombre único
$cleanName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', basename($pdf['name']));
$fileName = bin2hex(random_bytes(6)) . "-" . $cleanName;

// 4. Armar ruta dentro del bucket
$pathPdf = "users/$idUser/pdf/$fileName";

// 5. Subir a Supabase Storage (bucket: recipes)
$pdfUrl = supabaseUpload("recipes", $pathPdf, $pdf['tmp_name'], $pdf['type']);

if (!$pdfUrl) {
    respondError(500, "No se pudo subir el PDF a Storage");
}

try {
    // 6. Guardar metadata en Postgres
    $stmt = $pdo->prepare("
      INSERT INTO public.recetas (id_user, titulo, descripcion, pdf_url)
      VALUES (:id_user, :titulo, :descripcion, :pdf_url)
      RETURNING id_receta, created_at
    ");

    $stmt->execute([
      "id_user" => $idUser,
      "titulo" => $titulo,
      "descripcion" => $descripcion,
      "pdf_url" => $pdfUrl
    ]);

    $res = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
      "status" => "ok",
      "message" => "Receta subida correctamente",
      "id_receta" => $res['id_receta'],
      "pdf_url" => $pdfUrl,
      "created_at" => $res['created_at']
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    respondError(500, $e->getMessage());
}
