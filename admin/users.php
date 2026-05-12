<?php
$pageTitle = 'Manage Users';
require_once __DIR__ . '/../includes/header.php';
requireAdmin();
$db = getDB();

define('SYSTEM_OWNER_EMAIL', 'admin@fittrack.com');
$currentUserEmail = getCurrentUser()['email'] ?? '';
$isSystemOwner = ($currentUserEmail === SYSTEM_OWNER_EMAIL);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF()) {
    $action = $_POST['action'] ?? '';
    $targetId = (int)$_POST['user_id'];
    if ($targetId === (int)$_SESSION['user_id']) { setFlash('error','Cannot modify your own account here.'); redirect('/admin/users.php'); }

    // Get target user info
    $target = $db->prepare("SELECT email, role FROM users WHERE user_id=?"); $target->execute([$targetId]); $target = $target->fetch();

    if ($action === 'toggle_role') {
        $newRole = $_POST['new_role'] === 'admin' ? 'admin' : 'user';
        // Protect system owner from demotion
        if ($target && $target['email'] === SYSTEM_OWNER_EMAIL) {
            setFlash('error', 'Cannot modify the system owner account.');
        } elseif ($newRole === 'admin' && !$isSystemOwner) {
            // Non-owners promoting to admin needs owner approval — for now block
            setFlash('error', 'Only the system owner can promote users to admin.');
        } elseif ($newRole === 'user' && $target['role'] === 'admin' && !$isSystemOwner) {
            setFlash('error', 'Only the system owner can demote other admins.');
        } else {
            $db->prepare("UPDATE users SET role=?,updated_at=NOW() WHERE user_id=?")->execute([$newRole,$targetId]);
            logAction($_SESSION['user_id'],'admin_role_change',"Changed user #$targetId role to $newRole");
            setFlash('success', $newRole === 'admin' ? 'User promoted to Admin!' : 'Admin demoted to User.');
        }
    } elseif ($action === 'deactivate') {
        if ($target && $target['email'] === SYSTEM_OWNER_EMAIL) {
            setFlash('error', 'Cannot deactivate the system owner account.');
        } else {
            $db->prepare("UPDATE users SET deleted_at=NOW(),deactivation_reason=? WHERE user_id=?")->execute([$_POST['reason']??'Admin action',$targetId]);
            logAction($_SESSION['user_id'],'admin_deactivate',"Deactivated user #$targetId");
            setFlash('success','User deactivated. They can be found in Delete Logs.');
        }
    } elseif ($action === 'reactivate') {
        $db->prepare("UPDATE users SET deleted_at=NULL,deactivation_reason=NULL WHERE user_id=?")->execute([$targetId]);
        logAction($_SESSION['user_id'],'admin_reactivate',"Reactivated user #$targetId");
        setFlash('success','User reactivated.');
    }
    redirect('/admin/users.php?'.http_build_query(array_filter(['search'=>$_GET['search']??'','role'=>$_GET['role']??'','status'=>$_GET['status']??''])));
}

$search = trim($_GET['search']??''); $roleFilter = $_GET['role']??''; $statusFilter = $_GET['status']??'';
$page = max(1,(int)($_GET['page']??1));
$where = "deleted_at IS NULL"; $params = [];
if ($search) { $where .= " AND (username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)"; $params = array_merge($params,["%$search%","%$search%","%$search%","%$search%"]); }
if ($roleFilter) { $where .= " AND role=?"; $params[] = $roleFilter; }

$total = $db->prepare("SELECT COUNT(*) FROM users WHERE $where"); $total->execute($params); $total = $total->fetchColumn();
$pagination = paginate($total, 10, $page);

