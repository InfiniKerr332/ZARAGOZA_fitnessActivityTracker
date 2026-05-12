<?php
$pageTitle = 'Activities';
require_once __DIR__ . '/../includes/header.php';
requireLogin();
$db = getDB();
$uid = $_SESSION['user_id'];

// Handle Create/Update/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF()) {
    $action = $_POST['action'] ?? '';
    if ($action === 'create' || $action === 'update') {
        $data = [
            'exercise_id' => (int)$_POST['exercise_id'],
            'activity_date' => $_POST['activity_date'],
            'duration_minutes' => $_POST['duration_minutes'] ?: null,
            'distance_km' => $_POST['distance_km'] ?: null,
            'sets' => $_POST['sets'] ?: null,
            'reps' => $_POST['reps'] ?: null,
            'weight_used_kg' => $_POST['weight_used_kg'] ?: null,
            'calories_burned' => $_POST['calories_burned'] ?: null,
            'notes' => $_POST['notes'] ?: null,
        ];
        if ($action === 'create') {
            $status = 'planned'; // Always default to planned so user must check it off
            $stmt = $db->prepare("INSERT INTO activities (user_id, exercise_id, activity_date, duration_minutes, distance_km, sets, reps, weight_used_kg, calories_burned, notes, status) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$uid, $data['exercise_id'], $data['activity_date'], $data['duration_minutes'], $data['distance_km'], $data['sets'], $data['reps'], $data['weight_used_kg'], $data['calories_burned'], $data['notes'], $status]);
            logAction($uid, 'activity_create', 'Created activity');
            setFlash('success', 'Activity planned! Click the checkmark when completed.');
        } else {
            $id = (int)$_POST['activity_id'];
            $stmt = $db->prepare("UPDATE activities SET exercise_id=?, activity_date=?, duration_minutes=?, distance_km=?, sets=?, reps=?, weight_used_kg=?, calories_burned=?, notes=? WHERE activity_id=? AND user_id=?");
            $stmt->execute([$data['exercise_id'], $data['activity_date'], $data['duration_minutes'], $data['distance_km'], $data['sets'], $data['reps'], $data['weight_used_kg'], $data['calories_burned'], $data['notes'], $id, $uid]);
            logAction($uid, 'activity_update', "Updated activity #$id");
            setFlash('success', 'Activity updated!');
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['activity_id'];
        $db->prepare("UPDATE activities SET deleted_at = NOW() WHERE activity_id = ? AND user_id = ?")->execute([$id, $uid]);
        logAction($uid, 'activity_delete', "Soft-deleted activity #$id");
        setFlash('success', 'Activity deleted.');
    } elseif ($action === 'complete') {
        $id = (int)$_POST['activity_id'];
        $db->prepare("UPDATE activities SET status = 'completed' WHERE activity_id = ? AND user_id = ?")->execute([$id, $uid]);
        logAction($uid, 'activity_complete', "Completed activity #$id");
        setFlash('success', 'Activity marked as completed!');
    } elseif ($action === 'relog') {
        $id = (int)$_POST['activity_id'];
        $orig = $db->prepare("SELECT * FROM activities WHERE activity_id=? AND user_id=?");
        $orig->execute([$id, $uid]); $o = $orig->fetch();
        if ($o) {
            $db->prepare("INSERT INTO activities (user_id,exercise_id,activity_date,duration_minutes,distance_km,sets,reps,weight_used_kg,calories_burned,notes,status) VALUES (?,?,CURDATE(),?,?,?,?,?,?,?,?)")
               ->execute([$uid,$o['exercise_id'],$o['duration_minutes'],$o['distance_km'],$o['sets'],$o['reps'],$o['weight_used_kg'],$o['calories_burned'],$o['notes'],'completed']);
            logAction($uid,'activity_relog',"Re-logged activity #$id");
            setFlash('success','Workout re-logged for today!');
        }
    }
    if (($_POST['redirect_to'] ?? '') === 'dashboard') {
        redirect('/user/dashboard.php');
    }
    redirect('/user/activities.php?' . http_build_query(array_filter(['search' => $_GET['search'] ?? '', 'category' => $_GET['category'] ?? '', 'sort' => $_GET['sort'] ?? '', 'page' => $_GET['page'] ?? 1])));
}

// Search & Filter
$search = trim($_GET['search'] ?? '');
$catFilter = $_GET['category'] ?? '';
$sortOrder = $_GET['sort'] ?? 'latest';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$orderBy = $sortOrder === 'oldest' ? 'a.activity_date ASC, a.created_at ASC' : 'a.activity_date DESC, a.created_at DESC';

$where = "a.user_id = ? AND a.deleted_at IS NULL";
$params = [$uid];
if ($search) { $where .= " AND (e.exercise_name LIKE ? OR a.notes LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($catFilter) { $where .= " AND c.category_id = ?"; $params[] = $catFilter; }

$countStmt = $db->prepare("SELECT COUNT(*) FROM activities a JOIN exercise_types e ON a.exercise_id=e.exercise_id JOIN activity_categories c ON e.category_id=c.category_id WHERE $where");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$pagination = paginate($total, $perPage, $page);

// MULTIPLE JOIN with search/filter
$stmt = $db->prepare("SELECT a.*, e.exercise_name, e.unit as exercise_unit, c.category_name, c.color_hex
    FROM activities a
    JOIN exercise_types e ON a.exercise_id = e.exercise_id
    JOIN activity_categories c ON e.category_id = c.category_id
    WHERE $where ORDER BY $orderBy LIMIT $perPage OFFSET {$pagination['offset']}");
$stmt->execute($params);
$activities = $stmt->fetchAll();

// Get categories & exercises for form
$categories = $db->query("SELECT * FROM activity_categories WHERE deleted_at IS NULL ORDER BY category_name")->fetchAll();
$exercises = $db->query("SELECT e.*, c.category_name FROM exercise_types e JOIN activity_categories c ON e.category_id=c.category_id WHERE e.deleted_at IS NULL ORDER BY c.category_name, e.exercise_name")->fetchAll();
?>

<div class="section-header">
    <h2>My Activities</h2>
    <button class="btn btn-primary" data-modal="activityModal" onclick="resetForm()"><i data-lucide="plus"></i> Log Activity</button>
</div>

<!-- Toolbar -->
<div class="toolbar">
    <form method="GET" style="display:flex; gap:8px; width:100%; flex-wrap:wrap; align-items:center;">
        <div class="toolbar-search" style="flex:1; margin:0; min-width:200px;">
            <i data-lucide="search"></i>
            <input type="text" name="search" class="form-control" placeholder="Search exercises..." value="<?= sanitize($search) ?>">
        </div>
        <select name="category" class="form-control" style="width:auto;">
            <option value="">All Categories</option>
            <?php foreach ($categories as $c): ?>
            <option value="<?= $c['category_id'] ?>" <?= $catFilter == $c['category_id'] ? 'selected' : '' ?>><?= sanitize($c['category_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="sort" class="form-control" style="width:auto;">
            <option value="latest" <?= $sortOrder==='latest'?'selected':'' ?>>Latest First</option>
            <option value="oldest" <?= $sortOrder==='oldest'?'selected':'' ?>>Oldest First</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm" style="padding:0.5rem 1rem;">Filter</button>
        <a href="activities.php" class="btn btn-outline btn-sm" style="padding:0.5rem 1rem;">Reset</a>
    </form>
</div>

<?php if (empty($activities)): ?>
<div class="card"><div class="empty-state"><i data-lucide="activity"></i><h3>No activities found</h3><p>Log your first workout to get started!</p></div></div>
<?php else: ?>
<div class="table-container">
    <table class="data-table">
        <thead><tr><th>Exercise</th><th>Category</th><th>Date</th><th>Duration</th><th>Calories</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($activities as $a): ?>
        <tr>
            <td><strong><?= sanitize($a['exercise_name']) ?></strong></td>
            <td><span class="badge" style="background:<?= $a['color_hex'] ?>20;color:<?= $a['color_hex'] ?>"><?= sanitize($a['category_name']) ?></span></td>
            <td><?= formatDate($a['activity_date']) ?></td>
            <td><?= $a['duration_minutes'] ? number_format($a['duration_minutes'],0).' min' : '—' ?></td>
            <td><?= $a['calories_burned'] ? number_format($a['calories_burned'],0).' kcal' : '—' ?></td>
            <td><span class="badge badge-<?= $a['status']==='completed'?'success':'warning' ?>"><?= ucfirst($a['status']) ?></span></td>
            <td class="table-actions">
                <?php if ($a['status'] === 'planned'): ?>
                <form method="POST" style="display:inline">
                    <?= csrfField() ?><input type="hidden" name="action" value="complete"><input type="hidden" name="activity_id" value="<?= $a['activity_id'] ?>">
                    <button class="btn btn-ghost btn-sm" style="color:#22c55e" title="Mark as completed"><i data-lucide="check-circle"></i></button>
                </form>
                <?php else: ?>
                <form method="POST" style="display:inline" class="relog-form">
                    <?= csrfField() ?><input type="hidden" name="action" value="relog"><input type="hidden" name="activity_id" value="<?= $a['activity_id'] ?>">
                    <button class="btn btn-ghost btn-sm" style="color:#22c55e" title="Re-log this workout for today"><i data-lucide="check-circle"></i></button>
                </form>
                <?php endif; ?>
                <button class="btn btn-ghost btn-sm" onclick='editActivity(<?= json_encode($a) ?>)'><i data-lucide="pencil"></i></button>
                <form method="POST" style="display:inline">
                    <?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="activity_id" value="<?= $a['activity_id'] ?>">
                    <button class="btn btn-ghost btn-sm" style="color:#ef4444"><i data-lucide="trash-2"></i></button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= renderPagination($pagination, '?search=' . urlencode($search) . '&category=' . urlencode($catFilter)) ?>
<?php endif; ?>

<!-- Activity Modal -->
<div class="modal-overlay" id="activityModal">
    <div class="modal">
        <div class="modal-header"><h2 id="modalTitle">Log Activity</h2><button class="modal-close" onclick="closeModal('activityModal')"><i data-lucide="x"></i></button></div>
        <div class="modal-body">
            <form method="POST" id="activityForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="activity_id" id="formActivityId">
                <div class="form-group">
                    <label class="form-label">Exercise <span class="required-asterisk">*</span></label>
                    <select name="exercise_id" id="formExercise" class="form-control" required>
                        <option value="">Select exercise...</option>
                        <?php $lastCat = ''; foreach ($exercises as $e): if ($e['category_name'] !== $lastCat): if ($lastCat) echo '</optgroup>'; echo '<optgroup label="'.sanitize($e['category_name']).'">'; $lastCat = $e['category_name']; endif; ?>
                        <option value="<?= $e['exercise_id'] ?>" data-cpu="<?= $e['calories_per_unit'] ?>"><?= sanitize($e['exercise_name']) ?></option>
                        <?php endforeach; if ($lastCat) echo '</optgroup>'; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Date <span class="required-asterisk">*</span></label>
                    <input type="date" name="activity_date" id="formDate" class="form-control" required value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-row">
                    <div class="form-group"><label class="form-label">Duration (min) <span class="required-asterisk">*</span></label><input type="number" name="duration_minutes" id="formDuration" class="form-control" step="0.01" min="0" required></div>
                    <div class="form-group"><label class="form-label">Distance (km)</label><input type="number" name="distance_km" id="formDistance" class="form-control" step="0.001" min="0"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label class="form-label">Sets</label><input type="number" name="sets" id="formSets" class="form-control" min="0"></div>
                    <div class="form-group"><label class="form-label">Reps</label><input type="number" name="reps" id="formReps" class="form-control" min="0"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label class="form-label">Weight Used (kg)</label><input type="number" name="weight_used_kg" id="formWeight" class="form-control" step="0.01" min="0"></div>
                    <div class="form-group"><label class="form-label">Calories Burned</label><input type="number" name="calories_burned" id="formCalories" class="form-control" step="0.01" min="0"></div>
                </div>
                <div class="form-group"><label class="form-label">Notes</label><textarea name="notes" id="formNotes" class="form-control" rows="2"></textarea></div>
                <div class="form-actions"><button type="submit" class="btn btn-primary btn-block">Save Activity</button></div>
            </form>
        </div>
    </div>
</div>

<script>
function resetForm() {
    document.getElementById('modalTitle').textContent = 'Log Activity';
    document.getElementById('formAction').value = 'create';
    document.getElementById('activityForm').reset();
    document.getElementById('formDate').value = new Date().toISOString().split('T')[0];
    openModal('activityModal');
}
function editActivity(a) {
    document.getElementById('modalTitle').textContent = 'Edit Activity';
    document.getElementById('formAction').value = 'update';
    document.getElementById('formActivityId').value = a.activity_id;
    document.getElementById('formExercise').value = a.exercise_id;
    document.getElementById('formDate').value = a.activity_date;
    document.getElementById('formDuration').value = a.duration_minutes || '';
    document.getElementById('formDistance').value = a.distance_km || '';
    document.getElementById('formSets').value = a.sets || '';
    document.getElementById('formReps').value = a.reps || '';
    document.getElementById('formWeight').value = a.weight_used_kg || '';
    document.getElementById('formCalories').value = a.calories_burned || '';
    document.getElementById('formNotes').value = a.notes || '';
    openModal('activityModal');
}

function calcCalories() {
    const ex = document.getElementById('formExercise');
    const dur = parseFloat(document.getElementById('formDuration').value) || 0;
    if (ex.selectedIndex > 0 && dur > 0) {
        const cpu = parseFloat(ex.options[ex.selectedIndex].dataset.cpu) || 0;
        if (cpu > 0) {
            document.getElementById('formCalories').value = (cpu * dur).toFixed(0);
        }
    }
}
document.getElementById('formExercise').addEventListener('change', calcCalories);
document.getElementById('formDuration').addEventListener('input', calcCalories);

/* Save confirmation */
document.getElementById('activityForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    showConfirm('Save Activity', 'Are you sure you want to save this activity?', 'Save', 'btn-primary', () => {
        form.submit();
    });
});

/* Intercept complete-activity forms */
document.querySelectorAll('form').forEach(form => {
    const actionInput = form.querySelector('input[name="action"][value="complete"]');
    if (actionInput) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            showConfirm('Complete Activity', 'Mark this workout as completed?', 'Complete', 'btn-primary', () => {
                this.submit();
            });
        });
    }
});

/* Intercept re-log forms */
document.querySelectorAll('.relog-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        showConfirm('Re-log Workout', 'You already completed this workout. Log it again for today?', 'Log Again', 'btn-primary', () => {
            this.submit();
        });
    });
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
