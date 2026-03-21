<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/mail.php';
require_once __DIR__ . '/Install.php';

class Mailer {
    private PHPMailer $mail;
    private bool $configured;

    public function __construct() {
        $this->mail = new PHPMailer(true);
        $this->configured = self::isConfigured();

        if (!$this->configured) {
            return;
        }

        $this->mail->isSMTP();
        $this->mail->Host       = SMTP_HOST;
        $this->mail->SMTPAuth   = true;
        $this->mail->Username   = SMTP_USER;
        $this->mail->Password   = SMTP_PASS;
        $this->mail->SMTPSecure = SMTP_SECURE ?? 'tls';
        $this->mail->Port       = SMTP_PORT;
        $this->mail->Timeout    = 15;            // seconds
        $this->mail->SMTPKeepAlive = false;
        $this->mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $this->mail->isHTML(true);
        $this->mail->CharSet = 'UTF-8';
    }

    public static function isConfigured(): bool {
        return Install::isMailConfigured();
    }

    public function send(string $toEmail, string $toName, string $subject, string $htmlBody, string $plainText=''): bool {
        if (!$this->configured) {
            return false;
        }

        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($toEmail, $toName);
            $this->mail->Subject = $subject;
            $this->mail->Body    = $htmlBody;
            if ($plainText !== '') {
                $this->mail->AltBody = $plainText;
            } else {
                $this->mail->AltBody = strip_tags(str_replace(['<br>','<br/>','<br />'], "\n", $htmlBody));
            }
            return $this->mail->send();
        } catch (Exception $e) {
            error_log('Mailer Error: '.$this->mail->ErrorInfo);
            return false;
        }
    }
}
