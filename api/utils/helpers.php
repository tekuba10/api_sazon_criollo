<?php

function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64UrlDecode($data) {
    return base64_decode(strtr($data, '-_', '+/'));
}

function generateJWT(array $payload): string {

    $header = [
        'alg' => 'HS256',
        'typ' => 'JWT'
    ];

    $now = time();

    $payload['iat'] = $now; // issued at
    $payload['exp'] = $now + JWT_EXPIRE;
    $payload['jti'] = bin2hex(random_bytes(16)); // ID único del token

    $base64Header  = base64UrlEncode(json_encode($header));
    $base64Payload = base64UrlEncode(json_encode($payload));

    $signature = hash_hmac(
        'sha256',
        $base64Header . '.' . $base64Payload,
        JWT_SECRET,
        true
    );

    $base64Signature = base64UrlEncode($signature);

    return $base64Header . '.' . $base64Payload . '.' . $base64Signature;
}


function validateJWT(string $jwt, PDO $pdo) {

    try {

        $payload = jwtDecode($jwt);

        // Buscar token_version actual en la base de datos
        $stmt = $pdo->prepare("
            SELECT token_version 
            FROM users 
            WHERE id_user = :id_user
        ");

        $stmt->execute([
            'id_user' => $payload['id_user']
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return false;
        }

        // Si la versión no coincide → token inválido
        if ($user['token_version'] != $payload['token_version']) {
            return false;
        }

        return $payload;

    } catch (Exception $e) {
        return false;
    }
}


