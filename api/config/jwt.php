<?php
// api/config/jwt.php

define('JWT_SECRET', $_ENV['JWT_SECRET']);
define('JWT_EXPIRE', $_ENV['JWT_EXPIRE']); // segundos

function jwtDecode($token) {
    $parts = explode(".", $token);
    if (count($parts) !== 3) {
        throw new Exception("Estructura JWT inválida");
    }

    $payload = json_decode(base64_decode($parts[1]));
    if (!$payload) {
        throw new Exception("Payload no válido");
    }

    return $payload;
}