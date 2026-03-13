<?php

require __DIR__ . '/../config/database.php';
require __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido"]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

/* =====================================================
   🔐 MODO 2: CAMBIAR CONTRASEÑA (PRIMERO)
   ===================================================== */
if (!empty($input['token']) && !empty($input['password_nueva'])) {

    $token = trim($input['token']);
    $passwordNueva = $input['password_nueva'];

    if (
        strlen($passwordNueva) < 6 ||
        !preg_match('/[A-Z]/', $passwordNueva) ||
        !preg_match('/[0-9]/', $passwordNueva) ||
        !preg_match('/[\W_]/', $passwordNueva)
    ) {
        http_response_code(400);
        echo json_encode([
            "error" => "La contraseña debe tener al menos 6 caracteres, una mayúscula, un número y un carácter especial"
        ]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT id, user_id, token
            FROM password_resets
            WHERE expires_at > NOW()
        ");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $resetRow = null;
        foreach ($rows as $row) {
            if (password_verify($token, $row['token'])) {
                $resetRow = $row;
                break;
            }
        }

        if (!$resetRow) {
            http_response_code(400);
            echo json_encode(["error" => "Token inválido o expirado"]);
            exit;
        }

        $newHash = password_hash($passwordNueva, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            UPDATE users
            SET password_hash = :password_hash
            WHERE id_user = :id_user
        ");
        $stmt->execute([
            'password_hash' => $newHash,
            'id_user' => $resetRow['user_id']
        ]);

        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE id = :id");
        $stmt->execute(['id' => $resetRow['id']]);

        echo json_encode([
            "success" => true,
            "message" => "Contraseña actualizada correctamente"
        ]);
        exit;

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Error interno"]);
        exit;
    }
}

/* =====================================================
   🧭 MODO 1: ENVIAR CORREO
   ===================================================== */
if (!empty($input['identifier'])) {

    $identifier = trim($input['identifier']);

    try {
        $stmt = $pdo->prepare("
            SELECT id_user, email, usuario, idioma
            FROM users
            WHERE email = :identifier
               OR usuario = :identifier
            LIMIT 1
        ");
        $stmt->execute(['identifier' => $identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo json_encode([
                "success" => true,
                "message" => "Si el usuario existe, se enviará un correo"
            ]);
            exit;
        }

        // 🌍 Idioma
        $idioma = strtolower($user['idioma'] ?? 'es');
        if (!in_array($idioma, ['es', 'en', 'pt'])) {
            $idioma = 'es';
        }

        // 🔐 Token
        $plainToken  = bin2hex(random_bytes(32));
        $hashedToken = password_hash($plainToken, PASSWORD_DEFAULT);
        $expiresAt   = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt = $pdo->prepare("
            INSERT INTO password_resets (user_id, token, expires_at)
            VALUES (:user_id, :token, :expires_at)
        ");
        $stmt->execute([
            'user_id' => $user['id_user'],
            'token' => $hashedToken,
            'expires_at' => $expiresAt
        ]);

        $resetLink = rtrim(getenv('FRONTEND_URL'), '/') . "/reset-password?token={$plainToken}";

        // ✉️ Email
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = getenv('SMTP_HOST');
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv('SMTP_USER');
        $mail->Password   = getenv('SMTP_PASS');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = getenv('SMTP_PORT');
        $mail->setFrom(getenv('SMTP_FROM') ?: getenv('SMTP_USER'), 'Sazón Criollo');
        $mail->addAddress($user['email']);
        $mail->isHTML(true);
        $mail->CharSet  = 'UTF-8';
        $mail->Encoding = 'base64';

        // Subject por idioma
        $mail->Subject = match ($idioma) {
            'en' => 'Reset your password – Sazón Criollo',
            'pt' => 'Redefinir senha – Sazón Criollo',
            default => 'Recupera tu contraseña – Sazón Criollo'
        };

        // HTML (MISMO ESTILO)
        $mail->Body = getResetEmailHtml(
            $idioma,
            $user['usuario'],
            $resetLink
        );

        $mail->send();

        echo json_encode([
            "success" => true,
            "message" => "Si el usuario existe, se enviará un correo"
        ]);
        exit;

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => "Error interno"]);
        exit;
    }
}

/* =====================================================
   ❌ REQUEST INVÁLIDO
   ===================================================== */
http_response_code(400);
echo json_encode(["error" => "Datos inválidos"]);

/* =====================================================
   🌍 HTML ORIGINAL CON TEXTOS DINÁMICOS
   ===================================================== */

function getResetEmailHtml(string $idioma, string $usuario, string $resetLink): string
{
    $t = getResetTexts($idioma);

    return '
<!DOCTYPE html>
<html lang="'.$idioma.'">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>'.$t['title'].'</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f4;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="padding:20px 0;">
<tr>
<td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;box-shadow:0 4px 10px rgba(0,0,0,0.08);">
<tr>
<td align="center" style="padding:35px;">
<img src="https://hjlxbalnmotkgnyfuyxu.supabase.co/storage/v1/object/public/iconos/Logo_Isotipo_Red-removebg-preview.png" width="140" alt="Sazón Criollo">
</td>
</tr>
<tr>
<td style="padding:20px 30px;color:#333;font-size:15px;line-height:1.6;">
<p>'.$t['hello'].' <strong>'.htmlspecialchars($usuario, ENT_QUOTES, 'UTF-8').'</strong>,</p>
<p>'.$t['request'].'</p>

<p style="text-align:center;margin:32px 0;">
<a href="'.$resetLink.'" style="background:#C62828;color:#fff;padding:15px 32px;border-radius:6px;text-decoration:none;font-weight:bold;">
'.$t['button'].'
</a>
</p>

<p>'.$t['expires'].'</p>
<p>'.$t['ignore'].'</p>

<p style="margin-top:32px;">—<br><strong>'.$t['team'].'</strong></p>
</td>
</tr>
<tr>
<td style="background:#fafafa;padding:15px;text-align:center;font-size:12px;color:#888;">
© '.date('Y').' Sazón Criollo · '.$t['rights'].'
</td>
</tr>
</table>
</td>
</tr>
</table>
</body>
</html>';
}

function getResetTexts(string $idioma): array
{
    return match ($idioma) {
        'en' => [
            'title' => 'Reset password',
            'hello' => 'Hello',
            'request' => 'We received a request to reset your password.',
            'button' => 'Create new password',
            'expires' => 'This link is valid for 1 hour.',
            'ignore' => 'If you didn’t request this change, you can safely ignore this email.',
            'team' => 'Sazón Criollo Team',
            'rights' => 'All rights reserved'
        ],
        'pt' => [
            'title' => 'Redefinir senha',
            'hello' => 'Olá',
            'request' => 'Recebemos uma solicitação para redefinir sua senha.',
            'button' => 'Criar nova senha',
            'expires' => 'Este link é válido por 1 hora.',
            'ignore' => 'Se você não solicitou esta alteração, ignore este e-mail.',
            'team' => 'Equipe Sazón Criollo',
            'rights' => 'Todos os direitos reservados'
        ],
        default => [
            'title' => 'Recuperar contraseña',
            'hello' => 'Hola',
            'request' => 'Recibimos una solicitud para restablecer tu contraseña.',
            'button' => 'Crear nueva contraseña',
            'expires' => 'Este enlace es válido por 1 hora.',
            'ignore' => 'Si tú no solicitaste este cambio, puedes ignorar este correo.',
            'team' => 'Equipo Sazón Criollo',
            'rights' => 'Todos los derechos reservados'
        ],
    };
}
