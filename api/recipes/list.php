<?php
require __DIR__ . '/../middleware/auth.php';
require __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

// 1. Usuario autenticado desde JWT (middleware ya lo guarda en $_REQUEST['user'])
$user = $_REQUEST['user'] ?? null;
$idUser = $user['id_user'] ?? null;

if (!$idUser) {
    http_response_code(401);
    echo json_encode(["error" => "Token inválido o usuario no encontrado"]);
    exit;
}

try {
    // 2. Consultar recetas de ese usuario (columnas reales)
    $stmt = $pdo->prepare("
        SELECT id_receta, titulo, descripcion, pdf_url, created_at
        FROM public.recetas
        WHERE id_user = :id_user
        ORDER BY created_at DESC
    ");
    $stmt->execute(['id_user' => $idUser]);

    echo json_encode([
      "status" => "ok",
      "recipes" => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "No se pudieron obtener las recetas"]);
}
