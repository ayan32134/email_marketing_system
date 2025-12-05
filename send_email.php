<?php
require_once "config/Database.php";
require_once "classes/Member.php";
require_once "classes/SMTPSetting.php";
require_once "classes/EmailLog.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Try Composer autoload first
$composerAutoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
} else {
    // Fallback: manual PHPMailer include from classes/phpmailer/src
    $phpmailerBase = __DIR__ . '/classes/phpmailer/src';
    $phpMailerFile = $phpmailerBase . '/PHPMailer.php';
    $smtpFile      = $phpmailerBase . '/SMTP.php';
    $exceptionFile = $phpmailerBase . '/Exception.php';

    if (file_exists($phpMailerFile) && file_exists($smtpFile) && file_exists($exceptionFile)) {
        require_once $exceptionFile;
        require_once $smtpFile;
        require_once $phpMailerFile;
    } else {
        die("PHPMailer library not found. Please either install via Composer (composer require phpmailer/phpmailer) or copy PHPMailer sources into classes/phpmailer/src.");
    }
}

$db = (new Database())->connect();
$member_id = 1;

$smtp = new SMTPSetting($db);
$smtpConfig = $smtp->getSMTPByMember($member_id);

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = $smtpConfig["host"];
    $mail->Port = $smtpConfig["port"];
    $mail->SMTPAuth = true;
    $mail->Username = $smtpConfig["username"];
    $mail->Password = $smtpConfig["password"];
    $mail->SMTPSecure = $smtpConfig["encryption"];

    $mail->setFrom($smtpConfig["username"], "Your Company");
    $mail->addAddress("recipient@example.com", "Customer Name");
    $mail->Subject = "Welcome!";
    $mail->Body = "Hello, thank you for joining.";

    $mail->send();

    $log = new EmailLog($db);
    $log->logEmail([
        "campaign_id" => 1,
        "contact_id" => 123,
        "template_id" => 1,
        "group_id" => 5,
        "status" => "sent"
    ]);

    echo "Email sent successfully!";
} catch (Exception $e) {
    echo "Mailer Error: {$mail->ErrorInfo}";
}
?>
