<?php
require __DIR__ . '/../middleware/auth.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/supabase.php';

header("Content-Type: application/json");

$user = $_REQUEST['user'] ?? null;

if (!$user) {
    http_response_code(401);
    echo json_encode(["error" => "No autenticado"]);
    exit;
}

$id = $_POST['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(["error" => "ID de receta requerido"]);
    exit;
}

$descripcion = trim($_POST['descripcion'] ?? '');

if ($descripcion === '') {
    http_response_code(400);
    echo json_encode(["error" => "Descripción requerida"]);
    exit;
}

try {

    /* =========================
       1️⃣ Obtener receta actual
    ========================= */

    $stmt = $pdo->prepare("
        SELECT *
        FROM public.recetas
        WHERE id_receta = :id AND id_user = :user
    ");

    $stmt->execute([
        'id'   => $id,
        'user' => $user['id_user']
    ]);

    $receta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$receta) {
        http_response_code(403);
        echo json_encode(["error" => "No puedes editar esta receta"]);
        exit;
    }

    $newPdfPath   = $receta['pdf_url'];
    $newCoverPath = $receta['cover_image'];

    /* =========================
       2️⃣ Si hay nuevo PDF
    ========================= */

    if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {

        $pdf = $_FILES['pdf'];

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $pdfMime = $finfo->file($pdf['tmp_name']);

        if ($pdfMime !== 'application/pdf') {
            http_response_code(400);
            echo json_encode(["error" => "El archivo debe ser un PDF válido"]);
            exit;
        }

        $uniqueId = bin2hex(random_bytes(8));
        $userId   = $user['id_user'];

        $newPdfPath   = "users/{$userId}/pdf/{$uniqueId}.pdf";
        $newCoverPath = "users/{$userId}/cover/{$uniqueId}.jpg";

        /* Subir nuevo PDF */

        $pdfUpload = supabaseUpload("recipes", $newPdfPath, $pdf['tmp_name'], $pdfMime);

        if (!$pdfUpload) {
            http_response_code(500);
            echo json_encode(["error" => "No se pudo subir el nuevo PDF"]);
            exit;
        }

        /* Generar nueva portada */

        $tempCoverPath = sys_get_temp_dir() . "/cover_{$uniqueId}.jpg";

        try {
            $imagick = new \Imagick();
            $imagick->setResolution(150, 150);
            $imagick->readImage($pdf['tmp_name'] . "[0]");
            $imagick->setImageFormat("jpeg");
            $imagick->setImageCompressionQuality(85);
            $imagick->writeImage($tempCoverPath);
            $imagick->clear();
            $imagick->destroy();
        } catch (Exception $e) {

            supabaseDelete("recipes", $newPdfPath);

            http_response_code(500);
            echo json_encode(["error" => "No se pudo generar la nueva portada"]);
            exit;
        }

        $coverUpload = supabaseUpload(
            "recipes",
            $newCoverPath,
            $tempCoverPath,
            "image/jpeg"
        );

        unlink($tempCoverPath);

        if (!$coverUpload) {

            supabaseDelete("recipes", $newPdfPath);

            http_response_code(500);
            echo json_encode(["error" => "No se pudo subir la nueva portada"]);
            exit;
        }

        /* Borrar archivos antiguos */

        supabaseDelete("recipes", $receta['pdf_url']);
        supabaseDelete("recipes", $receta['cover_image']);
    }

    /* =========================
       3️⃣ Actualizar BD
    ========================= */

    $stmt = $pdo->prepare("
        UPDATE public.recetas
        SET descripcion = :descripcion,
            pdf_url = :pdf_url,
            cover_image = :cover_image
        WHERE id_receta = :id AND id_user = :user
        RETURNING id_receta, descripcion, pdf_url, cover_image, created_at
    ");

    $stmt->execute([
        'id'          => $id,
        'user'        => $user['id_user'],
        'descripcion' => $descripcion,
        'pdf_url'     => $newPdfPath,
        'cover_image' => $newCoverPath
    ]);

    $updated = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        "status"  => "ok",
        "message" => "Receta actualizada correctamente",
        "receta"  => $updated
    ], JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {

    http_response_code(500);
    echo json_encode(["error" => "Error al actualizar la receta"]);
}
