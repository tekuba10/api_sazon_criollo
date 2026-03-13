<?php

require __DIR__ . '/../middleware/auth.php';
require __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$user = $GLOBALS['auth_user'] ?? null;
$idUser = $user['id_user'] ?? null;

if (!$idUser) {
    http_response_code(401);
    echo json_encode([
        "error" => "Usuario no autenticado"
    ]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

/*
|--------------------------------------------------------------------------
| GET → Obtener dashboard (recommended + campaigns)
|--------------------------------------------------------------------------
*/
if ($method === 'GET') {

    try {

        /*
        |--------------------------------------------------------------------------
        | 1️⃣ Obtener Recommended
        |--------------------------------------------------------------------------
        */
        $stmtRecommended = $pdo->prepare("
            SELECT
                id_recomendado,
                titulo,
                url,
                poster,
                fecha_creacion
            FROM public.recomendado
            ORDER BY fecha_creacion DESC
        ");

        $stmtRecommended->execute();
        $recommended = $stmtRecommended->fetchAll(PDO::FETCH_ASSOC);


        /*
        |--------------------------------------------------------------------------
        | 2️⃣ Obtener Campaigns activas y visibles para el usuario
        |--------------------------------------------------------------------------
        */
        $stmtCampaigns = $pdo->prepare("
            SELECT DISTINCT
                c.id_campaña,
                c.titulo,
                c.url_etsy,
                c.banner_escritorio,
                c.banner_tablet,
                c.banner_movil,
                c.fecha_inicio,
                c.fecha_final
            FROM campañas c
            LEFT JOIN campaña_usuarios cu
                ON cu.id_campaña = c.id_campaña
            WHERE
                c.activa = true
                AND c.fecha_inicio <= NOW()
                AND c.fecha_final >= NOW()
                AND (
                    c.dirigido_todos = true
                    OR cu.id_user = :id_user
                )
            ORDER BY c.fecha_inicio DESC
        ");

        $stmtCampaigns->execute([
            'id_user' => $idUser
        ]);

        $campaigns = $stmtCampaigns->fetchAll(PDO::FETCH_ASSOC);


        /*
        |--------------------------------------------------------------------------
        | Respuesta final
        |--------------------------------------------------------------------------
        */
        echo json_encode([
            "status" => "ok",
            "recommended" => $recommended,
            "campaigns" => $campaigns
        ], JSON_UNESCAPED_UNICODE);

        exit;

    } catch (PDOException $e) {

        http_response_code(500);
        echo json_encode([
            "error" => "Error al obtener el dashboard"
        ]);

        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Método no permitido
|--------------------------------------------------------------------------
*/
http_response_code(405);
echo json_encode([
    "error" => "Método no permitido"
]);