$stmt = $db->prepare("SELECT u.*, 
    (SELECT COUNT(*) FROM activities WHERE user_id = u.user_id AND deleted_at IS NULL) as activity_count,
    (SELECT COUNT(*) FROM nutrition_logs WHERE user_id = u.user_id AND deleted_at IS NULL) as nutrition_count
    FROM users u WHERE $where ORDER BY u.created_at DESC LIMIT 10 OFFSET {$pagination['offset']}");
$stmt->execute($params); $users = $stmt->fetchAll();
?>
<div class="section-header"><h2>Users Management</h2></div>

<!-- Unified Toolbar -->
<div class="toolbar">
    <form method="GET" style="display:flex; gap:8px; width:100%; flex-wrap:wrap; align-items:center;">
        <div class="toolbar-search" style="flex:1; margin:0; min-width:200px;">
            <i data-lucide="search"></i>
            <input type="text" name="search" class="form-control" placeholder="Search users..." value="<?= sanitize($search) ?>">
        </div>
        <select name="role" class="form-control" style="width:auto;">
            <option value="">All Roles</option>
            <option value="user" <?= $roleFilter==='user'?'selected':'' ?>>User</option>
            <option value="admin" <?= $roleFilter==='admin'?'selected':'' ?>>Admin</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm" style="padding:0.5rem 1rem;">Filter</button>
        <a href="users.php" class="btn btn-outline btn-sm" style="padding:0.5rem 1rem;">Reset</a>
    </form>
</div>

<div class="table-container"><table class="data-table">
<thead><tr><th>User</th><th>Email</th><th>Role</th><th>Status</th><th>Activities</th><th>Nutrition</th><th>Joined</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach($users as $u): $isSelf = $u['user_id'] == $_SESSION['user_id']; $isOwner = $u['email'] === SYSTEM_OWNER_EMAIL; ?>
<tr>
    <td><strong><?= sanitize($u['first_name'].' '.$u['last_name']) ?></strong><br><small style="color:var(--text-muted)">@<?= sanitize($u['username']) ?></small></td>
    <td><?= sanitize($u['email']) ?></td>
    <td>
        <span class="badge badge-<?= $u['role']==='admin'?'info':'secondary' ?>"><?= ucfirst($u['role']) ?></span>
        <?php if($isOwner): ?><span class="badge badge-warning" style="margin-left:4px;font-size:10px;">Owner</span><?php endif; ?>
    </td>
    <td><span class="badge badge-<?= $u['deleted_at']?'danger':'success' ?>"><?= $u['deleted_at']?'Deactivated':'Active' ?></span></td>
    <td><?= $u['activity_count'] ?></td><td><?= $u['nutrition_count'] ?></td>
    <td><?= formatDate($u['created_at']) ?></td>
    <td class="table-actions">
        <?php if(!$isSelf && !$isOwner): ?>
        <!-- Promote/Demote -->
        <form method="POST" style="display:inline" class="role-toggle-form"><?= csrfField() ?><input type="hidden" name="action" value="toggle_role"><input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
            <input type="hidden" name="new_role" value="<?= $u['role']==='admin'?'user':'admin' ?>">
            <button type="submit" class="btn btn-ghost btn-sm" title="<?= $u['role']==='admin'?'Demote to User':'Promote to Admin' ?>"><i data-lucide="<?= $u['role']==='admin'?'shield-off':'shield' ?>"></i></button></form>
        <!-- Delete -->
        <form method="POST" style="display:inline" class="deactivate-form"><?= csrfField() ?><input type="hidden" name="action" value="deactivate"><input type="hidden" name="user_id" value="<?= $u['user_id'] ?>"><input type="hidden" name="reason" value="Admin action">
            <button type="submit" class="btn btn-ghost btn-sm" style="color:#ef4444" title="Delete"><i data-lucide="trash-2"></i></button></form>
        <?php elseif($isSelf): ?>
        <span style="color:var(--text-muted);font-size:12px;">You</span>
        <?php elseif($isOwner): ?>
        <span style="color:var(--text-muted);font-size:12px;">Protected</span>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?= renderPagination($pagination, '?search='.urlencode($search).'&role='.urlencode($roleFilter)) ?>

<script>
/* Intercept role toggle forms */
document.querySelectorAll('.role-toggle-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const newRole = this.querySelector('[name="new_role"]').value;
        const title = newRole === 'admin' ? 'Promote to Admin' : 'Demote to User';
        const msg = newRole === 'admin' ? 'This user will gain full admin privileges.' : 'This admin will lose all admin privileges.';
        const btnClass = newRole === 'admin' ? 'btn-primary' : 'btn-danger';
        showConfirm(title, msg, title, btnClass, () => { this.submit(); });
    });
});

/* Intercept delete forms */
document.querySelectorAll('.deactivate-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        showConfirm('Delete User', 'This user will be moved to Delete Logs. You can restore them later or permanently delete them.', 'Delete', 'btn-danger', () => { this.submit(); });
    });
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
