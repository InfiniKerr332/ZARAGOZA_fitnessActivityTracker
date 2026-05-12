<?php
/**
 * Mail Configuration - Fitness Tracker
 * Uses PHPMailer with SMTP
 * 
 * IMPORTANT: Update SMTP credentials below with your actual email settings.
 * For Gmail: Enable 2FA and create an App Password at https://myaccount.google.com/apppasswords
 */

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'kerrzaragoza43@gmail.com');
define('SMTP_PASSWORD', 'rlkflvaktngwdjbo');
define('SMTP_FROM_EMAIL', 'kerrzaragoza43@gmail.com');
define('SMTP_FROM_NAME', 'FitTrack Pro');
define('SMTP_ENCRYPTION', 'tls');

// Verification code settings
define('CODE_LENGTH', 6);
define('CODE_EXPIRY_MINUTES', 3);
define('CODE_RESEND_SECONDS', 60);
define('CODE_MAX_ATTEMPTS', 5);

// Set to true to show code on screen instead of sending email (for testing)
define('MAIL_DEBUG_MODE', false);

require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

/**
 * Send verification code email
 */
function sendVerificationEmail(string $toEmail, string $code, string $type = 'registration'): bool {
    // In debug mode, don't actually send - the code is shown on screen
    if (MAIL_DEBUG_MODE) {
        return true;
    }
    
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port       = SMTP_PORT;
        
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($toEmail);
        
        $mail->isHTML(true);
        
        $subject = $type === 'registration' 
            ? 'Verify Your FitTrack Pro Account' 
            : 'Reset Your FitTrack Pro Password';
        
        $mail->Subject = $subject;
        $mail->Body = getEmailTemplate($code, $type);
        $mail->AltBody = "Your FitTrack Pro verification code is: $code. This code expires in " . CODE_EXPIRY_MINUTES . " minutes.";
        
        $mail->send();
        return true;
    } catch (MailException $e) {
        error_log("Mail sending failed: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Generate HTML email template
 */
function getEmailTemplate(string $code, string $type): string {
    $title = $type === 'registration' ? 'Verify Your Account' : 'Reset Your Password';
    $message = $type === 'registration' 
        ? 'Welcome to FitTrack Pro! Use the code below to verify your email address.'
        : 'Use the code below to reset your password.';
    
    return '
    <!DOCTYPE html>
    <html>
    <head><meta charset="UTF-8"></head>
    <body style="margin:0;padding:0;background:#191918;font-family:Inter,Arial,sans-serif;">
        <div style="max-width:500px;margin:40px auto;background:#1e1e1e;border-radius:16px;overflow:hidden;border:1px solid rgba(255,255,255,0.06);">
            <div style="background:linear-gradient(135deg,#02093A,#455DD3);padding:40px 30px;text-align:center;">
                <h1 style="color:#fff;margin:0;font-size:24px;font-weight:700;">FitTrack Pro</h1>
                <p style="color:rgba(255,255,255,0.8);margin:8px 0 0;font-size:14px;">' . $title . '</p>
            </div>
            <div style="padding:40px 30px;text-align:center;">
                <p style="color:#a0a0a0;font-size:15px;line-height:1.6;margin:0 0 30px;">' . $message . '</p>
                <div style="background:#191918;border-radius:12px;padding:24px;margin:0 0 24px;border:1px solid rgba(69,93,211,0.3);">
                    <span style="font-size:36px;font-weight:800;letter-spacing:12px;color:#fff;">' . $code . '</span>
                </div>
                <p style="color:#666;font-size:13px;margin:0;">This code expires in <strong style="color:#0075DE;">' . CODE_EXPIRY_MINUTES . ' minutes</strong></p>
            </div>
            <div style="padding:20px 30px;background:rgba(0,0,0,0.2);text-align:center;">
                <p style="color:#555;font-size:12px;margin:0;">If you didn\'t request this code, you can safely ignore this email.</p>
            </div>
        </div>
    </body>
    </html>';
}
