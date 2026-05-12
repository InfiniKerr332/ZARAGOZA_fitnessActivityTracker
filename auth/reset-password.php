<?php
$pageTitle = 'Set New Password';
require_once __DIR__ . '/../includes/header.php';
if (isLoggedIn()) { redirect('/user/dashboard.php'); }

$email = $_SESSION['reset_email'] ?? '';
if (!$email) {
    redirect('/auth/login.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF()) { $error = 'Invalid request.'; }
    else {
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        
        if (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $db = getDB();
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE email = ?");
            $stmt->execute([$hash, $email]);
            
            // Log the action if we can find the user ID
            $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user) {
                logAction($user['user_id'], 'password_reset', 'User reset their password via email verification');
            }
            
            unset($_SESSION['reset_email']);
            setFlash('success', 'Your password has been successfully reset! Please log in.');
            redirect('/auth/login.php');
        }
    }
}
?>
<div class="auth-page">
    <div class="auth-card animate-in">
        <div class="auth-header">
            <div class="logo-icon"><i data-lucide="lock"></i></div>
            <h1>Set New Password</h1>
            <p>Create a new password for <strong><?= sanitize($email) ?></strong></p>
        </div>
        <?php if ($error): ?><div class="flash-message flash-error"><i data-lucide="alert-circle"></i><span><?= $error ?></span></div><?php endif; ?>
        <form method="POST" id="resetForm">
            <?= csrfField() ?>
            <div class="form-group">
                <label class="form-label" for="password">New Password <span class="required-asterisk">*</span></label>
                <input type="password" id="password" name="password" class="form-control" required minlength="6" autofocus>
            </div>
            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm New Password <span class="required-asterisk">*</span></label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="6">
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg">Reset Password</button>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
