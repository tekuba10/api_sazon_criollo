<?php
require __DIR__ . '/../config/admin.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/supabase.php';

header('Content-Type: application/json; charset=utf-8');

// Validar ADMIN KEY
$headers = array_change_key_case(getallheaders(), CASE_UPPER);
if (!isset($headers['X-ADMIN-KEY']) || trim($headers['X-ADMIN-KEY']) !== trim(ADMIN_KEY)) {
    http_response_code(403);
    echo json_encode(["error" => "Acceso denegado"], JSON_UNESCAPED_UNICODE);
    exit;
}

// Tomar ID desde form-data o query param
$id = $_POST['id_campaña'] ?? $_GET['id_campaña'] ?? null;
if (!$id) {
    http_response_code(400);
    echo json_encode(["error" => "ID de campaña requerido"], JSON_UNESCAPED_UNICODE);
    exit;
}

// Si llega banner nuevo y se indica dispositivo, subirlo
$bannerUrl = null;
$device = $_POST['device'] ?? null;

if (isset($_FILES['banner']) && $device) {
    $banner = $_FILES['banner'];
    // Generamos nombre seguro para evitar sobrescribir
    $fileName = bin2hex(random_bytes(6)) . "-" . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $banner['name']);
    $path = "banners/$device/$fileName";
    $bannerUrl = supabaseUpload("campaigns", $path, $banner['tmp_name'], $banner['type']);
}

// Actualizar solo columnas que existen en tu BD
$stmt = $pdo->prepare("
  UPDATE public.campañas
  SET 
    titulo = COALESCE(:titulo, titulo),
    banner_escritorio = COALESCE(:banner_escritorio, banner_escritorio),
    banner_tablet = COALESCE(:banner_tablet, banner_tablet),
    banner_movil = COALESCE(:banner_movil, banner_movil),
    fecha_final = COALESCE(:fecha_final, fecha_final),
    dirigido = COALESCE(:dirigido, dirigido)
  WHERE id_campaña = :id
  RETURNING id_campaña, titulo, url_etsy, banner_escritorio, banner_tablet, banner_movil, dirigido, fecha_inicio, fecha_final, created_at
");

$stmt->execute([
  "id" => $id,
  "titulo" => $_POST['titulo'] ?? null,
  "banner_escritorio" => $device === "desktop" ? $bannerUrl : null,
  "banner_tablet" => $device === "tablet" ? $bannerUrl : null,
  "banner_movil" => $device === "mobile" ? $bannerUrl : null,
  "fecha_final" => $_POST['fecha_final'] ?? null,
  "dirigido" => $_POST['dirigido'] ?? null
]);

$updated = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$updated) {
    http_response_code(404);
    echo json_encode(["error" => "Campaña no encontrada o no se pudo actualizar"], JSON_UNESCAPED_UNICODE);
    exit;
}

// Respuesta final consistente
echo json_encode([
  "status" => "ok",
  "message" => "Campaña actualizada correctamente",
  "campaign" => $updated
], JSON_UNESCAPED_UNICODE);
