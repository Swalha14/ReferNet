<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class SendMail
{
    private PHPMailer $mail;
    private array $conf;

    public function __construct(array $conf)
    {
        $this->conf = $conf;
        $this->mail = new PHPMailer(true);

        // SMTP Configuration
        $this->mail->isSMTP();
        $this->mail->Host       = $conf['smtp_host'];
        $this->mail->SMTPAuth   = true;
        $this->mail->Username   = $conf['smtp_user'];
        $this->mail->Password   = $conf['smtp_pass'];
        $this->mail->SMTPSecure = $conf['smtp_secure'] === 'ssl'
                                    ? PHPMailer::ENCRYPTION_SMTPS
                                    : PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port       = $conf['smtp_port'];

        // Default sender
        $this->mail->setFrom($conf['smtp_user'], $conf['site_name']);
        $this->mail->isHTML(true);
    }

    /* ==============================================
       Send OTP Email
    =============================================== */
    public function sendOTP(string $toEmail, string $toName, string $otpCode): bool
    {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($toEmail, $toName);

            $this->mail->Subject = $this->conf['site_name'] . ' — Your Login OTP';
            $this->mail->Body    = $this->otpEmailBody($toName, $otpCode);
            $this->mail->AltBody = "Hello $toName, your ReferNet OTP is: $otpCode. "
                                 . "It expires in 2 minutes. Do not share it.";

            $this->mail->send();
            return true;

        } catch (Exception $e) {
            error_log('ReferNet SendMail Error: ' . $e->getMessage());
            return false;
        }
    }

    /* ==============================================
       Send Referral Notification Email
    =============================================== */
    public function sendReferralNotification(
        string $toEmail,
        string $toName,
        string $subject,
        string $messageBody
    ): bool {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($toEmail, $toName);

            $this->mail->Subject = $this->conf['site_name'] . ' - ' . $subject;
            $this->mail->Body    = $this->notificationEmailBody($toName, $subject, $messageBody);
            $this->mail->AltBody = strip_tags($messageBody);

            $this->mail->send();
            return true;

        } catch (Exception $e) {
            error_log('ReferNet SendMail Error: ' . $e->getMessage());
            return false;
        }
    }

    /* ==============================================
       OTP Email HTML Template
    =============================================== */
    private function otpEmailBody(string $name, string $otp): string
    {
        $siteName = htmlspecialchars($this->conf['site_name']);
        return "
        <div style='font-family:Arial,sans-serif;max-width:500px;margin:auto;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;'>

            <div style='background:#1d4ed8;padding:20px 30px;'>
                <h2 style='color:white;margin:0;'>$siteName</h2>
                <p style='color:#bfdbfe;margin:4px 0 0;font-size:13px;'>Hospital Referral Coordination System</p>
            </div>

            <div style='padding:30px;'>
                <p style='font-size:15px;'>Hello <strong>" . htmlspecialchars($name) . "</strong>,</p>
                <p style='font-size:14px;color:#374151;'>
                    Your one-time login code for <strong>$siteName</strong> is:
                </p>

                <div style='text-align:center;margin:25px 0;'>
                    <span style='
                        display:inline-block;
                        font-size:36px;
                        font-weight:bold;
                        letter-spacing:10px;
                        color:#1d4ed8;
                        background:#eff6ff;
                        padding:14px 30px;
                        border-radius:10px;
                        border:2px dashed #93c5fd;
                    '>$otp</span>
                </div>

                <p style='font-size:13px;color:#6b7280;text-align:center;'>
                    ⏱ This code expires in <strong>2 minutes</strong>.
                </p>
                <p style='font-size:13px;color:#ef4444;text-align:center;'>
                    Do not share this code with anyone.
                </p>
            </div>

            <div style='background:#f3f4f6;padding:14px 30px;text-align:center;'>
                <p style='font-size:12px;color:#9ca3af;margin:0;'>
                    &copy; " . date('Y') . " $siteName &nbsp;|&nbsp; This is an automated message.
                </p>
            </div>

        </div>";
    }

    /* ==============================================
       Referral Notification HTML Template
    =============================================== */
    private function notificationEmailBody(string $name, string $subject, string $body): string
    {
        $siteName = htmlspecialchars($this->conf['site_name']);
        $safeBody = nl2br(htmlspecialchars($body));
        return "
        <div style='font-family:Arial,sans-serif;max-width:560px;margin:auto;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;'>

            <div style='background:#1d4ed8;padding:20px 30px;'>
                <h2 style='color:white;margin:0;'>$siteName</h2>
                <p style='color:#bfdbfe;margin:4px 0 0;font-size:13px;'>Hospital Referral Coordination System</p>
            </div>

            <div style='padding:30px;'>
                <p style='font-size:15px;'>Hello <strong>" . htmlspecialchars($name) . "</strong>,</p>
                <h3 style='color:#1d4ed8;margin-bottom:10px;'>" . htmlspecialchars($subject) . "</h3>
                <p style='font-size:14px;color:#374151;line-height:1.6;'>$safeBody</p>

                <div style='text-align:center;margin-top:25px;'>
                    <a href='{$this->conf['site_url']}/dashboard.php'
                       style='background:#1d4ed8;color:white;padding:12px 24px;
                              border-radius:8px;text-decoration:none;font-weight:bold;font-size:14px;'>
                        View in ReferNet
                    </a>
                </div>
            </div>

            <div style='background:#f3f4f6;padding:14px 30px;text-align:center;'>
                <p style='font-size:12px;color:#9ca3af;margin:0;'>
                    &copy; " . date('Y') . " $siteName &nbsp;|&nbsp; This is an automated message.
                </p>
            </div>

        </div>";
    }
}
?>