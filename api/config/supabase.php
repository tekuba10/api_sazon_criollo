<?php
$SUPABASE_URL = getenv('SUPABASE_URL');
$SUPABASE_KEY = getenv('SUPABASE_KEY');

function supabaseUpload($bucket, $path, $fileTmpPath, $mimeType) {
    global $SUPABASE_URL, $SUPABASE_KEY;

    $url = $SUPABASE_URL . "/storage/v1/object/" . $bucket . "/" . $path;
    $fileData = file_get_contents($fileTmpPath);

    $headers = [
        "Authorization: Bearer " . $SUPABASE_KEY,
        "Content-Type: " . $mimeType,
        "x-upsert: false" // ⛔ NO sobrescribir
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fileData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status >= 200 && $status < 300) {
        return $SUPABASE_URL . "/storage/v1/object/public/" . $bucket . "/" . $path;
    }

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
