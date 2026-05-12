<?php
$pageTitle = 'Profile';
require_once __DIR__ . '/../includes/header.php';
requireLogin();
$db = getDB(); $uid = $_SESSION['user_id']; $user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF()) {
    $action = $_POST['action'] ?? 'update_profile';
    if ($action === 'update_profile') {
        $db->prepare("UPDATE users SET first_name=?,middle_name=?,last_name=?,date_of_birth=?,primary_goal=?,gender=?,height_cm=?,weight_kg=?,target_weight_kg=?,daily_calorie_goal=?,workout_frequency=?,updated_at=NOW() WHERE user_id=?")
            ->execute([trim($_POST['first_name']),trim($_POST['middle_name'])?:null,trim($_POST['last_name']),$_POST['date_of_birth']?:null,$_POST['primary_goal']?:null,$_POST['gender'],$_POST['height_cm']?:null,$_POST['weight_kg']?:null,$_POST['target_weight_kg']?:null,$_POST['daily_calorie_goal']?:null,$_POST['workout_frequency']?:null,$uid]);
        logAction($uid,'profile_update','Updated profile'); setFlash('success','Profile updated!');
    } elseif ($action === 'change_password') {
        if (!password_verify($_POST['current_password'], $user['password_hash'])) {
            setFlash('error','Current password is incorrect.');
        } elseif (strlen($_POST['new_password']) < 6) {
            setFlash('error','New password must be at least 6 characters.');
        } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
            setFlash('error','Passwords do not match.');
        } else {
            $db->prepare("UPDATE users SET password_hash=?,updated_at=NOW() WHERE user_id=?")->execute([password_hash($_POST['new_password'],PASSWORD_BCRYPT),$uid]);
            logAction($uid,'password_change','Changed password'); setFlash('success','Password changed!');
        }
    }
    redirect('/user/profile.php');
}
?>
<div style="max-width:700px">
<div class="card mb-3">
    <div class="card-header"><h3 class="card-title">Personal Information</h3></div>
    <form method="POST" id="profileForm"><?= csrfField() ?><input type="hidden" name="action" value="update_profile">
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
        <div class="form-group"><label class="form-label">First Name <span class="required-asterisk">*</span></label><input type="text" name="first_name" class="form-control" value="<?= sanitize($user['first_name']) ?>" required></div>
        <div class="form-group"><label class="form-label">Middle Name</label><input type="text" name="middle_name" class="form-control" value="<?= sanitize($user['middle_name']??'') ?>"></div>
        <div class="form-group"><label class="form-label">Last Name <span class="required-asterisk">*</span></label><input type="text" name="last_name" class="form-control" value="<?= sanitize($user['last_name']) ?>" required></div>
    </div>
    <div class="form-group"><label class="form-label">Date of Birth <span class="required-asterisk">*</span></label><input type="date" name="date_of_birth" class="form-control" value="<?= $user['date_of_birth']??'' ?>" required></div>
    <div class="form-group"><label class="form-label">Primary Goal <span class="required-asterisk">*</span></label><select name="primary_goal" class="form-control" required>
        <option value="Lose Weight" <?= ($user['primary_goal']??'')==='Lose Weight'?'selected':'' ?>>Lose Weight</option>
        <option value="Gain Muscle" <?= ($user['primary_goal']??'')==='Gain Muscle'?'selected':'' ?>>Gain Muscle</option>
        <option value="Maintain Weight" <?= ($user['primary_goal']??'')==='Maintain Weight'?'selected':'' ?>>Maintain Weight</option>
        <option value="Improve Fitness" <?= ($user['primary_goal']??'')==='Improve Fitness'?'selected':'' ?>>Improve Fitness & Endurance</option>
        <option value="General Health" <?= ($user['primary_goal']??'')==='General Health'?'selected':'' ?>>General Health & Wellness</option>
    </select></div>
    <div class="form-group"><label class="form-label">Gender</label><select name="gender" class="form-control">
        <?php foreach(['male','female','other','prefer_not_to_say'] as $g): ?>
        <option value="<?= $g ?>" <?= ($user['gender']??'')===$g?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$g)) ?></option>
        <?php endforeach; ?>
    </select></div>
    <div class="form-row">
        <div class="form-group"><label class="form-label">Height (cm) <span class="required-asterisk">*</span></label><input type="number" name="height_cm" class="form-control" step="0.01" value="<?= $user['height_cm']??'' ?>" required></div>
        <div class="form-group"><label class="form-label">Weight (kg) <span class="required-asterisk">*</span></label><input type="number" name="weight_kg" class="form-control" step="0.01" value="<?= $user['weight_kg']??'' ?>" required></div>
    </div>
    <div class="form-row">
        <div class="form-group"><label class="form-label">Target Weight (kg)</label><input type="number" name="target_weight_kg" class="form-control" step="0.01" value="<?= $user['target_weight_kg']??'' ?>"></div>
        <div class="form-group"><label class="form-label">Daily Calorie Goal</label><input type="number" name="daily_calorie_goal" class="form-control" value="<?= $user['daily_calorie_goal']??'' ?>"></div>
    </div>
    <div class="form-group"><label class="form-label">Workout Frequency (per week)</label><input type="number" name="workout_frequency" class="form-control" min="0" max="14" value="<?= $user['workout_frequency']??'' ?>"></div>
    <div class="form-actions"><button type="submit" class="btn btn-primary">Save Changes</button></div>
    </form>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">Change Password</h3></div>
    <form method="POST" id="passwordForm"><?= csrfField() ?><input type="hidden" name="action" value="change_password">
    <div class="form-group"><label class="form-label">Current Password</label><input type="password" name="current_password" class="form-control" required></div>
    <div class="form-row">
        <div class="form-group"><label class="form-label">New Password</label><input type="password" name="new_password" class="form-control" required minlength="6"></div>
        <div class="form-group"><label class="form-label">Confirm Password</label><input type="password" name="confirm_password" class="form-control" required></div>
    </div>
    <div class="form-actions"><button type="submit" class="btn btn-secondary">Change Password</button></div>
    </form>
</div>
</div>

<script>
document.getElementById('profileForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    showConfirm('Update Profile', 'Are you sure you want to save your profile changes?', 'Save', 'btn-primary', () => { form.submit(); });
});
document.getElementById('passwordForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    showConfirm('Change Password', 'Are you sure you want to change your password?', 'Change Password', 'btn-warning', () => { form.submit(); });
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
