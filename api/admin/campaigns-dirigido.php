<?php
require __DIR__ . '/../middleware/cors.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/admin.php';

header('Content-Type: application/json; charset=utf-8');

// ============================
// VALIDAR ADMIN KEY
// ============================
$headers = array_change_key_case(getallheaders(), CASE_UPPER);

if (
    !isset($headers['X-ADMIN-KEY']) ||
    trim($headers['X-ADMIN-KEY']) !== trim(ADMIN_KEY)
) {
    http_response_code(403);
    echo json_encode(["error" => "Acceso denegado"], JSON_UNESCAPED_UNICODE);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {

    // ================= GET =================
    if ($method === 'GET') {

        $stmt = $pdo->query("
            SELECT
                cu.id_campaña,
                u.id_user,
                CONCAT(u.nombre, ' ', u.apellido) AS nombre_completo,
                u.usuario,
                u.email
            FROM \"campaña_usuarios\" cu
            INNER JOIN users u ON u.id_user = cu.id_user
            ORDER BY cu.id_campaña ASC, u.nombre ASC
        ");

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $campaigns = [];

        foreach ($rows as $row) {
            $idCampaña = (int)$row['id_campaña'];

            if (!isset($campaigns[$idCampaña])) {
                $campaigns[$idCampaña] = [
                    "id_campaña" => $idCampaña,
                    "usuarios"   => []
                ];
            }

            $campaigns[$idCampaña]["usuarios"][] = [
                "id_user"         => (int)$row['id_user'],
                "nombre_completo" => $row['nombre_completo'],
                "usuario"         => $row['usuario'],
                "email"           => $row['email']
            ];
        }

        echo json_encode([
            "status"    => "ok",
            "campaigns" => array_values($campaigns)
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ================= POST =================
    if ($method === 'POST') {

        $input = json_decode(file_get_contents("php://input"), true);

        if (!is_array($input)) {
            http_response_code(400);
            echo json_encode(["error" => "JSON inválido"], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $action    = $input['action'] ?? null;
        $idCampaña = isset($input['id_campaña']) ? (int)$input['id_campaña'] : 0;
        $usuarios  = isset($input['usuarios']) && is_array($input['usuarios'])
            ? $input['usuarios']
            : [];

        if (!$action || !$idCampaña) {
            http_response_code(400);
            echo json_encode(["error" => "Parámetros incompletos"], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $pdo->beginTransaction();

        // =================================================
        // UPDATE TARGETS
        // =================================================
        if ($action === 'update_targets') {

            $dirigidoTodos = isset($input['dirigido_todos'])
                ? (bool)$input['dirigido_todos']
                : false;

            // 🚨 NUEVA VALIDACIÓN
            // Si pasa de TODOS a DIRIGIDO, debe mandar al menos 1 usuario
            if ($dirigidoTodos === false && empty($usuarios)) {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode([
                    "error" => "Para dirigir la campaña debes seleccionar al menos un usuario"
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $stmt = $pdo->prepare("
                UPDATE campañas
                SET dirigido_todos = :dirigido
                WHERE id_campaña = :id
            ");

            $stmt->bindValue(':dirigido', $dirigidoTodos, PDO::PARAM_BOOL);
            $stmt->bindValue(':id', $idCampaña, PDO::PARAM_INT);
            $stmt->execute();

            $stmtDel = $pdo->prepare("
                DELETE FROM \"campaña_usuarios\"
                WHERE id_campaña = :id_del
            ");
            $stmtDel->bindValue(':id_del', $idCampaña, PDO::PARAM_INT);
            $stmtDel->execute();

            if (!$dirigidoTodos && !empty($usuarios)) {

                $stmtIns = $pdo->prepare("
                    INSERT INTO \"campaña_usuarios\" (id_campaña, id_user)
                    VALUES (:id_ins, :user_ins)
                ");

                foreach ($usuarios as $idUser) {
                    $stmtIns->bindValue(':id_ins', $idCampaña, PDO::PARAM_INT);
                    $stmtIns->bindValue(':user_ins', (int)$idUser, PDO::PARAM_INT);
                    $stmtIns->execute();
                }
            }
        }


        // =================================================
        // ADD USERS (CON VALIDACIÓN 🔥)
        // =================================================
        if ($action === 'add_users') {

            // 🔍 VALIDAR SI YA EXISTE ALGÚN USUARIO
            $stmtCheck = $pdo->prepare("
                SELECT COUNT(*)
                FROM \"campaña_usuarios\"
                WHERE id_campaña = :id
                AND id_user = :user
            ");

            foreach ($usuarios as $idUser) {
                $stmtCheck->bindValue(':id', $idCampaña, PDO::PARAM_INT);
                $stmtCheck->bindValue(':user', (int)$idUser, PDO::PARAM_INT);
                $stmtCheck->execute();

                if ((int)$stmtCheck->fetchColumn() > 0) {
                    $pdo->rollBack();
                    http_response_code(409);
                    echo json_encode([
                        "error" => "El usuario ya existe en la lista"
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }
            }

            $stmt = $pdo->prepare("
                UPDATE campañas
                SET dirigido_todos = false
                WHERE id_campaña = :id_upd
            ");
            $stmt->bindValue(':id_upd', $idCampaña, PDO::PARAM_INT);
            $stmt->execute();

            $stmtIns = $pdo->prepare("
                INSERT INTO \"campaña_usuarios\" (id_campaña, id_user)
                VALUES (:id_ins, :user_ins)
            ");

            foreach ($usuarios as $idUser) {
                $stmtIns->bindValue(':id_ins', $idCampaña, PDO::PARAM_INT);
                $stmtIns->bindValue(':user_ins', (int)$idUser, PDO::PARAM_INT);
                $stmtIns->execute();
            }
        }

        // =================================================
        // REMOVE USERS
        // =================================================
        if ($action === 'remove_users') {

            $stmtDel = $pdo->prepare("
                DELETE FROM \"campaña_usuarios\"
                WHERE id_campaña = :id_del
                AND id_user = :user_del
            ");

            foreach ($usuarios as $idUser) {
                $stmtDel->bindValue(':id_del', $idCampaña, PDO::PARAM_INT);
                $stmtDel->bindValue(':user_del', (int)$idUser, PDO::PARAM_INT);
                $stmtDel->execute();
            }
        }

        // =================================================
        // VALIDACIÓN FINAL
        // =================================================
        $stmtCount = $pdo->prepare("
            SELECT COUNT(*)
            FROM \"campaña_usuarios\"
            WHERE id_campaña = :id
        ");
        $stmtCount->bindValue(':id', $idCampaña, PDO::PARAM_INT);
        $stmtCount->execute();

        $totalUsuarios = (int)$stmtCount->fetchColumn();

        $stmtFinal = $pdo->prepare("
            UPDATE campañas
            SET dirigido_todos = :dirigido
            WHERE id_campaña = :id
        ");
        $stmtFinal->bindValue(':dirigido', $totalUsuarios === 0, PDO::PARAM_BOOL);
        $stmtFinal->bindValue(':id', $idCampaña, PDO::PARAM_INT);
        $stmtFinal->execute();

        $pdo->commit();

        echo json_encode([
            "status"           => "ok",
            "dirigido_todos"   => $totalUsuarios === 0,
            "usuarios_totales" => $totalUsuarios
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(405);
    echo json_encode(["error" => "Método no permitido"], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        "error"   => "Error interno del servidor",
        "detalle" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
