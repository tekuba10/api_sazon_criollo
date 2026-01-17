<?php

require __DIR__ . '/../config/admin.php';
require __DIR__ . '/../config/database.php';

// Normalizar headers
$headers = array_change_key_case(getallheaders(), CASE_UPPER);

// Validar ADMIN KEY
if (
    !isset($headers['X-ADMIN-KEY']) ||
    trim($headers['X-ADMIN-KEY']) !== trim(ADMIN_KEY)
) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

// Generar token único
$token = bin2hex(random_bytes(32));
$expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

try {
    // Guardar link en BD
    $stmt = $pdo->prepare("
        INSERT INTO registration_links (token, expires_at)
        VALUES (:token, :expires_at)
    ");

    $stmt->execute([
        'token' => $token,
        'expires_at' => $expiresAt
    ]);

    // Respuesta
    echo json_encode([
        'registration_url' => "https://sazoncriolloapp.com/register?$token
",
        'expires_at' => $expiresAt
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'No se pudo generar el link'
    ]);
}
