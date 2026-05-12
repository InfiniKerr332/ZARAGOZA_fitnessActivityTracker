<?php
$pageTitle = 'Delete Logs';
require_once __DIR__ . '/../includes/header.php';
requireAdmin();
$db = getDB();

define('SYSTEM_OWNER_EMAIL_DL', 'admin@fittrack.com');

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF()) {
    $action = $_POST['action'] ?? '';
    $type = $_POST['type'] ?? 'users';
    $targetId = (int)$_POST['item_id'];

    if ($type === 'users') {
        $target = $db->prepare("SELECT email FROM users WHERE user_id=?"); $target->execute([$targetId]); $target = $target->fetch();
        if ($target && $target['email'] === SYSTEM_OWNER_EMAIL_DL) {
            setFlash('error', 'Cannot modify the system owner account.');
            redirect('/admin/delete-logs.php?type=users');
        }

        if ($action === 'restore') {
            $db->prepare("UPDATE users SET deleted_at=NULL,deactivation_reason=NULL WHERE user_id=?")->execute([$targetId]);
            logAction($_SESSION['user_id'], 'admin_restore', "Restored user #$targetId");
            setFlash('success', 'User restored successfully!');
        } elseif ($action === 'permanent_delete') {
            $db->prepare("DELETE FROM system_logs WHERE user_id=?")->execute([$targetId]);
            $db->prepare("DELETE FROM body_metrics WHERE user_id=?")->execute([$targetId]);
            $db->prepare("DELETE FROM goals WHERE user_id=?")->execute([$targetId]);
            $db->prepare("DELETE FROM nutrition_logs WHERE user_id=?")->execute([$targetId]);
            $db->prepare("DELETE FROM activities WHERE user_id=?")->execute([$targetId]);
            $db->prepare("DELETE FROM users WHERE user_id=?")->execute([$targetId]);
            logAction($_SESSION['user_id'], 'admin_permanent_delete', "Permanently deleted user #$targetId");
            setFlash('success', 'User permanently deleted.');
        }
    } elseif ($type === 'categories') {
        if ($action === 'restore') {
            $db->prepare("UPDATE activity_categories SET deleted_at=NULL WHERE category_id=?")->execute([$targetId]);
            logAction($_SESSION['user_id'], 'category_restore', "Restored category #$targetId");
            setFlash('success', 'Category restored successfully!');
        } elseif ($action === 'permanent_delete') {
            // Delete exercises in this category first, then the category
            $db->prepare("DELETE FROM exercise_types WHERE category_id=?")->execute([$targetId]);
            $db->prepare("DELETE FROM activity_categories WHERE category_id=?")->execute([$targetId]);
            logAction($_SESSION['user_id'], 'category_permanent_delete', "Permanently deleted category #$targetId");
            setFlash('success', 'Category permanently deleted.');
        }
    } elseif ($type === 'exercises') {
        if ($action === 'restore') {
            $db->prepare("UPDATE exercise_types SET deleted_at=NULL WHERE exercise_id=?")->execute([$targetId]);
            logAction($_SESSION['user_id'], 'exercise_restore', "Restored exercise #$targetId");
            setFlash('success', 'Exercise restored successfully!');
        } elseif ($action === 'permanent_delete') {
            $db->prepare("DELETE FROM exercise_types WHERE exercise_id=?")->execute([$targetId]);
            logAction($_SESSION['user_id'], 'exercise_permanent_delete', "Permanently deleted exercise #$targetId");
            setFlash('success', 'Exercise permanently deleted.');
        }
    }
    redirect('/admin/delete-logs.php?type=' . urlencode($type));
}

// Filters
$typeFilter = $_GET['type'] ?? 'users';
if (!in_array($typeFilter, ['users', 'categories', 'exercises'])) $typeFilter = 'users';
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));

// Fetch deleted items based on type
$items = [];
$total = 0;
$pagination = null;

