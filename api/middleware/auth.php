<?php
require __DIR__ . '/../middleware/auth.php'; // ← Esto ya valida el JWT
require __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

// 1. Obtener usuario autenticado desde el middleware
$user = $_REQUEST['user'] ?? null;
$idUser = $user['id_user'] ?? null;

if (!$idUser) {
    http_response_code(401);
    echo json_encode(["error" => "Token inválido o sin usuario"]);
    exit;
}

try {
    // 2. Consultar recetas de ese usuario
    $stmt = $pdo->prepare("
      SELECT id_receta, titulo, descripcion, pdf_url, created_at
      FROM public.recetas
      WHERE id_user = :id_user
      ORDER BY created_at DESC
    ");
    $stmt->execute(["id_user" => $idUser]);
    $recetas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
      "status" => "ok",
      "recipes" => $recetas
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "No se pudieron obtener las recetas"]);
}
