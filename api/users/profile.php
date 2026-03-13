<?php
require __DIR__ . '/../middleware/auth.php';
require __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$user = $GLOBALS['auth_user'] ?? null;
$idUser = $user['id_user'] ?? null;

if (!$idUser) {
    http_response_code(401);
    echo json_encode(["error" => "Usuario no autenticado"]);
    exit;
}

/* ===============================
   GET → OBTENER PERFIL
================================= */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $stmt = $pdo->prepare("
        SELECT 
            id_user,
            nombre,
            apellido,
            usuario,
            email,
            fecha_nacimiento
        FROM users
        WHERE id_user = :id_user
        LIMIT 1
    ");

    $stmt->execute(['id_user' => $idUser]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile) {
        http_response_code(404);
        echo json_encode(["error" => "Usuario no encontrado"]);
        exit;
    }

    echo json_encode([
        "success" => true,
        "data" => $profile
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}


/* ===============================
   PUT / PATCH → ACTUALIZAR PERFIL
================================= */
if ($_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'PATCH') {

    $input = json_decode(file_get_contents("php://input"), true);

    if (!$input || !is_array($input)) {
        http_response_code(400);
        echo json_encode(["error" => "JSON inválido"]);
        exit;
    }

    // SOLO CAMPOS PERMITIDOS
    $allowedFields = ['nombre', 'apellido', 'fecha_nacimiento'];

    // 🔒 Detectar campos no permitidos
    $invalidFields = array_diff(array_keys($input), $allowedFields);

    if (!empty($invalidFields)) {
        http_response_code(400);
        echo json_encode([
            "error" => "Campos no permitidos: " . implode(', ', $invalidFields)
        ]);
        exit;
    }

    $updates = [];
    $params = ['id_user' => $idUser];

    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {

            $value = trim($input[$field]);

            if ($value === '') {
                http_response_code(400);
                echo json_encode(["error" => "El campo $field no puede estar vacío"]);
                exit;
            }

            if ($field === 'nombre' || $field === 'apellido') {

                if (strlen($value) < 2 || strlen($value) > 50) {
                    http_response_code(400);
                    echo json_encode([
                        "error" => "El campo $field debe tener entre 2 y 50 caracteres"
                    ]);
                    exit;
                }
            }

            if ($field === 'fecha_nacimiento') {

                if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $value)) {
                    http_response_code(400);
                    echo json_encode([
                        "error" => "Formato de fecha inválido (YYYY-MM-DD)"
                    ]);
                    exit;
                }

                $hoy = new DateTime();
                $fecha = new DateTime($value);

                if ($fecha > $hoy) {
                    http_response_code(400);
                    echo json_encode([
                        "error" => "La fecha de nacimiento no puede ser futura"
                    ]);
                    exit;
                }

                // Edad mínima 13 años
                $edad = $hoy->diff($fecha)->y;
                if ($edad < 13) {
                    http_response_code(400);
                    echo json_encode([
                        "error" => "Debes tener al menos 13 años"
                    ]);
                    exit;
                }
            }

            $updates[] = "$field = :$field";
            $params[$field] = $value;
        }
    }

    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(["error" => "No hay campos válidos para actualizar"]);
        exit;
    }

    $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id_user = :id_user";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode([
        "success" => true,
        "message" => "Perfil actualizado correctamente"
    ]);
    exit;
}

http_response_code(405);
echo json_encode(["error" => "Método no permitido"]);