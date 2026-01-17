<?php

require __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// =========================
// 📥 LEER JSON
// =========================
$data = json_decode(file_get_contents('php://input'), true);

// =========================
// 🔴 CAMPOS OBLIGATORIOS
// =========================
$token            = $data['token'] ?? null;
$email            = $data['email'] ?? null;
$password         = $data['password'] ?? null;
$nombre           = $data['nombre'] ?? null;
$apellido         = $data['apellido'] ?? null;
$usuario          = $data['usuario'] ?? null;
$fecha_nacimiento = $data['fecha_nacimiento'] ?? null;

// =========================
// 🟡 CAMPOS OPCIONALES
// =========================
$foto_perfil_raw  = $data['foto_perfil'] ?? null;
$idioma           = $data['idioma'] ?? 'es';
$marketing_raw    = $data['marketing_opt_in'] ?? false;

// =========================
// ✅ VALIDACIÓN BÁSICA
// =========================
if (
    !$token ||
    !$email ||
    !$password ||
    !$nombre ||
    !$apellido ||
    !$usuario ||
    !$fecha_nacimiento
) {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan campos obligatorios']);
    exit;
}

// =========================
// 🔐 NORMALIZACIÓN
// =========================
$email   = strtolower(trim($email));
$usuario = strtolower(trim($usuario));

// =========================
// 🔐 VALIDACIÓN DE USUARIO
// =========================
if (!preg_match('/^[a-zA-Z0-9._]{3,20}$/', $usuario)) {
    http_response_code(400);
    echo json_encode([
        'error' => 'El usuario solo puede contener letras, números, punto y guion bajo (3 a 20 caracteres)'
    ]);
    exit;
}

// =========================
// 🔐 FORZAR BOOLEAN
// =========================
$marketing_opt_in = filter_var(
    $marketing_raw,
    FILTER_VALIDATE_BOOLEAN,
    FILTER_NULL_ON_FAILURE
);
$marketing_opt_in = $marketing_opt_in ?? false;

// =========================
// 1️⃣ VALIDAR LINK
// =========================
$stmt = $pdo->prepare("
    SELECT id_link
    FROM registration_links
    WHERE token = :token
      AND used = false
      AND expires_at > NOW()
");
$stmt->execute(['token' => $token]);
$link = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$link) {
    http_response_code(400);
    echo json_encode(['error' => 'Link inválido o expirado']);
    exit;
}

// =========================
// 2️⃣ CREAR USUARIO
// =========================
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("
        INSERT INTO users (
            email,
            password_hash,
            nombre,
            apellido,
            usuario,
            foto_perfil,
            idioma,
            fecha_nacimiento,
            marketing_opt_in
        ) VALUES (
            :email,
            :password_hash,
            :nombre,
            :apellido,
            :usuario,
            COALESCE(:foto_perfil, 'default.png'),
            :idioma,
            :fecha_nacimiento,
            :marketing_opt_in
        )
    ");

    $stmt->bindValue(':email', $email);
    $stmt->bindValue(':password_hash', $passwordHash);
    $stmt->bindValue(':nombre', $nombre);
    $stmt->bindValue(':apellido', $apellido);
    $stmt->bindValue(':usuario', $usuario);
    $stmt->bindValue(':foto_perfil', $foto_perfil_raw);
    $stmt->bindValue(':idioma', $idioma);
    $stmt->bindValue(':fecha_nacimiento', $fecha_nacimiento);
    $stmt->bindValue(':marketing_opt_in', $marketing_opt_in, PDO::PARAM_BOOL);

    $stmt->execute();

} catch (PDOException $e) {
    if ($e->getCode() === '23505') {
        http_response_code(409);
        echo json_encode(['error' => 'Email o usuario ya existe']);
    } else {
        http_response_code(500);
        echo json_encode([
            'error'   => 'Error interno',
            'detalle' => $e->getMessage() // solo desarrollo
        ]);
    }
    exit;
}

// =========================
// 3️⃣ MARCAR LINK COMO USADO
// =========================
$stmt = $pdo->prepare("
    UPDATE registration_links
    SET used = true
    WHERE id_link = :id_link
");
$stmt->execute(['id_link' => $link['id_link']]);

// =========================
// ✅ RESPUESTA FINAL
// =========================
echo json_encode([
    'message' => 'Usuario registrado correctamente'
]);
