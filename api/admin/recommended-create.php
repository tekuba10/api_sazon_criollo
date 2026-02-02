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

if (
    !isset($headers['X-ADMIN-KEY']) ||
    trim($headers['X-ADMIN-KEY']) !== trim(ADMIN_KEY)
) {
    http_response_code(403);
    echo json_encode(["error" => "Acceso denegado"]);
    exit;
}

// =====================
// VALIDACIONES CAMPOS
// =====================
$requiredFields = ['titulo', 'url'];

foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
        http_response_code(400);
        echo json_encode(["error" => "Campo requerido: $field"]);
        exit;
    }
}

// =====================
// VALIDACIÓN POSTER
// =====================
if (!isset($_FILES['poster'])) {
    http_response_code(400);
    echo json_encode(["error" => "Archivo requerido: poster"]);
    exit;
}

// =====================
// PATH POSTER (SIN TIMESTAMP)
// =====================
$posterPath = 'poster/' . basename($_FILES['poster']['name']);

// =====================
// VALIDAR DUPLICADO EN STORAGE
// =====================
if (supabaseFileExists('poster', basename($_FILES['poster']['name']))) {
    http_response_code(409);
    echo json_encode([
        "error" => "El poster ya existe",
        "archivo" => $posterPath
    ]);
    exit;
}

// =====================
// SUBIDA POSTER
// =====================
$posterUrl = supabaseUpload(
    'poster',
    basename($_FILES['poster']['name']),
    $_FILES['poster']['tmp_name'],
    $_FILES['poster']['type']
);

if (!$posterUrl) {
    http_response_code(500);
    echo json_encode(["error" => "Error subiendo el poster"]);
    exit;
}

// =====================
// INSERT RECOMENDADO
// =====================
try {
    $stmt = $pdo->prepare("
        INSERT INTO public.recomendado
        (titulo, url, poster)
        VALUES
        (:titulo, :url, :poster)
        RETURNING id_recomendado, fecha_creacion
    ");

    $stmt->execute([
        ':titulo' => $_POST['titulo'],
        ':url'    => $_POST['url'],
        ':poster' => $posterUrl
    ]);

    $res = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (Exception $e) {

    // rollback storage
    supabaseDelete('poster', basename($_FILES['poster']['name']));

    http_response_code(500);
    echo json_encode([
        "error" => "Error al guardar en base de datos"
    ]);
    exit;
}

// =====================
// RESPONSE
// =====================
echo json_encode([
    "status" => "ok",
    "data" => [
        "id_recomendado" => $res['id_recomendado'],
        "titulo" => $_POST['titulo'],
        "url" => $_POST['url'],
        "poster" => $posterUrl,
        "fecha_creacion" => $res['fecha_creacion']
    ]
]);
exit;
