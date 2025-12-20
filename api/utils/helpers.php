<?php

function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64UrlDecode($data) {
    return base64_decode(strtr($data, '-_', '+/'));
}

function generateJWT(array $payload): string {
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];

    $payload['exp'] = time() + JWT_EXPIRE;

    $base64Header = base64UrlEncode(json_encode($header));
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

function validateJWT(string $jwt) {
    $parts = explode('.', $jwt);

    if (count($parts) !== 3) {
        return false;
    }

    [$header, $payload, $signature] = $parts;

    $validSignature = base64UrlEncode(
        hash_hmac(
            'sha256',
            "$header.$payload",
            JWT_SECRET,
            true
        )
    );

    if (!hash_equals($validSignature, $signature)) {
        return false;
    }

    $data = json_decode(base64UrlDecode($payload), true);

    if (!$data || $data['exp'] < time()) {
        return false;
    }

    return $data;
}
