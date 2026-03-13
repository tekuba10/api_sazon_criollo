<?php

require __DIR__ . '/../middleware/auth.php';

echo json_encode([
    "ok" => true,
    "user" => $GLOBALS['auth_user']
]);
