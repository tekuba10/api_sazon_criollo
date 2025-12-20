<?php
// api/config/admin.php

if (!isset($_ENV['ADMIN_KEY'])) {
    http_response_code(500);
    echo json_encode(['error' => 'ADMIN_KEY no definida']);
    exit;
}

define('ADMIN_KEY', $_ENV['ADMIN_KEY']);
