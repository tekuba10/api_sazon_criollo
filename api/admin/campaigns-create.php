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
// VALIDACIONES FECHAS
// =====================
date_default_timezone_set('Europe/Madrid'); // ajusta si usas otra zona

$fechaInicio = DateTime::createFromFormat('Y-m-d H:i:s', $_POST['fecha_inicio']);
$fechaFinal  = DateTime::createFromFormat('Y-m-d H:i:s', $_POST['fecha_final']);

if (!$fechaInicio || !$fechaFinal) {
    http_response_code(400);
    echo json_encode([
        "error" => "Formato de fecha inválido. Use Y-m-d H:i:s"
    ]);
    exit;
}

// Fecha actual (sin segundos para evitar falsos negativos)
$hoy = new DateTime();
$hoy->setTime(0, 0, 0);

// Clonamos fecha_inicio y la normalizamos
$fechaInicioCompare = clone $fechaInicio;
$fechaInicioCompare->setTime(0, 0, 0);

// ❌ fecha_inicio no puede ser menor que hoy
if ($fechaInicioCompare < $hoy) {
    http_response_code(400);
    echo json_encode([
        "error" => "La fecha de inicio no puede ser anterior a la fecha actual"
    ]);
    exit;
}

// ❌ fecha_final no puede ser menor que fecha_inicio
if ($fechaFinal < $fechaInicio) {
    http_response_code(400);
    echo json_encode([
        "error" => "La fecha final no puede ser anterior a la fecha de inicio"
    ]);
    exit;
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
// PATHS (ORIGINAL, SIN TIMESTAMP)
// =====================
$paths = [
    'desktop/' . basename($_FILES['banner_escritorio']['name']),
    'tablet/'  . basename($_FILES['banner_tablet']['name']),
    'mobile/'  . basename($_FILES['banner_movil']['name']),
];

// =====================
// VALIDAR DUPLICADOS EN STORAGE
// =====================
foreach ($paths as $path) {
    if (supabaseFileExists('campaigns', $path)) {
        http_response_code(409);
        echo json_encode([
            "error" => "Ya existe un archivo con ese nombre",
            "archivo" => $path
        ]);
        exit;
    }
}

// =====================
// SUBIDA DE BANNERS
// =====================
$uploadedPaths = [];

if (!supabaseUpload('campaigns', $paths[0], $_FILES['banner_escritorio']['tmp_name'], $_FILES['banner_escritorio']['type'])) goto rollback;
$uploadedPaths[] = $paths[0];

if (!supabaseUpload('campaigns', $paths[1], $_FILES['banner_tablet']['tmp_name'], $_FILES['banner_tablet']['type'])) goto rollback;
$uploadedPaths[] = $paths[1];

if (!supabaseUpload('campaigns', $paths[2], $_FILES['banner_movil']['tmp_name'], $_FILES['banner_movil']['type'])) goto rollback;
$uploadedPaths[] = $paths[2];

// =====================
// URLS PÚBLICAS SUPABASE
// =====================
$banner_escritorio = SUPABASE_URL . "/storage/v1/object/public/campaigns/" . $paths[0];
$banner_tablet     = SUPABASE_URL . "/storage/v1/object/public/campaigns/" . $paths[1];
$banner_movil      = SUPABASE_URL . "/storage/v1/object/public/campaigns/" . $paths[2];

// =====================
// INSERT CAMPAÑA
// =====================
$stmt = $pdo->prepare("
    INSERT INTO campañas
    (titulo, url_etsy, banner_escritorio, banner_tablet, banner_movil,
     fecha_inicio, fecha_final, dirigido_todos, activa)
    VALUES
    (:titulo, :url_etsy, :be, :bt, :bm,
     :fi, :ff, :todos, :activa)
    RETURNING id_campaña, created_at
");

$stmt->bindValue(':titulo', $_POST['titulo']);
$stmt->bindValue(':url_etsy', $_POST['url_etsy']);
$stmt->bindValue(':be', $banner_escritorio);
$stmt->bindValue(':bt', $banner_tablet);
$stmt->bindValue(':bm', $banner_movil);
$stmt->bindValue(':fi', $_POST['fecha_inicio']);
$stmt->bindValue(':ff', $_POST['fecha_final']);
$stmt->bindValue(':todos', $dirigidoTodos, PDO::PARAM_BOOL);
$stmt->bindValue(':activa', $_POST['activa'] === 'true', PDO::PARAM_BOOL);

$stmt->execute();
$res = $stmt->fetch(PDO::FETCH_ASSOC);

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
        $stmtUser->execute([
            ':id_campania' => $res['id_campaña'],
            ':id_user' => $idUser
        ]);
    }
}

// =====================
// RESPONSE
// =====================
echo json_encode([
    "status" => "ok",
    "id_campaña" => $res['id_campaña'],
    "banners" => [
        "desktop" => $banner_escritorio,
        "tablet"  => $banner_tablet,
        "mobile"  => $banner_movil
    ],
    "created_at" => $res['created_at']
]);
exit;

// =====================
// ROLLBACK STORAGE
// =====================
rollback:
foreach ($uploadedPaths as $p) {
    supabaseDelete('campaigns', $p);
}

http_response_code(500);
echo json_encode(["error" => "Error subiendo banners, operación revertida"]);
