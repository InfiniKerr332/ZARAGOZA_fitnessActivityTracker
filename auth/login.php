<?php
$pageTitle = 'Log In';
require_once __DIR__ . '/../includes/header.php';
if (isLoggedIn()) { redirect(isAdmin() ? '/admin/dashboard.php' : '/user/dashboard.php'); }

$error = '';
$loginSuccess = false;
$redirectUrl = '';
$welcomeName = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF()) { $error = 'Invalid request.'; }
    else {
        $login = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';
        if (!$login || !$password) {
            $error = 'Please fill in all fields.';
        } else {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE (email = ? OR username = ?) AND deleted_at IS NULL");
            $stmt->execute([$login, $login]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password_hash'])) {
                // System owner and admins do not need email verification
                if (!$user['email_verified_at'] && $user['role'] !== 'admin' && $user['email'] !== 'admin@fittrack.com') {
                    $error = 'Please verify your email first.';
                } else {
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['username'] = $user['username'];
                    logAction($user['user_id'], 'login', 'User logged in');
                    
                    $welcomeName = htmlspecialchars($user['first_name'] ?: $user['username']);
                    $redirectUrl = BASE_URL . ($user['role'] === 'admin' ? '/admin/dashboard.php' : '/user/dashboard.php');
                    $loginSuccess = true;
                }
            } else {
                $error = 'Invalid email/username or password.';
            }
        }
    }
}
?>
<div class="auth-page">
    <div class="auth-card animate-in">
        <div class="auth-header">
            <div class="logo-icon" style="background:transparent; padding:0; width:80px; height:80px; margin:0 auto 20px; overflow:hidden;">
                <img src="<?= BASE_URL ?>/assets/image/logo21.png" alt="FitTrack Pro" style="width:100%; height:100%; object-fit:cover; border-radius:50%; display:block;">
            </div>
            <h1>Welcome Back</h1>
            <p>Sign in to your FitTrack Pro account</p>
        </div>
        <?php if ($error): ?><div class="flash-message flash-error"><i data-lucide="alert-circle"></i><span><?= $error ?></span></div><?php endif; ?>
        <form method="POST" id="loginForm">
            <?= csrfField() ?>
            <div class="form-group">
                <label class="form-label" for="login">Email Address</label>
                <input type="email" id="login" name="login" class="form-control" value="<?= sanitize($_POST['login'] ?? '') ?>" required autofocus placeholder="john.doe@example.com">
            </div>
            <div class="form-group">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                    <label class="form-label" for="password" style="margin-bottom:0;">Password</label>
                    <a href="<?= BASE_URL ?>/auth/forgot-password.php" style="font-size:12px; color:var(--accent); text-decoration:none;">Forgot Password?</a>
                </div>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg">Sign In</button>
        </form>
        <div class="auth-footer">
            Don't have an account? <a href="<?= BASE_URL ?>/auth/register.php">Sign Up</a>
        </div>
    </div>
</div>

<?php if ($loginSuccess): ?>
<style>
.success-modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(17, 24, 39, 0.7); backdrop-filter: blur(8px); display: flex; align-items: center; justify-content: center; z-index: 9999; animation: fadeIn 0.3s ease-out forwards; }
.success-modal { background: #fff; padding: 48px 40px; border-radius: 24px; text-align: center; max-width: 420px; width: 90%; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); transform: scale(0.9); opacity: 0; animation: popIn 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) 0.1s forwards; }
.success-icon-wrapper { width: 88px; height: 88px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; color: #fff; box-shadow: 0 0 0 12px rgba(16, 185, 129, 0.15); animation: pulseGreen 2s infinite; }
.success-icon-wrapper svg { width: 44px; height: 44px; }
.success-title { font-size: 26px; font-weight: 800; color: #111827; margin: 0 0 12px; letter-spacing: -0.5px; }
.success-subtitle { font-size: 16px; color: #6b7280; margin: 0 0 32px; line-height: 1.5; }
.loading-bar-container { width: 100%; height: 6px; background: #f3f4f6; border-radius: 3px; overflow: hidden; }
.loading-bar { width: 0%; height: 100%; background: linear-gradient(90deg, #455DD3, #60A5FA); animation: loadBar 1.5s ease-in-out forwards; }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes popIn { 0% { transform: scale(0.8) translateY(20px); opacity: 0; } 100% { transform: scale(1) translateY(0); opacity: 1; } }
@keyframes loadBar { 0% { width: 0%; } 100% { width: 100%; } }
@keyframes pulseGreen { 0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.3); } 70% { box-shadow: 0 0 0 20px rgba(16, 185, 129, 0); } 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); } }
</style>
<div class="success-modal-overlay">
    <div class="success-modal">
        <div class="success-icon-wrapper">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
        </div>
        <h2 class="success-title">Welcome Back, <?= $welcomeName ?>!</h2>
        <p class="success-subtitle">Authentication successful. Preparing your dashboard...</p>
        <div class="loading-bar-container">
            <div class="loading-bar"></div>
        </div>
    </div>
</div>
<script>
setTimeout(() => { window.location.href = '<?= $redirectUrl ?>'; }, 1500);
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
