<?php
$pageTitle = 'Categories';
require_once __DIR__ . '/../includes/header.php';
requireAdmin();
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF()) {
    $action = $_POST['action']??'';
    if ($action === 'create') {
        $db->prepare("INSERT INTO activity_categories (category_name,icon,color_hex,description) VALUES (?,?,?,?)")
            ->execute([trim($_POST['category_name']),$_POST['icon']??'activity',$_POST['color_hex']??'#00D4AA',$_POST['description']?:null]);
        logAction($_SESSION['user_id'],'category_create','Created category'); setFlash('success','Category created!');
    } elseif ($action === 'update') {
        $id = (int)$_POST['category_id'];
        $db->prepare("UPDATE activity_categories SET category_name=?,icon=?,color_hex=?,description=? WHERE category_id=?")
            ->execute([trim($_POST['category_name']),$_POST['icon']??'activity',$_POST['color_hex']??'#00D4AA',$_POST['description']?:null,$id]);
        logAction($_SESSION['user_id'],'category_update',"Updated category #$id"); setFlash('success','Category updated!');
    } elseif ($action === 'delete') {
        $id = (int)$_POST['category_id'];
        $db->prepare("UPDATE activity_categories SET deleted_at=NOW() WHERE category_id=?")->execute([$id]);
        logAction($_SESSION['user_id'],'category_delete',"Deleted category #$id"); setFlash('success','Category deleted.');
    }
    redirect('/admin/categories.php?search=' . urlencode($_GET['search'] ?? ''));
}

$search = trim($_GET['search'] ?? '');
$where = "c.deleted_at IS NULL"; $params = [];
if ($search) {
    $where .= " AND (c.category_name LIKE ? OR c.description LIKE ? OR c.icon LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}
$stmt = $db->prepare("SELECT c.*, (SELECT COUNT(*) FROM exercise_types WHERE category_id=c.category_id AND deleted_at IS NULL) as exercise_count FROM activity_categories c WHERE $where ORDER BY c.category_name");
$stmt->execute($params); $cats = $stmt->fetchAll();
?>
<div class="section-header"><h2>Activity Categories</h2>
    <button class="btn btn-primary" onclick="document.getElementById('cAction').value='create';document.getElementById('catForm').reset();openModal('catModal')"><i data-lucide="plus"></i> Add Category</button>
</div>

<!-- Unified Toolbar -->
<div class="toolbar">
    <form method="GET" style="display:flex; gap:8px; width:100%; flex-wrap:wrap; align-items:center;">
        <div class="toolbar-search" style="flex:1; margin:0; min-width:200px;">
            <i data-lucide="search"></i>
            <input type="text" name="search" class="form-control" placeholder="Search categories..." value="<?= sanitize($search) ?>">
        </div>
        <button type="submit" class="btn btn-primary btn-sm" style="padding:0.5rem 1rem;">Filter</button>
        <a href="categories.php" class="btn btn-outline btn-sm" style="padding:0.5rem 1rem;">Reset</a>
    </form>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
<?php foreach($cats as $c): ?>
<div class="card">
    <div class="d-flex align-center gap-1" style="margin-bottom:12px">
        <div class="stat-icon" style="background:<?= $c['color_hex'] ?>20;color:<?= $c['color_hex'] ?>;width:40px;height:40px"><i data-lucide="<?= sanitize($c['icon']) ?>"></i></div>
        <div><strong><?= sanitize($c['category_name']) ?></strong><br><small style="color:var(--text-muted)"><?= $c['exercise_count'] ?> exercises</small></div>
    </div>
    <?php if($c['description']): ?><p style="font-size:13px;color:var(--text-secondary);margin-bottom:12px"><?= sanitize($c['description']) ?></p><?php endif; ?>
    <div class="table-actions">
        <button class="btn btn-ghost btn-sm" onclick='editCat(<?= json_encode($c) ?>)'><i data-lucide="pencil"></i> Edit</button>
        <form method="POST" style="display:inline" class="cat-delete-form"><?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="category_id" value="<?= $c['category_id'] ?>"><button class="btn btn-ghost btn-sm" style="color:#ef4444"><i data-lucide="trash-2"></i></button></form>
    </div>
</div>
<?php endforeach; ?>
</div>

<div class="modal-overlay" id="catModal"><div class="modal">
<div class="modal-header"><h2 id="cTitle">Add Category</h2><button class="modal-close" onclick="closeModal('catModal')"><i data-lucide="x"></i></button></div>
<div class="modal-body"><form method="POST" id="catForm"><?= csrfField() ?>
<input type="hidden" name="action" id="cAction" value="create"><input type="hidden" name="category_id" id="cId">
<div class="form-group"><label class="form-label">Name <span class="required-asterisk">*</span></label><input type="text" name="category_name" id="cName" class="form-control" required></div>
<div class="form-row">
    <div class="form-group"><label class="form-label">Icon (Lucide)</label><input type="text" name="icon" id="cIcon" class="form-control" value="activity" placeholder="activity"></div>
    <div class="form-group"><label class="form-label">Color</label><input type="color" name="color_hex" id="cColor" class="form-control" value="#00D4AA" style="height:42px;padding:4px"></div>
</div>
<div class="form-group"><label class="form-label">Description</label><textarea name="description" id="cDesc" class="form-control" rows="2"></textarea></div>
<div class="form-actions"><button type="submit" class="btn btn-primary btn-block">Save</button></div>
</form></div></div></div>
<script>
function editCat(c){document.getElementById('cTitle').textContent='Edit Category';document.getElementById('cAction').value='update';document.getElementById('cId').value=c.category_id;document.getElementById('cName').value=c.category_name;document.getElementById('cIcon').value=c.icon;document.getElementById('cColor').value=c.color_hex;document.getElementById('cDesc').value=c.description||'';openModal('catModal');}
document.getElementById('catForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    showConfirm('Save Category', 'Are you sure you want to save this category?', 'Save', 'btn-primary', () => { form.submit(); });
});
document.querySelectorAll('.cat-delete-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        showConfirm('Delete Category', 'This category will be moved to Delete Logs. You can restore it later or permanently delete it.', 'Delete', 'btn-danger', () => { this.submit(); });
    });
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
