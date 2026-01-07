<?php
// Cargar variables de Supabase
if (!defined('SUPABASE_URL')) {
    $supabaseUrl = getenv('SUPABASE_URL');
    if (!$supabaseUrl) {
        http_response_code(500);
        echo json_encode(['error' => 'SUPABASE_URL no definida']);
        exit;
    }
    define('SUPABASE_URL', $supabaseUrl);
}

if (!defined('SUPABASE_KEY')) {
    $supabaseKey = getenv('SUPABASE_KEY');
    if (!$supabaseKey) {
        http_response_code(500);
        echo json_encode(['error' => 'SUPABASE_KEY no definida']);
        exit;
    }
    define('SUPABASE_KEY', $supabaseKey);
}

// Cargar ADMIN_KEY solo si no está definida
if (!defined('ADMIN_KEY')) {
    $adminKey = getenv('ADMIN_KEY');
    if (!$adminKey) {
        http_response_code(500);
        echo json_encode(['error' => 'ADMIN_KEY no definida']);
        exit;
    }
    define('ADMIN_KEY', $adminKey);
}
