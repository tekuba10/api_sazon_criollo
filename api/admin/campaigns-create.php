<?php
require __DIR__ . '/../middleware/cors.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/admin.php';
require __DIR__ . '/../config/supabase.php';

header('Content-Type: application/json');

// =====================
// AUTH
// =====================
$headers = array_change_key_case(getallheaders(), CASE_UPPER);

if (!isset($headers['X-ADMIN-KEY']) || trim($headers['X-ADMIN-KEY']) !== trim(ADMIN_KEY)) {
    http_response_code(403);
    echo json_encode(["error" => "Acceso denegado"]);
    exit;
}

// =====================
// VALIDACIONES CAMPOS
// =====================
$requiredFields = ['titulo', 'url_etsy', 'fecha_inicio', 'fecha_final', 'dirigido_todos'];
foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || $_POST[$field] === '') {
        http_response_code(400);
        echo json_encode(["error" => "Campo requerido: $field"]);
        exit;
    }
}

// =====================
// VALIDACIONES ARCHIVOS
// =====================
$requiredFiles = ['banner_escritorio', 'banner_tablet', 'banner_movil'];
foreach ($requiredFiles as $file) {
    if (!isset($_FILES[$file])) {
        http_response_code(400);
        echo json_encode(["error" => "Archivo requerido: $file"]);
        exit;
    }
}

// =====================
// VALIDAR dirigido_todos
// =====================
if (!in_array($_POST['dirigido_todos'], ['true', 'false'], true)) {
    http_response_code(400);
    echo json_encode(["error" => "dirigido_todos debe ser 'true' o 'false'"]);
    exit;
}

$dirigidoTodos = $_POST['dirigido_todos'] === 'true';

// =====================
// SUBIDA DE BANNERS
// =====================
$banner_escritorio = supabaseUpload(
    "campaigns",
    "desktop/" . basename($_FILES['banner_escritorio']['name']),
    $_FILES['banner_escritorio']['tmp_name'],
    $_FILES['banner_escritorio']['type']
);

$banner_tablet = supabaseUpload(
    "campaigns",
    "tablet/" . basename($_FILES['banner_tablet']['name']),
    $_FILES['banner_tablet']['tmp_name'],
    $_FILES['banner_tablet']['type']
);

$banner_movil = supabaseUpload(
    "campaigns",
    "mobile/" . basename($_FILES['banner_movil']['name']),
    $_FILES['banner_movil']['tmp_name'],
    $_FILES['banner_movil']['type']
);

// =====================
// INSERT CAMPAÑA
// =====================
$stmt = $pdo->prepare("
  INSERT INTO campañas
  (titulo, url_etsy, banner_escritorio, banner_tablet, banner_movil, fecha_inicio, fecha_final, dirigido_todos, activa)
  VALUES
  (:titulo, :url_etsy, :be, :bt, :bm, :fi, :ff, :todos, true)
  RETURNING id_campaña, created_at
");

$stmt->bindValue(':titulo', $_POST['titulo'], PDO::PARAM_STR);
$stmt->bindValue(':url_etsy', $_POST['url_etsy'], PDO::PARAM_STR);
$stmt->bindValue(':be', $banner_escritorio, PDO::PARAM_STR);
$stmt->bindValue(':bt', $banner_tablet, PDO::PARAM_STR);
$stmt->bindValue(':bm', $banner_movil, PDO::PARAM_STR);
$stmt->bindValue(':fi', $_POST['fecha_inicio'], PDO::PARAM_STR);
$stmt->bindValue(':ff', $_POST['fecha_final'], PDO::PARAM_STR);
$stmt->bindValue(':todos', $dirigidoTodos, PDO::PARAM_BOOL);

$stmt->execute();

$res = $stmt->fetch(PDO::FETCH_ASSOC);
$idCampaña = $res['id_campaña'];

// =====================
// USUARIOS ESPECÍFICOS
// =====================
if (!$dirigidoTodos) {

    if (!isset($_POST['usuarios']) || !is_array($_POST['usuarios'])) {
        http_response_code(400);
        echo json_encode(["error" => "Debe indicar usuarios[] cuando dirigido_todos=false"]);
        exit;
    }

    $stmtUser = $pdo->prepare("
        INSERT INTO campaña_usuarios (id_campaña, id_user)
        VALUES (:id_campania, :id_user)
    ");

    foreach ($_POST['usuarios'] as $idUser) {
        $stmtUser->bindValue(':id_campania', $idCampaña, PDO::PARAM_INT);
        $stmtUser->bindValue(':id_user', $idUser, PDO::PARAM_INT);
        $stmtUser->execute();
    }
}

// =====================
// RESPONSE
// =====================
echo json_encode([
    "status" => "ok",
    "id_campaña" => $idCampaña,
    "banners" => [
        "desktop" => $banner_escritorio,
        "tablet" => $banner_tablet,
        "mobile" => $banner_movil
    ],
    "created_at" => $res['created_at']
]);
