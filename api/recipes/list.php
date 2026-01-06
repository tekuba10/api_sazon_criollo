<?php
require __DIR__ . '/../middleware/auth.php';
require __DIR__ . '/../config/database.php';

$user = $_REQUEST['user'];

$stmt = $pdo->prepare("
    SELECT id_receta, titulo, descripcion, pdf_url, cover_image, created_at
    FROM public.recetas
    WHERE id_user = :id_user
    ORDER BY created_at DESC
");

$stmt->execute(['id_user' => $user['id_user']]);

echo json_encode([
  "status" => "ok",
  "recetas" => $stmt->fetchAll(PDO::FETCH_ASSOC)
]);
