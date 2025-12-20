<?php

require __DIR__ . '/../middleware/auth.php';
require __DIR__ . '/../config/database.php';

$user = $_REQUEST['user'];

$stmt = $pdo->prepare("
    SELECT id_user, email, nombre, fecha_creacion
    FROM users
    WHERE id_user = :id_user
");

$stmt->execute([
    'id_user' => $user['id_user']
]);

$profile = $stmt->fetch();

echo json_encode($profile);
