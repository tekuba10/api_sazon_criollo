<?php
require __DIR__ . '/../middleware/auth.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/supabase.php';

header('Content-Type: application/json; charset=utf-8');

// Usuario autenticado
$user = $_REQUEST['user'] ?? null;
$idUser = $user['id_user'] ?? null;

if (!$idUser) {
    http_response_code(401);
    echo json_encode(["error" => "Token inválido o usuario no encontrado"]);
    exit;
}

try {

    $stmt = $pdo->prepare("
        SELECT id_receta, descripcion, cover_image, created_at
        FROM public.recetas
        WHERE id_user = :id_user
        ORDER BY created_at DESC
    ");

    $stmt->execute(['id_user' => $idUser]);
    $recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = [];

    foreach ($recipes as $recipe) {

        // Generar signed URL para cover (24 horas)
        $signedCover = supabaseCreateSignedUrl(
            "recipes",
            $recipe['cover_image'],
            86400 // 24h
        );

        $result[] = [
            "id_receta"  => $recipe['id_receta'],
            "descripcion"=> $recipe['descripcion'],
            "cover_image"=> $signedCover,
            "created_at" => $recipe['created_at']
        ];
    }

    echo json_encode([
        "status" => "ok",
        "recipes" => $result
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "No se pudieron obtener las recetas"]);
}
