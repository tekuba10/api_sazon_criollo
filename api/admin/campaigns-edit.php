<?php
require __DIR__ . '/../middleware/cors.php';
require __DIR__ . '/../config/admin.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/supabase.php';

header('Content-Type: application/json; charset=utf-8');

// ============================
// AUTH
// ============================
$headers = array_change_key_case(getallheaders(), CASE_UPPER);
if (($headers['X-ADMIN-KEY'] ?? '') !== trim(ADMIN_KEY)) {
    http_response_code(403);
    echo json_encode(["error" => "Acceso denegado"], JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================
// ID campaña
// ============================
$id = $_POST['id_campaña'] ?? $_POST['id_campana'] ?? null;
if (!$id) {
    http_response_code(400);
    echo json_encode(["error" => "ID de campaña requerido"], JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================
// Obtener campaña actual
// ============================
$stmt = $pdo->prepare("
    SELECT
        banner_escritorio,
        banner_tablet,
        banner_movil
    FROM campañas
    WHERE id_campaña = :id
    LIMIT 1
");
$stmt->execute(["id" => $id]);
$current = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$current) {
    http_response_code(404);
    echo json_encode(["error" => "Campaña no encontrada"], JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================
// Manejo de banners (MISMA ESTRUCTURA)
// ============================
$map = [
    'banner_escritorio' => 'desktop',
    'banner_tablet'     => 'tablet',
    'banner_movil'      => 'mobile'
];

$newBanners = [];

foreach ($map as $field => $folder) {

    if (!isset($_FILES[$field])) {
        $newBanners[$field] = null;
        continue;
    }

    $file = $_FILES[$field];
    $fileName = basename($file['name']);
    $path = "$folder/$fileName";

    // ============================
    // VALIDAR SI YA EXISTE EL NUEVO
    // ============================
    if (supabaseFileExists('campaigns', $path)) {

      $labels = [
          'banner_escritorio' => 'banner de escritorio',
          'banner_tablet'     => 'banner de tablet',
          'banner_movil'      => 'banner de móvil'
      ];

      http_response_code(409);
      echo json_encode([
          "error" => "En {$labels[$field]} ya existe una imagen con este nombre. Cámbialo y vuelve a intentarlo.",
          "campo" => $field,
          "archivo" => $path
      ], JSON_UNESCAPED_UNICODE);
      exit;
  }


    // ============================
    // BORRAR ARCHIVO VIEJO
    // ============================
    if (!empty($current[$field])) {
        $oldPath = parse_url($current[$field], PHP_URL_PATH);
        $oldPath = ltrim(str_replace('/storage/v1/object/public/campaigns/', '', $oldPath), '/');

        if (supabaseFileExists('campaigns', $oldPath)) {
            supabaseDelete('campaigns', $oldPath);
        }
    }

    // ============================
    // SUBIR NUEVO ARCHIVO
    // ============================
    if (!supabaseUpload('campaigns', $path, $file['tmp_name'], $file['type'])) {
        http_response_code(500);
        echo json_encode([
            "error" => "Error subiendo $field"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $newBanners[$field] = SUPABASE_URL . "/storage/v1/object/public/campaigns/" . $path;
}


// ============================
// UPDATE campaña
// ============================
$stmt = $pdo->prepare("
    UPDATE campañas
    SET
        titulo = COALESCE(:titulo, titulo),
        url_etsy = COALESCE(:url_etsy, url_etsy),
        fecha_inicio = COALESCE(:fecha_inicio, fecha_inicio),
        fecha_final = COALESCE(:fecha_final, fecha_final),
        banner_escritorio = COALESCE(:be, banner_escritorio),
        banner_tablet = COALESCE(:bt, banner_tablet),
        banner_movil = COALESCE(:bm, banner_movil)
    WHERE id_campaña = :id
    RETURNING
        id_campaña,
        titulo,
        url_etsy,
        banner_escritorio,
        banner_tablet,
        banner_movil,
        fecha_inicio,
        fecha_final,
        created_at
");

$stmt->execute([
    "id" => $id,
    "titulo" => $_POST['titulo'] ?? null,
    "url_etsy" => $_POST['url_etsy'] ?? null,
    "fecha_inicio" => $_POST['fecha_inicio'] ?? null,
    "fecha_final" => $_POST['fecha_final'] ?? null,
    "be" => $newBanners['banner_escritorio'],
    "bt" => $newBanners['banner_tablet'],
    "bm" => $newBanners['banner_movil']
]);

$updated = $stmt->fetch(PDO::FETCH_ASSOC);

// ============================
// RESPONSE
// ============================
echo json_encode([
    "status" => "ok",
    "message" => "Campaña actualizada correctamente",
    "campaign" => $updated
], JSON_UNESCAPED_UNICODE);
