<?php
require __DIR__ . '/../config/admin.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/supabase.php';

$headers = array_change_key_case(getallheaders(), CASE_UPPER);

if (!isset($headers['X-ADMIN-KEY']) || trim($headers['X-ADMIN-KEY']) !== trim(ADMIN_KEY)) {
    http_response_code(403);
    echo json_encode(["error" => "Acceso denegado"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id_campaña'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(["error" => "ID de campaña requerido"]);
    exit;
}

// Si llega un banner nuevo, lo subimos al bucket correcto
$bannerUrl = null;
if (isset($_FILES['banner'])) {
    $banner = $_FILES['banner'];
    $device = $data['device'] ?? "mobile";
    $path = "banners/$device/" . basename($banner['name']);
    $bannerUrl = supabaseUpload("campaigns", $path, $banner['tmp_name'], $banner['type']);
}

// Update solo con columnas que SÍ existen en tu tabla
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
  RETURNING id_campaña, titulo, banner_escritorio, banner_tablet, banner_movil, fecha_inicio, fecha_final, dirigido, created_at
");

$stmt->execute([
  "id" => $id,
  "titulo" => $data['titulo'] ?? null,
  "banner_escritorio" => ($data['device'] ?? null) === "desktop" ? $bannerUrl : null,
  "banner_tablet" => ($data['device'] ?? null) === "tablet" ? $bannerUrl : null,
  "banner_movil" => ($data['device'] ?? null) === "mobile" ? $bannerUrl : null,
  "fecha_final" => $data['fecha_final'] ?? null,
  "dirigido" => $data['dirigido'] ?? null
]);

echo json_encode([
  "status" => "ok",
  "message" => "Campaña actualizada",
  "campaña" => $stmt->fetch(PDO::FETCH_ASSOC)
]);
