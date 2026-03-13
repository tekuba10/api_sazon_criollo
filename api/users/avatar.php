<?php
require __DIR__ . '/../middleware/auth.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/supabase.php';

header("Content-Type: application/json");

define('SUPABASE_AVATAR_PUBLIC_URL', 
    'https://hjlxbalnmotkgnyfuyxu.supabase.co/storage/v1/object/public/avatars/'
);

$user = $_REQUEST['user'] ?? null;

if (!$user) {
    http_response_code(401);
    echo json_encode(["error" => "No autenticado"]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

/* =========================================================
   GET → OBTENER AVATAR
========================================================= */
if ($method === 'GET') {

    $stmt = $pdo->prepare("
        SELECT foto_perfil
        FROM public.users
        WHERE id_user = :id
    ");

    $stmt->execute(['id' => $user['id_user']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        http_response_code(404);
        echo json_encode(["error" => "Usuario no encontrado"]);
        exit;
    }

    // ✅ Si viene vacío o null → usar default
    $path = !empty($result['foto_perfil'])
        ? $result['foto_perfil']
        : 'default.png';

    // ✅ Si ya es URL completa no concatenar
    $url = str_starts_with($path, 'http')
        ? $path
        : SUPABASE_AVATAR_PUBLIC_URL . $path;

    echo json_encode([
        "path" => $path,
        "url"  => $url
    ], JSON_UNESCAPED_SLASHES);

    exit;
}

/* =========================================================
   POST → SUBIR / REEMPLAZAR AVATAR
========================================================= */
if ($method === 'POST') {

    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(["error" => "Imagen requerida"]);
        exit;
    }

    try {

        $file = $_FILES['avatar'];

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);

        $allowed = ['image/jpeg', 'image/png', 'image/webp'];

        if (!in_array($mime, $allowed)) {
            http_response_code(400);
            echo json_encode(["error" => "Formato no permitido"]);
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT foto_perfil
            FROM public.users
            WHERE id_user = :id
        ");

        $stmt->execute(['id' => $user['id_user']]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$current) {
            http_response_code(404);
            echo json_encode(["error" => "Usuario no encontrado"]);
            exit;
        }

        $oldPath = $current['foto_perfil'];

        $uniqueId  = bin2hex(random_bytes(8));
        $userId    = $user['id_user'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        $newPath = "users/{$userId}/avatar/{$uniqueId}.{$extension}";

        $upload = supabaseUpload("avatars", $newPath, $file['tmp_name'], $mime);

        if (!$upload) {
            http_response_code(500);
            echo json_encode(["error" => "No se pudo subir la imagen"]);
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE public.users
            SET foto_perfil = :foto
            WHERE id_user = :id
            RETURNING id_user, foto_perfil
        ");

        $stmt->execute([
            'foto' => $newPath,
            'id'   => $user['id_user']
        ]);

        $updated = $stmt->fetch(PDO::FETCH_ASSOC);

        if (
            $oldPath &&
            !str_contains($oldPath, 'default.png') &&
            $oldPath !== $newPath
        ) {
            supabaseDelete("avatars", $oldPath);
        }

        echo json_encode([
            "status"  => "ok",
            "message" => "Avatar actualizado correctamente",
            "path"    => $updated['foto_perfil'],
            "url"     => SUPABASE_AVATAR_PUBLIC_URL . $updated['foto_perfil']
        ], JSON_UNESCAPED_SLASHES);

    } catch (Exception $e) {

        http_response_code(500);
        echo json_encode(["error" => "Error al actualizar avatar"]);
    }

    exit;
}

/* =========================================================
   DELETE → VOLVER A DEFAULT
========================================================= */
if ($method === 'DELETE') {

    try {

        $stmt = $pdo->prepare("
            SELECT foto_perfil
            FROM public.users
            WHERE id_user = :id
        ");

        $stmt->execute(['id' => $user['id_user']]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$current) {
            http_response_code(404);
            echo json_encode(["error" => "Usuario no encontrado"]);
            exit;
        }

        $oldPath = $current['foto_perfil'];

        if (empty($oldPath) || str_contains($oldPath, 'default.png')) {
            echo json_encode([
                "status" => "ok",
                "message" => "Ya estás usando la imagen por defecto",
                "url" => SUPABASE_AVATAR_PUBLIC_URL . "default.png"
            ], JSON_UNESCAPED_SLASHES);
            exit;
        }

        supabaseDelete("avatars", $oldPath);

        $defaultPath = "default.png";

        $stmt = $pdo->prepare("
            UPDATE public.users
            SET foto_perfil = :foto
            WHERE id_user = :id
            RETURNING id_user, foto_perfil
        ");

        $stmt->execute([
            'foto' => $defaultPath,
            'id'   => $user['id_user']
        ]);

        echo json_encode([
            "status"  => "ok",
            "message" => "Avatar eliminado correctamente",
            "path"    => $defaultPath,
            "url"     => SUPABASE_AVATAR_PUBLIC_URL . $defaultPath
        ], JSON_UNESCAPED_SLASHES);

    } catch (Exception $e) {

        http_response_code(500);
        echo json_encode(["error" => "Error al eliminar avatar"]);
    }

    exit;
}

/* =========================================================
   MÉTODO NO PERMITIDO
========================================================= */
http_response_code(405);
echo json_encode(["error" => "Método no permitido"]);
exit;