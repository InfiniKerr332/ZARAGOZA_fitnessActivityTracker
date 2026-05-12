<?php
$pageTitle = 'Exercises';
require_once __DIR__ . '/../includes/header.php';
requireAdmin();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF()) {
    $action = $_POST['action']??'';
    if ($action === 'create') {
        $db->prepare("INSERT INTO exercise_types (category_id,exercise_name,unit,muscle_group,difficulty,calories_per_unit,description) VALUES (?,?,?,?,?,?,?)")
            ->execute([(int)$_POST['category_id'],trim($_POST['exercise_name']),$_POST['unit']??'minutes',$_POST['muscle_group']?:null,$_POST['difficulty']??'beginner',$_POST['calories_per_unit']??0,$_POST['description']?:null]);
        logAction($_SESSION['user_id'],'exercise_create','Created exercise'); setFlash('success','Exercise created!');
    } elseif ($action === 'update') {
        $id = (int)$_POST['exercise_id'];
        $db->prepare("UPDATE exercise_types SET category_id=?,exercise_name=?,unit=?,muscle_group=?,difficulty=?,calories_per_unit=?,description=? WHERE exercise_id=?")
            ->execute([(int)$_POST['category_id'],trim($_POST['exercise_name']),$_POST['unit'],$_POST['muscle_group']?:null,$_POST['difficulty'],$_POST['calories_per_unit']??0,$_POST['description']?:null,$id]);
        logAction($_SESSION['user_id'],'exercise_update',"Updated exercise #$id"); setFlash('success','Exercise updated!');
    } elseif ($action === 'delete') {
        $id = (int)$_POST['exercise_id'];
        $db->prepare("UPDATE exercise_types SET deleted_at=NOW() WHERE exercise_id=?")->execute([$id]);
        logAction($_SESSION['user_id'],'exercise_delete',"Deleted exercise #$id"); setFlash('success','Exercise deleted.');
    }
    redirect('/admin/exercises.php?'.http_build_query(array_filter(['search'=>$_GET['search']??'','category'=>$_GET['category']??''])));
}

