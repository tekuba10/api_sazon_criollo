<?php
// Helpers de respuesta JSON para el MVP

function respondSuccess($data = []) {
    http_response_code(200);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function respondError($code, $message) {
    http_response_code($code);
    echo json_encode(["error" => $message], JSON_UNESCAPED_UNICODE);
    exit;
}
