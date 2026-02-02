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
// VALIDACIONES MÍNIMAS
// =====================
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    http_response_code(400);
    echo json_encode(["error" => "ID inválido"]);
    exit;
}

$id = (int) $_POST['id'];

// =====================
// CAMPOS A ACTUALIZAR
// =====================
$campos = [];
$params = [':id' => $id];

// titulo (solo si viene y no está vacío)
if (isset($_POST['titulo']) && trim($_POST['titulo']) !== '') {
    $campos[] = 'titulo = :titulo';
    $params[':titulo'] = trim($_POST['titulo']);
}

// url (solo si viene y no está vacía)
if (isset($_POST['url']) && trim($_POST['url']) !== '') {
    $campos[] = 'url = :url';
    $params[':url'] = trim($_POST['url']);
}

// =====================
// VALIDAR QUE ALGO SE ACTUALICE
// =====================
$hayPosterNuevo = isset($_FILES['poster']) && $_FILES['poster']['error'] === 0;

if (empty($campos) && !$hayPosterNuevo) {
    http_response_code(400);
    echo json_encode([
        "error" => "No hay datos válidos para actualizar"
    ]);
    exit;
}

// =====================
// OBTENER RECOMENDADO ACTUAL
// =====================
$stmt = $pdo->prepare("
    SELECT poster
    FROM public.recomendado
    WHERE id_recomendado = :id
    LIMIT 1
");
$stmt->execute([':id' => $id]);
$current = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$current) {
    http_response_code(404);
    echo json_encode(["error" => "Recomendado no encontrado"]);
    exit;
}

$currentPosterUrl = $current['poster'];

// =====================
// POSTER OPCIONAL
// =====================
if ($hayPosterNuevo) {

    $newPosterName = basename($_FILES['poster']['name']);

    if (supabaseFileExists('poster', $newPosterName)) {
        http_response_code(409);
        echo json_encode([
            "error" => "Ya existe un poster con ese nombre"
        ]);
        exit;
    }

    $newPosterUrl = supabaseUpload(
        'poster',
        $newPosterName,
        $_FILES['poster']['tmp_name'],
        $_FILES['poster']['type']
    );

    if (!$newPosterUrl) {
        http_response_code(500);
        echo json_encode([
            "error" => "Error subiendo el poster"
        ]);
        exit;
    }

    $campos[] = 'poster = :poster';
    $params[':poster'] = $newPosterUrl;
}

// =====================
// UPDATE
// =====================
try {

    $sql = "
        UPDATE public.recomendado
        SET " . implode(', ', $campos) . "
        WHERE id_recomendado = :id
    ";

    $stmtUpdate = $pdo->prepare($sql);
    $stmtUpdate->execute($params);

    // =====================
    // BORRAR POSTER VIEJO SOLO SI CAMBIÓ
    // =====================
    if ($hayPosterNuevo && !empty($currentPosterUrl)) {
        $parsed = parse_url($currentPosterUrl);
        $oldPath = str_replace(
            '/storage/v1/object/public/poster/',
            '',
            $parsed['path']
        );
        supabaseDelete('poster', $oldPath);
    }

    echo json_encode([
        "status" => "ok",
        "updated_id" => $id
    ]);
    exit;

} catch (Exception $e) {

    // rollback poster nuevo si algo falla
    if ($hayPosterNuevo) {
        supabaseDelete('poster', $newPosterName);
    }

    http_response_code(500);
    echo json_encode([
        "error" => "Error al actualizar el recomendado"
    ]);
    exit;
}
