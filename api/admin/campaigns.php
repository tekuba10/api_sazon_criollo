<?php
require __DIR__ . '/../middleware/cors.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/admin.php';
require __DIR__ . '/../config/supabase.php';

$headers = array_change_key_case(getallheaders(), CASE_UPPER);

if (!isset($headers['X-ADMIN-KEY']) || trim($headers['X-ADMIN-KEY']) !== trim(ADMIN_KEY)) {
    http_response_code(403);
    echo json_encode(["error" => "Acceso denegado"]);
    exit;
}

if (!isset($_FILES['banner'])) {
    http_response_code(400);
    echo json_encode(["error" => "Banner requerido"]);
    exit;
}

$banner = $_FILES['banner'];
$device = $_POST['device'] ?? "desktop";
$path = "banners/$device/" . basename($banner['name']);

$bannerUrl = supabaseUpload("campaigns", $path, $banner['tmp_name'], $banner['type']);

$stmt = $pdo->prepare("
  INSERT INTO public.campañas (titulo, url_etsy, banner_escritorio, banner_tablet, banner_movil, fecha_inicio, fecha_final, dirigido)
  VALUES (:titulo, :url_etsy, :banner_escritorio, :banner_tablet, :banner_movil, NOW(), :fecha_final, :dirigido)
  RETURNING id_campaña, created_at
");

$stmt->execute([
  "titulo" => $_POST['titulo'],
  "url_etsy" => $_POST['url_etsy'] ?? null,
  "banner_escritorio" => null,
  "banner_tablet" => null,
  "banner_movil" => $bannerUrl,
  "fecha_final" => $_POST['fecha_fin'] ?? date('Y-m-d H:i:s', strtotime('+7 days')),
  "dirigido" => $_POST['dirigido'] ?? null
]);

$res = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
  "status" => "ok",
  "id_campaña" => $res['id_campaña'],
  "banner_movil" => $bannerUrl,
  "created_at" => $res['created_at']
]);
