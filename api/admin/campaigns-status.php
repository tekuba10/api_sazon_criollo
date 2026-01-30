<?php
require __DIR__ . '/../middleware/cors.php';
require __DIR__ . '/../config/admin.php';
require __DIR__ . '/../config/database.php';

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
// INPUT
// ============================
$id = $_POST['id_campaña'] ?? $_POST['id_campana'] ?? null;
$estadoRaw = $_POST['estado'] ?? null;

if (!$id || !in_array($estadoRaw, ['true', 'false'], true)) {
    http_response_code(400);
    echo json_encode([
        "error" => "Debe enviar id_campaña y estado (true | false)"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$estado = $estadoRaw === 'true';

// ============================
// OBTENER CAMPAÑA
// ============================
$stmt = $pdo->prepare("
    SELECT id_campaña, activa, fecha_inicio, fecha_final
    FROM campañas
    WHERE id_campaña = :id
    LIMIT 1
");
$stmt->execute(["id" => $id]);
$campaign = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$campaign) {
    http_response_code(404);
    echo json_encode([
        "error" => "Campaña no encontrada"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================
// MISMO ESTADO → NO HACER NADA
// ============================
if ((bool)$campaign['activa'] === $estado) {
    echo json_encode([
        "status" => "ok",
        "message" => $estado
            ? "La campaña ya está activa"
            : "La campaña ya está desactivada",
        "campaign" => [
            "id_campaña" => $campaign['id_campaña'],
            "activa" => (bool)$campaign['activa']
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================
// VALIDAR FECHAS SOLO AL ACTIVAR
// ============================
if ($estado === true) {

    $now = new DateTime('now');
    $fechaFinal = new DateTime($campaign['fecha_final']);

    if ($fechaFinal < $now) {
        http_response_code(409);
        echo json_encode([
            "error" => "La campaña ya finalizó. Debe modificar las fechas antes de activarla."
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ============================
// UPDATE
// ============================
$stmt = $pdo->prepare("
    UPDATE campañas
    SET activa = :estado
    WHERE id_campaña = :id
    RETURNING id_campaña, activa
");

$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->bindValue(':estado', $estado, PDO::PARAM_BOOL);
$stmt->execute();

$res = $stmt->fetch(PDO::FETCH_ASSOC);

// ============================
// RESPONSE
// ============================
echo json_encode([
    "status" => "ok",
    "message" => $estado
        ? "Campaña activada correctamente"
        : "Campaña desactivada correctamente",
    "campaign" => $res
], JSON_UNESCAPED_UNICODE);
