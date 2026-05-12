<?php
$pageTitle = 'Register';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/mail.php';
if (isLoggedIn()) {
    redirect('/user/dashboard.php');
}

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF()) {
        $error = 'Invalid request.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $firstName = trim($_POST['first_name'] ?? '');
        $middleName = trim($_POST['middle_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $dob = $_POST['date_of_birth'] ?? '';
        $primaryGoal = $_POST['primary_goal'] ?? '';
        $unitPref = $_POST['unit_pref'] ?? '';
        $heightInput = $_POST['height'] ?? '';
        $weightInput = $_POST['weight'] ?? '';

        $heightCm = null;
        $weightKg = null;

        if ($heightInput !== '') {
            $heightCm = $unitPref === 'imperial' ? (float) $heightInput * 2.54 : (float) $heightInput; // Inches to cm
        }
        if ($weightInput !== '') {
            $weightKg = $unitPref === 'imperial' ? (float) $weightInput * 0.453592 : (float) $weightInput; // lbs to kg
        }

        if (!$email || !$password || !$firstName || !$lastName || !$dob || !$primaryGoal || !$unitPref || $heightInput === '' || $weightInput === '') {
            $error = 'Please fill in all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $db = getDB();
            // Check if email already exists
            $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ? AND deleted_at IS NULL");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email already taken.';
            } else {
                // Check resend cooldown entirely in MySQL to avoid PHP/MySQL timezone desyncs
                $stmt = $db->prepare("SELECT TIMESTAMPDIFF(SECOND, created_at, NOW()) as seconds_ago FROM verification_codes WHERE email = ? AND type = 'registration' ORDER BY created_at DESC LIMIT 1");
                $stmt->execute([$email]);
                $lastCode = $stmt->fetch();
                if ($lastCode && $lastCode['seconds_ago'] >= 0 && $lastCode['seconds_ago'] < CODE_RESEND_SECONDS) {
                    $wait = CODE_RESEND_SECONDS - $lastCode['seconds_ago'];
                    $error = "Please wait $wait seconds before requesting another code.";
                }

                if (empty($error)) {
                    $baseUsername = preg_replace('/[^a-zA-Z0-9]/', '', strstr($email, '@', true));
                    if (!$baseUsername)
                        $baseUsername = 'user';
                    $username = $baseUsername;
                    $counter = 1;
                    while (true) {
                        $stmt = $db->prepare("SELECT user_id FROM users WHERE username = ?");
                        $stmt->execute([$username]);
                        if (!$stmt->fetch())
                            break;
                        $username = $baseUsername . $counter;
                        $counter++;
                    }

                    $code = generateCode();
                    $payload = json_encode([
                        'username' => $username,
                        'email' => $email,
                        'password_hash' => password_hash($password, PASSWORD_BCRYPT),
                        'first_name' => $firstName,
                        'middle_name' => $middleName,
                        'last_name' => $lastName,
                        'date_of_birth' => $dob,
                        'primary_goal' => $primaryGoal,
                        'height_cm' => $heightCm,
                        'weight_kg' => $weightKg
                    ]);
                    $stmt = $db->prepare("INSERT INTO verification_codes (email, code, type, payload, expires_at, created_at) VALUES (?, ?, 'registration', ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), NOW())");
                    $stmt->execute([$email, $code, $payload, CODE_EXPIRY_MINUTES]);

                    $sent = sendVerificationEmail($email, $code, 'registration');
                    if ($sent || MAIL_DEBUG_MODE) {
                        $_SESSION['verify_email'] = $email;
                        $_SESSION['verify_type'] = 'registration';
                        if (MAIL_DEBUG_MODE) {
                            $_SESSION['debug_code'] = $code;
                        }
                        redirect('/auth/verify.php');
                    } else {
                        $error = 'Failed to send verification email. Please try again.';
                    }
                }
            }
        }
    }
}
?>
<div class="auth-page">
    <div class="auth-card animate-in" style="max-width: 540px;">
        <div class="auth-header">
            <div class="logo-icon"
                style="background:transparent; padding:0; width:80px; height:80px; margin:0 auto 20px; overflow:hidden;">
                <img src="<?= BASE_URL ?>/assets/image/logo21.png" alt="FitTrack Pro"
                    style="width:100%; height:100%; object-fit:cover; border-radius:50%; display:block;">
            </div>
            <h1>Create Account</h1>
            <p>Start your fitness journey with FitTrack Pro</p>
        </div>
        <?php if ($error): ?>
            <div class="flash-message flash-error"><i data-lucide="alert-circle"></i><span><?= $error ?></span></div>
        <?php endif; ?>
        <form method="POST" id="registerForm">
            <?= csrfField() ?>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label class="form-label" for="first_name">First Name <span
                            class="required-asterisk">*</span></label>
                    <input type="text" id="first_name" name="first_name" class="form-control"
                        value="<?= sanitize($_POST['first_name'] ?? '') ?>" required placeholder="John">
                </div>
                <div class="form-group">
                    <label class="form-label" for="middle_name">Middle Name</label>
                    <input type="text" id="middle_name" name="middle_name" class="form-control"
                        value="<?= sanitize($_POST['middle_name'] ?? '') ?>" placeholder="Robert">
                </div>
                <div class="form-group">
                    <label class="form-label" for="last_name">Last Name <span class="required-asterisk">*</span></label>
                    <input type="text" id="last_name" name="last_name" class="form-control"
                        value="<?= sanitize($_POST['last_name'] ?? '') ?>" required placeholder="Doe">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="date_of_birth">Date of Birth <span
                        class="required-asterisk">*</span></label>
                <input type="date" id="date_of_birth" name="date_of_birth" class="form-control"
                    value="<?= sanitize($_POST['date_of_birth'] ?? '') ?>" required max="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="primary_goal">Primary Goal <span
                        class="required-asterisk">*</span></label>
                <select name="primary_goal" id="primary_goal" class="form-control" required>
                    <option value="" disabled <?= empty($_POST['primary_goal']) ? 'selected' : '' ?>>Select your goal...
                    </option>
                    <option value="Lose Weight" <?= (($_POST['primary_goal'] ?? '') === 'Lose Weight') ? 'selected' : '' ?>>Lose Weight</option>
                    <option value="Gain Muscle" <?= (($_POST['primary_goal'] ?? '') === 'Gain Muscle') ? 'selected' : '' ?>>Gain Muscle</option>
                    <option value="Maintain Weight" <?= (($_POST['primary_goal'] ?? '') === 'Maintain Weight') ? 'selected' : '' ?>>Maintain Weight</option>
                    <option value="Improve Fitness" <?= (($_POST['primary_goal'] ?? '') === 'Improve Fitness') ? 'selected' : '' ?>>Improve Fitness & Endurance</option>
                    <option value="General Health" <?= (($_POST['primary_goal'] ?? '') === 'General Health') ? 'selected' : '' ?>>General Health & Wellness</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Unit System <span class="required-asterisk">*</span></label>
                <div style="display:flex;gap:8px;">
                    <button type="button" class="btn" id="btn-metric" style="flex:1"
                        onclick="setUnit('metric')">Metric</button>
                    <button type="button" class="btn btn-outline" id="btn-imperial" style="flex:1"
                        onclick="setUnit('imperial')">Imperial </button>
                </div>
                <input type="hidden" name="unit_pref" id="unit_pref"
                    value="<?= sanitize($_POST['unit_pref'] ?? 'metric') ?>" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="height">Height (<span id="hLabel">cm</span>) <span
                            class="required-asterisk">*</span></label>
                    <input type="number" id="height" name="height" class="form-control" step="0.1"
                        value="<?= sanitize($_POST['height'] ?? '') ?>" required placeholder="e.g. 175">
                </div>
                <div class="form-group">
                    <label class="form-label" for="weight">Weight (<span id="wLabel">kg</span>) <span
                            class="required-asterisk">*</span></label>
                    <input type="number" id="weight" name="weight" class="form-control" step="0.1"
                        value="<?= sanitize($_POST['weight'] ?? '') ?>" required placeholder="e.g. 70">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email Address <span class="required-asterisk">*</span></label>
                <input type="email" id="email" name="email" class="form-control"
                    value="<?= sanitize($_POST['email'] ?? '') ?>" required placeholder="john.doe@example.com">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="password">Password <span class="required-asterisk">*</span></label>
                    <input type="password" id="password" name="password" class="form-control" required minlength="6"
                        placeholder="Min. 6 characters">
                </div>
                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm Password <span
                            class="required-asterisk">*</span></label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required
                        placeholder="Retype password">
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg">Create Account</button>
        </form>
        <div class="auth-footer">
            Already have an account? <a href="<?= BASE_URL ?>/auth/login.php">Sign In</a>
        </div>
    </div>
</div>
<script>
    function setUnit(unit) {
        document.getElementById('unit_pref').value = unit;
        updateUnits();
    }
    function updateUnits() {
        const unit = document.getElementById('unit_pref').value || 'metric';
        const isImperial = unit === 'imperial';
        document.getElementById('hLabel').textContent = isImperial ? 'inches' : 'cm';
        document.getElementById('wLabel').textContent = isImperial ? 'lbs' : 'kg';

        if (isImperial) {
            document.getElementById('btn-imperial').className = 'btn btn-primary';
            document.getElementById('btn-metric').className = 'btn btn-outline';
        } else {
            document.getElementById('btn-metric').className = 'btn btn-primary';
            document.getElementById('btn-imperial').className = 'btn btn-outline';
        }
    }
    document.addEventListener('DOMContentLoaded', updateUnits);
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>