if ($typeFilter === 'users') {
    $where = "deleted_at IS NOT NULL"; $params = [];
    if ($search) {
        $where .= " AND (username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
        $params = ["%$search%", "%$search%", "%$search%", "%$search%"];
    }
    $total = $db->prepare("SELECT COUNT(*) FROM users WHERE $where"); $total->execute($params); $total = $total->fetchColumn();
    $pagination = paginate($total, 10, $page);
    $stmt = $db->prepare("SELECT u.*,
        (SELECT COUNT(*) FROM activities WHERE user_id = u.user_id) as activity_count,
        (SELECT COUNT(*) FROM nutrition_logs WHERE user_id = u.user_id) as nutrition_count
        FROM users u WHERE $where ORDER BY u.deleted_at DESC LIMIT 10 OFFSET {$pagination['offset']}");
    $stmt->execute($params); $items = $stmt->fetchAll();

} elseif ($typeFilter === 'categories') {
    $where = "c.deleted_at IS NOT NULL"; $params = [];
    if ($search) {
        $where .= " AND (c.category_name LIKE ? OR c.description LIKE ?)";
        $params = ["%$search%", "%$search%"];
    }
    $total = $db->prepare("SELECT COUNT(*) FROM activity_categories c WHERE $where"); $total->execute($params); $total = $total->fetchColumn();
    $pagination = paginate($total, 10, $page);
    $stmt = $db->prepare("SELECT c.*,
        (SELECT COUNT(*) FROM exercise_types WHERE category_id = c.category_id) as exercise_count
        FROM activity_categories c WHERE $where ORDER BY c.deleted_at DESC LIMIT 10 OFFSET {$pagination['offset']}");
    $stmt->execute($params); $items = $stmt->fetchAll();

} elseif ($typeFilter === 'exercises') {
    $where = "e.deleted_at IS NOT NULL"; $params = [];
    if ($search) {
        $where .= " AND (e.exercise_name LIKE ? OR e.muscle_group LIKE ?)";
        $params = ["%$search%", "%$search%"];
    }
    $total = $db->prepare("SELECT COUNT(*) FROM exercise_types e WHERE $where"); $total->execute($params); $total = $total->fetchColumn();
    $pagination = paginate($total, 10, $page);
    $stmt = $db->prepare("SELECT e.*, c.category_name, c.color_hex
        FROM exercise_types e
        LEFT JOIN activity_categories c ON e.category_id = c.category_id
        WHERE $where ORDER BY e.deleted_at DESC LIMIT 10 OFFSET {$pagination['offset']}");
    $stmt->execute($params); $items = $stmt->fetchAll();
}

// Count badges
$countUsers = $db->query("SELECT COUNT(*) FROM users WHERE deleted_at IS NOT NULL")->fetchColumn();
$countCats = $db->query("SELECT COUNT(*) FROM activity_categories WHERE deleted_at IS NOT NULL")->fetchColumn();
$countExercises = $db->query("SELECT COUNT(*) FROM exercise_types WHERE deleted_at IS NOT NULL")->fetchColumn();
$totalAll = $countUsers + $countCats + $countExercises;
?>
<div class="section-header">
    <h2>Delete Logs</h2>
    <span class="badge badge-danger" style="font-size:14px;padding:8px 16px;"><?= $totalAll ?> Deleted Item<?= $totalAll !== 1 ? 's' : '' ?></span>
</div>

<!-- Unified Toolbar -->
<div class="toolbar">
    <form method="GET" style="display:flex; gap:8px; width:100%; flex-wrap:wrap; align-items:center;">
        <div class="toolbar-search" style="flex:1; margin:0; min-width:200px;">
            <i data-lucide="search"></i>
            <input type="text" name="search" class="form-control" placeholder="Search deleted items..." value="<?= sanitize($search) ?>">
        </div>
        <select name="type" class="form-control" style="width:auto;">
            <option value="users" <?= $typeFilter==='users'?'selected':'' ?>>Users (<?= $countUsers ?>)</option>
            <option value="categories" <?= $typeFilter==='categories'?'selected':'' ?>>Categories (<?= $countCats ?>)</option>
            <option value="exercises" <?= $typeFilter==='exercises'?'selected':'' ?>>Exercises (<?= $countExercises ?>)</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm" style="padding:0.5rem 1rem;">Filter</button>
        <a href="delete-logs.php" class="btn btn-outline btn-sm" style="padding:0.5rem 1rem;">Reset</a>
    </form>
</div>

<?php if (empty($items)): ?>
<div class="card"><div class="empty-state">
    <i data-lucide="check-circle"></i>
    <h3>No Deleted <?= ucfirst($typeFilter) ?></h3>
    <p>No deleted <?= $typeFilter ?> found. They will appear here when removed.</p>
</div></div>

<?php elseif ($typeFilter === 'users'): ?>
<!-- USERS TABLE -->
<div class="table-container"><table class="data-table">
<thead><tr><th>User</th><th>Email</th><th>Role</th><th>Reason</th><th>Activities</th><th>Nutrition</th><th>Deleted On</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach($items as $u): ?>
<tr>
    <td><strong><?= sanitize($u['first_name'].' '.$u['last_name']) ?></strong><br><small style="color:var(--text-muted)">@<?= sanitize($u['username']) ?></small></td>
    <td><?= sanitize($u['email']) ?></td>
    <td><span class="badge badge-<?= $u['role']==='admin'?'info':'secondary' ?>"><?= ucfirst($u['role']) ?></span></td>
    <td><span style="color:var(--text-secondary);font-size:13px;"><?= sanitize($u['deactivation_reason'] ?: 'No reason provided') ?></span></td>
    <td><?= $u['activity_count'] ?></td>
    <td><?= $u['nutrition_count'] ?></td>
    <td><?= formatDate($u['deleted_at']) ?></td>
    <td class="table-actions">
        <form method="POST" class="restore-form"><input type="hidden" name="type" value="users"><?= csrfField() ?><input type="hidden" name="action" value="restore"><input type="hidden" name="item_id" value="<?= $u['user_id'] ?>">
            <button type="submit" class="btn btn-ghost btn-sm" style="color:#22c55e" title="Restore User"><i data-lucide="undo-2"></i></button></form>
        <form method="POST" class="perm-delete-form"><input type="hidden" name="type" value="users"><?= csrfField() ?><input type="hidden" name="action" value="permanent_delete"><input type="hidden" name="item_id" value="<?= $u['user_id'] ?>">
            <button type="submit" class="btn btn-ghost btn-sm" style="color:#ef4444" title="Permanently Delete"><i data-lucide="trash-2"></i></button></form>
    </td>
</tr>
<?php endforeach; ?>
</tbody></table></div>

<?php elseif ($typeFilter === 'categories'): ?>
<!-- CATEGORIES TABLE -->
<div class="table-container"><table class="data-table">
<thead><tr><th>Category</th><th>Icon</th><th>Color</th><th>Exercises</th><th>Description</th><th>Deleted On</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach($items as $c): ?>
<tr>
    <td><strong><?= sanitize($c['category_name']) ?></strong></td>
    <td><i data-lucide="<?= sanitize($c['icon']) ?>"></i> <?= sanitize($c['icon']) ?></td>
    <td><span style="display:inline-block;width:20px;height:20px;border-radius:4px;background:<?= $c['color_hex'] ?>;vertical-align:middle;"></span> <?= $c['color_hex'] ?></td>
    <td><?= $c['exercise_count'] ?></td>
    <td><span style="color:var(--text-secondary);font-size:13px;"><?= sanitize($c['description'] ?: '—') ?></span></td>
    <td><?= formatDate($c['deleted_at']) ?></td>
    <td class="table-actions">
        <form method="POST" class="restore-form"><input type="hidden" name="type" value="categories"><?= csrfField() ?><input type="hidden" name="action" value="restore"><input type="hidden" name="item_id" value="<?= $c['category_id'] ?>">
            <button type="submit" class="btn btn-ghost btn-sm" style="color:#22c55e" title="Restore Category"><i data-lucide="undo-2"></i></button></form>
        <form method="POST" class="perm-delete-form"><input type="hidden" name="type" value="categories"><?= csrfField() ?><input type="hidden" name="action" value="permanent_delete"><input type="hidden" name="item_id" value="<?= $c['category_id'] ?>">
            <button type="submit" class="btn btn-ghost btn-sm" style="color:#ef4444" title="Permanently Delete"><i data-lucide="trash-2"></i></button></form>
    </td>
</tr>
<?php endforeach; ?>
</tbody></table></div>

<?php elseif ($typeFilter === 'exercises'): ?>
<!-- EXERCISES TABLE -->
<div class="table-container"><table class="data-table">
<thead><tr><th>Exercise</th><th>Category</th><th>Unit</th><th>Muscle Group</th><th>Difficulty</th><th>Cal/Unit</th><th>Deleted On</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach($items as $e): ?>
<tr>
    <td><strong><?= sanitize($e['exercise_name']) ?></strong></td>
    <td><span class="badge" style="background:<?= $e['color_hex'] ?? '#666' ?>20;color:<?= $e['color_hex'] ?? '#666' ?>"><?= sanitize($e['category_name'] ?? 'Unknown') ?></span></td>
    <td><?= sanitize($e['unit']) ?></td>
    <td><?= $e['muscle_group'] ? sanitize($e['muscle_group']) : '—' ?></td>
    <td><span class="badge badge-<?= match($e['difficulty']){'beginner'=>'success','intermediate'=>'warning','advanced'=>'danger',default=>'secondary'} ?>"><?= ucfirst($e['difficulty']) ?></span></td>
    <td><?= number_format($e['calories_per_unit'],1) ?></td>
    <td><?= formatDate($e['deleted_at']) ?></td>
    <td class="table-actions">
        <form method="POST" class="restore-form"><input type="hidden" name="type" value="exercises"><?= csrfField() ?><input type="hidden" name="action" value="restore"><input type="hidden" name="item_id" value="<?= $e['exercise_id'] ?>">
            <button type="submit" class="btn btn-ghost btn-sm" style="color:#22c55e" title="Restore Exercise"><i data-lucide="undo-2"></i></button></form>
        <form method="POST" class="perm-delete-form"><input type="hidden" name="type" value="exercises"><?= csrfField() ?><input type="hidden" name="action" value="permanent_delete"><input type="hidden" name="item_id" value="<?= $e['exercise_id'] ?>">
            <button type="submit" class="btn btn-ghost btn-sm" style="color:#ef4444" title="Permanently Delete"><i data-lucide="trash-2"></i></button></form>
    </td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>

<?php if ($pagination): ?>
<?= renderPagination($pagination, '?type='.urlencode($typeFilter).'&search='.urlencode($search)) ?>
<?php endif; ?>

<script>
/* Restore confirmation modals */
document.querySelectorAll('.restore-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const type = this.querySelector('[name="type"]').value;
        const label = type === 'users' ? 'User' : (type === 'categories' ? 'Category' : 'Exercise');
        showConfirm('Restore ' + label, 'This ' + label.toLowerCase() + ' will be restored and become active again.', 'Restore', 'btn-primary', () => { this.submit(); });
    });
});

/* Permanent delete confirmation modals */
document.querySelectorAll('.perm-delete-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const type = this.querySelector('[name="type"]').value;
        const label = type === 'users' ? 'User' : (type === 'categories' ? 'Category' : 'Exercise');
        const extra = type === 'users' ? ' and ALL their data' : (type === 'categories' ? ' and all its exercises' : '');
        showConfirm('Permanently Delete ' + label, 'This will permanently remove this ' + label.toLowerCase() + extra + '. This action CANNOT be undone!', 'Delete Forever', 'btn-danger', () => { this.submit(); });
    });
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
