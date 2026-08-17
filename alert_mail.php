<?php
/** Sends approved-area notifications only when SMTP and an alert recipient are configured. */
function send_location_alert_email(mysqli $conn, int $assetId, string $message): bool
{
    $settings = config();
    if (empty($settings['mail_host']) || empty($settings['mail_username']) || empty($settings['mail_password']) || empty($settings['admin_alert_email'])) return false;
    require_once __DIR__ . '/PHPMailer-master/src/Exception.php';
    require_once __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer-master/src/SMTP.php';
    $asset = $conn->prepare('SELECT asset_tag FROM laptops WHERE id=?'); $asset->bind_param('i', $assetId); $asset->execute();
    $row = $asset->get_result()->fetch_assoc();
    if (!$row) return false;
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP(); $mail->Host = $settings['mail_host']; $mail->SMTPAuth = true;
        $mail->Username = $settings['mail_username']; $mail->Password = $settings['mail_password'];
        $mail->Port = (int) ($settings['mail_port'] ?? 587); $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->setFrom($settings['mail_from'] ?: $settings['mail_username'], 'IRA Asset Management');
        $mail->addAddress($settings['admin_alert_email']);
        $mail->Subject = 'Approved-area alert: ' . $row['asset_tag'];
        $mail->Body = "Asset {$row['asset_tag']}: {$message}"; $mail->send();
        return true;
    } catch (Throwable) { return false; }
}
