<?php
// mailer.php — función send_mail($to,$subject,$html)
// Requiere PHPMailer. Si usas Composer: composer require phpmailer/phpmailer
// Luego: require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Si NO usas Composer, descomenta y ajusta rutas:
// require __DIR__.'/vendor/phpmailer/phpmailer/src/Exception.php';
// require __DIR__.'/vendor/phpmailer/phpmailer/src/PHPMailer.php';
// require __DIR__.'/vendor/phpmailer/phpmailer/src/SMTP.php';

function send_mail($to, $subject, $html){
  // TODO: ajusta tus credenciales SMTP
  $SMTP_HOST = getenv('SMTP_HOST') ?: 'smtp.tu-dominio.com';
  $SMTP_USER = getenv('SMTP_USER') ?: 'notificaciones@tu-dominio.com';
  $SMTP_PASS = getenv('SMTP_PASS') ?: 'password';
  $SMTP_PORT = getenv('SMTP_PORT') ?: 587;
  $SMTP_SECURE = getenv('SMTP_SECURE') ?: PHPMailer::ENCRYPTION_STARTTLS;
  $FROM_EMAIL = getenv('FROM_EMAIL') ?: 'notificaciones@tu-dominio.com';
  $FROM_NAME  = getenv('FROM_NAME') ?: 'Mi Agenda';

  $mail = new PHPMailer(true);
  try {
    $mail->isSMTP();
    $mail->Host       = $SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = $SMTP_USER;
    $mail->Password   = $SMTP_PASS;
    $mail->SMTPSecure = $SMTP_SECURE;
    $mail->Port       = $SMTP_PORT;

    $mail->setFrom($FROM_EMAIL, $FROM_NAME);
    $mail->addAddress($to);

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $html;

    $mail->send();
    return [true, null];
  } catch (Exception $e) {
    return [false, $mail->ErrorInfo];
  }
}
