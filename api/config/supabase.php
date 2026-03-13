<?php
$SUPABASE_URL = getenv('SUPABASE_URL');
$SUPABASE_KEY = getenv('SUPABASE_KEY');

function supabaseUpload($bucket, $path, $fileTmpPath, $mimeType) {
    global $SUPABASE_URL, $SUPABASE_KEY;

    $url = $SUPABASE_URL . "/storage/v1/object/" . $bucket . "/" . $path;
    $fileData = file_get_contents($fileTmpPath);

    $headers = [
        "Authorization: Bearer " . $SUPABASE_KEY,
        "apikey: " . $SUPABASE_KEY, 
        "Content-Type: " . $mimeType,
        "x-upsert: false"
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $fileData,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true
    ]);

    $response = curl_exec($ch);
    $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($status >= 200 && $status < 300) {
        return $SUPABASE_URL . "/storage/v1/object/public/" . $bucket . "/" . $path;
    }

    // 🔎 DEBUG TEMPORAL (puedes quitarlo luego)
    error_log("Supabase error ($status): $response");

    return false;
}


function supabaseDelete($bucket, $path) {
    global $SUPABASE_URL, $SUPABASE_KEY;

    $url = $SUPABASE_URL . "/storage/v1/object/" . $bucket . "/" . $path;
    $headers = [
        "Authorization: Bearer " . $SUPABASE_KEY,
        "apikey: " . $SUPABASE_KEY
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}


function supabaseFileExists(string $bucket, string $path): bool
{
    global $SUPABASE_URL, $SUPABASE_KEY;

    $url = $SUPABASE_URL . "/storage/v1/object/info/$bucket/$path";

    $headers = [
        "Authorization: Bearer " . $SUPABASE_KEY,
        "apikey: " . $SUPABASE_KEY
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CUSTOMREQUEST => "GET"
    ]);

    curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // 200 = existe | 404 = no existe
    return $status === 200;
}

function supabaseCreateSignedUrl($bucket, $path, $expires = 3600) {
    global $SUPABASE_URL, $SUPABASE_KEY;

    $url = rtrim($SUPABASE_URL, '/') . "/storage/v1/object/sign/$bucket/$path";

    $payload = json_encode([
        "expiresIn" => $expires
    ]);

    $headers = [
        "Authorization: Bearer " . $SUPABASE_KEY,
        "apikey: " . $SUPABASE_KEY,
        "Content-Type: application/json"
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    if (!isset($data['signedURL'])) {
        return null;
    }

    // 👇 ESTA es la línea clave
    return rtrim($SUPABASE_URL, '/') . "/storage/v1" . $data['signedURL'];
}
