<?php
$pageTitle = 'Forgot Password';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/mail.php';
if (isLoggedIn()) { redirect('/user/dashboard.php'); }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF()) { $error = 'Invalid request.'; }
    else {
        $email = trim($_POST['email'] ?? '');
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $db = getDB();
            $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ? AND deleted_at IS NULL");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                // Check resend cooldown entirely in MySQL
                $stmt = $db->prepare("SELECT TIMESTAMPDIFF(SECOND, created_at, NOW()) as seconds_ago FROM verification_codes WHERE email = ? AND type = 'password_reset' ORDER BY created_at DESC LIMIT 1");
                $stmt->execute([$email]);
                $lastCode = $stmt->fetch();
                if ($lastCode && $lastCode['seconds_ago'] >= 0 && $lastCode['seconds_ago'] < CODE_RESEND_SECONDS) {
                    $wait = CODE_RESEND_SECONDS - $lastCode['seconds_ago'];
                    $error = "Please wait $wait seconds before requesting another code.";
                } else {
                    $code = generateCode();
                    $stmt = $db->prepare("INSERT INTO verification_codes (email, code, type, expires_at, created_at) VALUES (?, ?, 'password_reset', DATE_ADD(NOW(), INTERVAL ? MINUTE), NOW())");
                    $stmt->execute([$email, $code, CODE_EXPIRY_MINUTES]);
                    
                    $sent = sendVerificationEmail($email, $code, 'password_reset');
                    if ($sent || MAIL_DEBUG_MODE) {
                        $_SESSION['verify_email'] = $email;
                        $_SESSION['verify_type'] = 'password_reset';
                        if (MAIL_DEBUG_MODE) { $_SESSION['debug_code'] = $code; }
                        redirect('/auth/verify.php');
                    } else {
                        $error = 'Failed to send verification email. Please try again.';
                    }
                }
            } else {
                // For security, do not reveal if email exists or not, just redirect to verify
                // but in this demo, let's just show a vague success to not leak emails.
                // However, without sending an email, verify page will fail. 
                // We'll redirect to verify but fake it.
                $_SESSION['verify_email'] = $email;
                $_SESSION['verify_type'] = 'password_reset';
                redirect('/auth/verify.php');
            }
        }
    }
}
?>
<div class="auth-page">
    <div class="auth-card animate-in">
        <div class="auth-header">
            <div class="logo-icon"><i data-lucide="key"></i></div>
            <h1>Reset Password</h1>
            <p>Enter your email to receive a verification code</p>
        </div>
        <?php if ($error): ?><div class="flash-message flash-error"><i data-lucide="alert-circle"></i><span><?= $error ?></span></div><?php endif; ?>
        <form method="POST" id="forgotForm">
            <?= csrfField() ?>
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" required autofocus>
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg">Send Reset Code</button>
        </form>
        <div class="auth-footer">
            Remembered your password? <a href="<?= BASE_URL ?>/auth/login.php">Sign In</a>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
