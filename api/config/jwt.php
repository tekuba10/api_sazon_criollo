<?php
// api/config/jwt.php

require_once __DIR__ . '/../utils/helpers.php';


define('JWT_SECRET', $_ENV['JWT_SECRET']);
define('JWT_EXPIRE', $_ENV['JWT_EXPIRE']); // segundos

function jwtDecode($token) {

    $parts = explode(".", $token);

    if (count($parts) !== 3) {
        throw new Exception("Estructura JWT inválida");
    }

    [$header, $payload, $signature] = $parts;

    // Verificar firma
    $validSignature = base64UrlEncode(
        hash_hmac(
            'sha256',
            "$header.$payload",
            JWT_SECRET,
            true
        )
    );

    if (!hash_equals($validSignature, $signature)) {
        throw new Exception("Firma inválida");
    }

    $decodedPayload = json_decode(base64UrlDecode($payload), true);

    if (!$decodedPayload) {
        throw new Exception("Payload no válido");
    }

    // Verificar expiración
    if (!isset($decodedPayload['exp']) || $decodedPayload['exp'] < time()) {
        throw new Exception("Token expirado");
    }

    return $decodedPayload;
}
