<?php
$pageTitle = 'Verify Email';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/mail.php';

$email = $_SESSION['verify_email'] ?? '';
$type = $_SESSION['verify_type'] ?? 'registration';
if (!$email) { redirect('/auth/register.php'); }

$error = '';
$debugCode = $_SESSION['debug_code'] ?? null;
$db = getDB();

$stmt = $db->prepare("SELECT TIMESTAMPDIFF(SECOND, NOW(), expires_at) as sec_left, TIMESTAMPDIFF(SECOND, NOW(), DATE_ADD(created_at, INTERVAL ? SECOND)) as resend_wait FROM verification_codes WHERE email = ? AND type = ? AND verified_at IS NULL ORDER BY created_at DESC LIMIT 1");
$stmt->execute([CODE_RESEND_SECONDS, $email, $type]);
$timers = $stmt->fetch();
$secondsLeft = max(0, (int)($timers['sec_left'] ?? (CODE_EXPIRY_MINUTES * 60)));
$resendLeft = max(0, (int)($timers['resend_wait'] ?? 0));

// Handle resend
if (isset($_POST['resend'])) {
    if (verifyCSRF()) {
        $stmt = $db->prepare("SELECT TIMESTAMPDIFF(SECOND, created_at, NOW()) as seconds_ago FROM verification_codes WHERE email = ? AND type = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$email, $type]);
        $last = $stmt->fetch();
        if ($last && $last['seconds_ago'] >= 0 && $last['seconds_ago'] < CODE_RESEND_SECONDS) {
            $error = 'Please wait ' . (CODE_RESEND_SECONDS - $last['seconds_ago']) . ' seconds before resending.';
        } else {
            $code = generateCode();
            if ($type === 'registration') {
                $stmt = $db->prepare("SELECT payload FROM verification_codes WHERE email = ? AND type = 'registration' ORDER BY created_at DESC LIMIT 1");
                $stmt->execute([$email]);
                $prev = $stmt->fetch();
                $payload = $prev['payload'] ?? null;
            } else { $payload = null; }
            $stmt = $db->prepare("INSERT INTO verification_codes (email, code, type, payload, expires_at, created_at) VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), NOW())");
            $stmt->execute([$email, $code, $type, $payload, CODE_EXPIRY_MINUTES]);
            sendVerificationEmail($email, $code, $type);
            if (MAIL_DEBUG_MODE) { $_SESSION['debug_code'] = $code; $debugCode = $code; }
            setFlash('success', 'New verification code sent!');
            redirect('/auth/verify.php');
        }
    }
}

// Handle code verification
if (isset($_POST['verify'])) {
    if (!verifyCSRF()) { $error = 'Invalid request.'; }
    else {
        $code = trim($_POST['verification_code'] ?? '');
        if (strlen($code) !== 6) { $error = 'Please enter the full 6-digit code.'; }
        else {
            // Find valid code (subquery to get latest unexpired, unverified code)
            $stmt = $db->prepare("SELECT * FROM verification_codes WHERE email = ? AND type = ? AND code = ? AND verified_at IS NULL AND expires_at > NOW() AND attempts < ? ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$email, $type, $code, CODE_MAX_ATTEMPTS]);
            $record = $stmt->fetch();
            if ($record) {
                // Mark as verified
                $db->prepare("UPDATE verification_codes SET verified_at = NOW() WHERE id = ?")->execute([$record['id']]);
                if ($type === 'registration') {
                    $data = json_decode($record['payload'], true);
                    // Create user account
                    $stmt = $db->prepare("INSERT INTO users (username, email, email_verified_at, password_hash, first_name, middle_name, last_name, date_of_birth, height_cm, weight_kg, role, created_at, updated_at) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, 'user', NOW(), NOW())");
                    $stmt->execute([$data['username'], $data['email'], $data['password_hash'], $data['first_name'], $data['middle_name'] ?? null, $data['last_name'], $data['date_of_birth'] ?? null, $data['height_cm'] ?? null, $data['weight_kg'] ?? null]);
                    $userId = $db->lastInsertId();
                    logAction($userId, 'register', 'New user registered');
                    // Auto login
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['role'] = 'user';
                    $_SESSION['username'] = $data['username'];
                    unset($_SESSION['verify_email'], $_SESSION['verify_type'], $_SESSION['debug_code']);
                    setFlash('success', 'Account created! Welcome to FitTrack Pro!');
                    redirect('/user/dashboard.php');
                } else {
                    $_SESSION['reset_email'] = $email;
                    unset($_SESSION['verify_email'], $_SESSION['verify_type'], $_SESSION['debug_code']);
                    redirect('/auth/reset-password.php');
                }
            } else {
                // Increment attempts on latest code
                $db->prepare("UPDATE verification_codes SET attempts = attempts + 1 WHERE email = ? AND type = ? AND verified_at IS NULL ORDER BY created_at DESC LIMIT 1")->execute([$email, $type]);
                $error = 'Invalid or expired code. Please try again.';
            }
        }
    }
}
?>
<div class="auth-page">
    <div class="auth-card animate-in">
        <div class="auth-header">
            <div class="logo-icon"><i data-lucide="mail"></i></div>
            <h1>Verify Your Email</h1>
            <p>Enter the 6-digit code sent to <strong><?= sanitize($email) ?></strong></p>
        </div>
        <?php if ($error): ?><div class="flash-message flash-error"><i data-lucide="alert-circle"></i><span><?= $error ?></span></div><?php endif; ?>
        <?= displayFlashMessages() ?>
        <?php if ($debugCode && MAIL_DEBUG_MODE): ?>
        <div style="background:rgba(0,117,222,0.1); border:1px solid #0075DE; border-radius:8px; padding:12px; margin-bottom:20px; text-align:center;">
            <p style="font-size:12px; color:var(--text-secondary); margin-bottom:8px;"><i data-lucide="info" style="width:14px;height:14px;display:inline;vertical-align:middle;"></i> <strong>SMTP Not Configured</strong>. Email simulation mode is active.</p>
            <p style="font-size:14px;">Your verification code is: <strong style="font-size:18px; letter-spacing:2px; color:var(--accent);"><?= $debugCode ?></strong></p>
        </div>
        <?php endif; ?>
        <form method="POST" id="verifyForm">
            <?= csrfField() ?>
            <input type="hidden" name="verification_code" id="verification_code">
            <div class="code-inputs">
                <input type="text" class="code-input" maxlength="1" inputmode="numeric" autofocus>
                <input type="text" class="code-input" maxlength="1" inputmode="numeric">
                <input type="text" class="code-input" maxlength="1" inputmode="numeric">
                <input type="text" class="code-input" maxlength="1" inputmode="numeric">
                <input type="text" class="code-input" maxlength="1" inputmode="numeric">
                <input type="text" class="code-input" maxlength="1" inputmode="numeric">
            </div>
            <p class="form-text text-center mb-2" id="expiryTimer" style="font-weight:600;"></p>
            <button type="submit" name="verify" class="btn btn-primary btn-block btn-lg">Verify Code</button>
        </form>
        <form method="POST" class="mt-3 text-center">
            <?= csrfField() ?>
            <div class="resend-timer" id="resendTimer"></div>
            <button type="submit" name="resend" id="resendBtn" class="resend-btn">Resend Code</button>
        </form>
        <div class="auth-footer">
            <a href="<?= BASE_URL ?>/auth/register.php">← Back to Register</a>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    initCodeInputs();
    if (<?= $secondsLeft ?> > 0) startExpiryTimer(<?= $secondsLeft ?>);
    else document.getElementById('expiryTimer').textContent = 'Code expired';
    
    if (<?= $resendLeft ?> > 0) startResendTimer(<?= $resendLeft ?>);
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