$search = trim($_GET['search']??''); $catFilter = $_GET['category']??''; $page = max(1,(int)($_GET['page']??1));
$where = "e.deleted_at IS NULL"; $params = [];
if ($search) { $where .= " AND (e.exercise_name LIKE ? OR e.muscle_group LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($catFilter) { $where .= " AND c.category_id=?"; $params[] = $catFilter; }

$total = $db->prepare("SELECT COUNT(*) FROM exercise_types e JOIN activity_categories c ON e.category_id=c.category_id WHERE $where"); $total->execute($params); $total = $total->fetchColumn();
$pagination = paginate($total, 12, $page);
$stmt = $db->prepare("SELECT e.*, c.category_name, c.color_hex FROM exercise_types e JOIN activity_categories c ON e.category_id=c.category_id WHERE $where ORDER BY c.category_name, e.exercise_name LIMIT 12 OFFSET {$pagination['offset']}");
$stmt->execute($params); $exercises = $stmt->fetchAll();
$categories = $db->query("SELECT * FROM activity_categories WHERE deleted_at IS NULL ORDER BY category_name")->fetchAll();
?>
<div class="section-header"><h2>Exercise Types</h2>
    <button class="btn btn-primary" onclick="document.getElementById('eAction').value='create';document.getElementById('exForm').reset();openModal('exModal')"><i data-lucide="plus"></i> Add Exercise</button>
</div>
<div class="toolbar">
    <form method="GET" style="display:flex; gap:8px; width:100%; flex-wrap:wrap; align-items:center;">
        <div class="toolbar-search" style="flex:1; margin:0; min-width:200px;">
            <i data-lucide="search"></i>
            <input type="text" name="search" class="form-control" placeholder="Search exercises..." value="<?= sanitize($search) ?>">
        </div>
        <select name="category" class="form-control" style="width:auto;">
            <option value="">All Categories</option>
            <?php foreach($categories as $c): ?><option value="<?= $c['category_id'] ?>" <?= $catFilter==$c['category_id']?'selected':'' ?>><?= sanitize($c['category_name']) ?></option><?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary btn-sm" style="padding:0.5rem 1rem;">Filter</button>
        <a href="exercises.php" class="btn btn-outline btn-sm" style="padding:0.5rem 1rem;">Reset</a>
    </form>
</div>

<div class="table-container"><table class="data-table">
<thead><tr><th>Exercise</th><th>Category</th><th>Unit</th><th>Muscle Group</th><th>Difficulty</th><th>Cal/Unit</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach($exercises as $e): ?>
<tr>
    <td><strong><?= sanitize($e['exercise_name']) ?></strong></td>
    <td><span class="badge" style="background:<?= $e['color_hex'] ?>20;color:<?= $e['color_hex'] ?>"><?= sanitize($e['category_name']) ?></span></td>
    <td><?= sanitize($e['unit']) ?></td>
    <td><?= $e['muscle_group'] ? sanitize($e['muscle_group']) : '—' ?></td>
    <td><span class="badge badge-<?= match($e['difficulty']){'beginner'=>'success','intermediate'=>'warning','advanced'=>'danger'} ?>"><?= ucfirst($e['difficulty']) ?></span></td>
    <td><?= number_format($e['calories_per_unit'],1) ?></td>
    <td class="table-actions">
        <button class="btn btn-ghost btn-sm" onclick='editEx(<?= json_encode($e) ?>)'><i data-lucide="pencil"></i></button>
        <form method="POST" style="display:inline" class="ex-delete-form"><?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="exercise_id" value="<?= $e['exercise_id'] ?>"><button class="btn btn-ghost btn-sm" style="color:#ef4444"><i data-lucide="trash-2"></i></button></form>
    </td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?= renderPagination($pagination, '?search='.urlencode($search).'&category='.urlencode($catFilter)) ?>

<div class="modal-overlay" id="exModal"><div class="modal">
<div class="modal-header"><h2 id="eTitle">Add Exercise</h2><button class="modal-close" onclick="closeModal('exModal')"><i data-lucide="x"></i></button></div>
<div class="modal-body"><form method="POST" id="exForm"><?= csrfField() ?>
<input type="hidden" name="action" id="eAction" value="create"><input type="hidden" name="exercise_id" id="eId">
<div class="form-group"><label class="form-label">Name <span class="required-asterisk">*</span></label><input type="text" name="exercise_name" id="eName" class="form-control" required></div>
<div class="form-row">
    <div class="form-group"><label class="form-label">Category <span class="required-asterisk">*</span></label><select name="category_id" id="eCat" class="form-control" required>
        <?php foreach($categories as $c): ?><option value="<?= $c['category_id'] ?>"><?= sanitize($c['category_name']) ?></option><?php endforeach; ?></select></div>
    <div class="form-group"><label class="form-label">Unit</label><input type="text" name="unit" id="eUnit" class="form-control" value="minutes"></div>
</div>
<div class="form-row">
    <div class="form-group"><label class="form-label">Muscle Group</label><input type="text" name="muscle_group" id="eMuscle" class="form-control"></div>
    <div class="form-group"><label class="form-label">Difficulty</label><select name="difficulty" id="eDiff" class="form-control">
        <option value="beginner">Beginner</option><option value="intermediate">Intermediate</option><option value="advanced">Advanced</option></select></div>
</div>
<div class="form-group"><label class="form-label">Calories per Unit</label><input type="number" name="calories_per_unit" id="eCpu" class="form-control" step="0.001" min="0" value="0"></div>
<div class="form-group"><label class="form-label">Description</label><textarea name="description" id="eDesc" class="form-control" rows="2"></textarea></div>
<div class="form-actions"><button type="submit" class="btn btn-primary btn-block">Save</button></div>
</form></div></div></div>
<script>
function editEx(e){document.getElementById('eTitle').textContent='Edit Exercise';document.getElementById('eAction').value='update';document.getElementById('eId').value=e.exercise_id;document.getElementById('eName').value=e.exercise_name;document.getElementById('eCat').value=e.category_id;document.getElementById('eUnit').value=e.unit;document.getElementById('eMuscle').value=e.muscle_group||'';document.getElementById('eDiff').value=e.difficulty;document.getElementById('eCpu').value=e.calories_per_unit;document.getElementById('eDesc').value=e.description||'';openModal('exModal');}
document.getElementById('exForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    showConfirm('Save Exercise', 'Are you sure you want to save this exercise?', 'Save', 'btn-primary', () => { form.submit(); });
});
document.querySelectorAll('.ex-delete-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        showConfirm('Delete Exercise', 'This exercise will be moved to Delete Logs. You can restore it later or permanently delete it.', 'Delete', 'btn-danger', () => { this.submit(); });
    });
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
