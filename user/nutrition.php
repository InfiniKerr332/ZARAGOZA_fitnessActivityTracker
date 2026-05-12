<?php
$pageTitle = 'Nutrition';
require_once __DIR__ . '/../includes/header.php';
requireLogin();
$db = getDB();
$uid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF()) {
    $action = $_POST['action'] ?? '';
    if ($action === 'create' || $action === 'update') {
        $d = ['log_date'=>$_POST['log_date'],'meal_type'=>$_POST['meal_type'],'food_name'=>$_POST['food_name'],
              'serving_size'=>$_POST['serving_size']?:null,'calories'=>$_POST['calories']?:0,
              'protein_g'=>$_POST['protein_g']?:0,'carbs_g'=>$_POST['carbs_g']?:0,
              'fat_g'=>$_POST['fat_g']?:0,'fiber_g'=>$_POST['fiber_g']?:0,'notes'=>$_POST['notes']?:null];
        if ($action === 'create') {
            $stmt = $db->prepare("INSERT INTO nutrition_logs (user_id,log_date,meal_type,food_name,serving_size,calories,protein_g,carbs_g,fat_g,fiber_g,notes) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$uid,$d['log_date'],$d['meal_type'],$d['food_name'],$d['serving_size'],$d['calories'],$d['protein_g'],$d['carbs_g'],$d['fat_g'],$d['fiber_g'],$d['notes']]);
            logAction($uid,'nutrition_create','Logged nutrition');
            setFlash('success','Nutrition entry added!');
        } else {
            $id = (int)$_POST['nutrition_id'];
            $stmt = $db->prepare("UPDATE nutrition_logs SET log_date=?,meal_type=?,food_name=?,serving_size=?,calories=?,protein_g=?,carbs_g=?,fat_g=?,fiber_g=?,notes=? WHERE nutrition_id=? AND user_id=?");
            $stmt->execute([$d['log_date'],$d['meal_type'],$d['food_name'],$d['serving_size'],$d['calories'],$d['protein_g'],$d['carbs_g'],$d['fat_g'],$d['fiber_g'],$d['notes'],$id,$uid]);
            logAction($uid,'nutrition_update',"Updated nutrition #$id");
            setFlash('success','Entry updated!');
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['nutrition_id'];
        $db->prepare("UPDATE nutrition_logs SET deleted_at=NOW() WHERE nutrition_id=? AND user_id=?")->execute([$id,$uid]);
        logAction($uid,'nutrition_delete',"Deleted nutrition #$id");
        setFlash('success','Entry deleted.');
    } elseif ($action === 'relog') {
        $id = (int)$_POST['nutrition_id'];
        $orig = $db->prepare("SELECT * FROM nutrition_logs WHERE nutrition_id=? AND user_id=?");
        $orig->execute([$id,$uid]); $o = $orig->fetch();
        if ($o) {
            $db->prepare("INSERT INTO nutrition_logs (user_id,log_date,meal_type,food_name,serving_size,calories,protein_g,carbs_g,fat_g,fiber_g,notes) VALUES (?,CURDATE(),?,?,?,?,?,?,?,?,?)")
               ->execute([$uid,$o['meal_type'],$o['food_name'],$o['serving_size'],$o['calories'],$o['protein_g'],$o['carbs_g'],$o['fat_g'],$o['fiber_g'],$o['notes']]);
            logAction($uid,'nutrition_relog',"Re-logged nutrition #$id");
            setFlash('success','Meal re-logged for today!');
        }
    }
    redirect('/user/nutrition.php?'.http_build_query(array_filter(['search'=>$_GET['search']??'','meal'=>$_GET['meal']??'','sort'=>$_GET['sort']??''])));
}

$search = trim($_GET['search']??''); $mealFilter = $_GET['meal']??''; $sortOrder = $_GET['sort']??'latest'; $page = max(1,(int)($_GET['page']??1));
$orderBy = $sortOrder === 'oldest' ? 'log_date ASC, created_at ASC' : 'log_date DESC, created_at DESC';
$where = "user_id=? AND deleted_at IS NULL"; $params = [$uid];
if ($search) { $where .= " AND (food_name LIKE ? OR notes LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($mealFilter) { $where .= " AND meal_type=?"; $params[] = $mealFilter; }

$total = $db->prepare("SELECT COUNT(*) FROM nutrition_logs WHERE $where"); $total->execute($params); $total = $total->fetchColumn();
$pagination = paginate($total, 10, $page);
$stmt = $db->prepare("SELECT * FROM nutrition_logs WHERE $where ORDER BY $orderBy LIMIT 10 OFFSET {$pagination['offset']}");
$stmt->execute($params); $rows = $stmt->fetchAll();

// Get user's unique past foods for autocomplete (distinct food entries with avg nutrition)
$pastFoods = $db->prepare("
    SELECT food_name, serving_size,
           ROUND(AVG(calories)) as calories, ROUND(AVG(protein_g),1) as protein_g,
           ROUND(AVG(carbs_g),1) as carbs_g, ROUND(AVG(fat_g),1) as fat_g,
           ROUND(AVG(fiber_g),1) as fiber_g, COUNT(*) as times_logged
    FROM nutrition_logs WHERE user_id=? AND deleted_at IS NULL
    GROUP BY food_name, serving_size ORDER BY times_logged DESC, food_name ASC LIMIT 100
");
$pastFoods->execute([$uid]);
$pastFoodsList = $pastFoods->fetchAll();
?>
<div class="section-header">
    <h2>Nutrition Log</h2>
    <button class="btn btn-primary" onclick="resetNutritionForm()"><i data-lucide="plus"></i> Add Entry</button>
</div>
<div class="toolbar">
    <form method="GET" style="display:flex; gap:8px; width:100%; flex-wrap:wrap; align-items:center;">
        <div class="toolbar-search" style="flex:1; margin:0; min-width:200px;">
            <i data-lucide="search"></i>
            <input type="text" name="search" class="form-control" placeholder="Search foods..." value="<?= sanitize($search) ?>">
        </div>
        <select name="meal" class="form-control" style="width:auto;">
            <option value="">All Meals</option>
            <?php foreach(['breakfast','lunch','dinner','snack','supplement'] as $m): ?>
            <option value="<?= $m ?>" <?= $mealFilter===$m?'selected':'' ?>><?= ucfirst($m) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="sort" class="form-control" style="width:auto;">
            <option value="latest" <?= $sortOrder==='latest'?'selected':'' ?>>Latest First</option>
            <option value="oldest" <?= $sortOrder==='oldest'?'selected':'' ?>>Oldest First</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm" style="padding:0.5rem 1rem;">Filter</button>
        <a href="nutrition.php" class="btn btn-outline btn-sm" style="padding:0.5rem 1rem;">Reset</a>
    </form>
</div>
<?php if (empty($rows)): ?>
<div class="card"><div class="empty-state"><i data-lucide="apple"></i><h3>No nutrition entries</h3><p>Start tracking your meals!</p></div></div>
<?php else: ?>
<div class="table-container"><table class="data-table">
<thead><tr><th>Food</th><th>Meal</th><th>Date</th><th>Calories</th><th>Protein</th><th>Carbs</th><th>Fat</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach($rows as $r): ?>
<tr>
    <td><strong><?= sanitize($r['food_name']) ?></strong><?php if($r['serving_size']): ?><br><small class="text-muted"><?= sanitize($r['serving_size']) ?></small><?php endif; ?></td>
    <td><span class="badge badge-info"><?= ucfirst($r['meal_type']) ?></span></td>
    <td><?= formatDate($r['log_date']) ?></td>
    <td><?= number_format($r['calories']) ?></td>
    <td><?= number_format($r['protein_g'],1) ?>g</td>
    <td><?= number_format($r['carbs_g'],1) ?>g</td>
    <td><?= number_format($r['fat_g'],1) ?>g</td>
    <td class="table-actions">
        <form method="POST" style="display:inline" class="relog-nutrition-form">
            <?= csrfField() ?><input type="hidden" name="action" value="relog"><input type="hidden" name="nutrition_id" value="<?= $r['nutrition_id'] ?>">
            <button class="btn btn-ghost btn-sm" style="color:#22c55e" title="Re-log this meal for today"><i data-lucide="check-circle"></i></button>
        </form>
        <button class="btn btn-ghost btn-sm" onclick='editN(<?= json_encode($r) ?>)'><i data-lucide="pencil"></i></button>
        <form method="POST" style="display:inline"><?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="nutrition_id" value="<?= $r['nutrition_id'] ?>">
        <button class="btn btn-ghost btn-sm" style="color:#ef4444"><i data-lucide="trash-2"></i></button></form>
    </td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?= renderPagination($pagination, '?search='.urlencode($search).'&meal='.urlencode($mealFilter)) ?>
<?php endif; ?>

<div class="modal-overlay" id="nutritionModal"><div class="modal">
<div class="modal-header"><h2 id="nTitle">Add Nutrition</h2><button class="modal-close" onclick="closeModal('nutritionModal')"><i data-lucide="x"></i></button></div>
<div class="modal-body"><form method="POST" id="nutritionForm"><?= csrfField() ?>
<input type="hidden" name="action" id="nAction" value="create"><input type="hidden" name="nutrition_id" id="nId">
<div class="form-row">
    <div class="form-group"><label class="form-label">Date <span class="required-asterisk">*</span></label><input type="date" name="log_date" id="nDate" class="form-control" required value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>"></div>
    <div class="form-group"><label class="form-label">Meal Type <span class="required-asterisk">*</span></label><select name="meal_type" id="nMeal" class="form-control" required>
        <option value="breakfast">Breakfast</option><option value="lunch">Lunch</option><option value="dinner">Dinner</option><option value="snack">Snack</option><option value="supplement">Supplement</option></select></div>
</div>
<div class="form-row">
    <div class="form-group" style="position:relative;">
        <label class="form-label">Food Name <span class="required-asterisk">*</span></label>
        <input type="text" name="food_name" id="nFood" class="form-control" required autocomplete="off" placeholder="Type food name (e.g. Chicken Breast)">
        <div id="foodResults" style="display:none;position:absolute;z-index:100;background:var(--card-bg,#27272a);width:100%;max-height:220px;overflow-y:auto;border:1px solid #444;border-radius:6px;box-shadow:0 8px 24px rgba(0,0,0,0.4);margin-top:2px;"></div>
    </div>
    <div class="form-group"><label class="form-label">Serving Size</label><input type="text" name="serving_size" id="nServing" class="form-control" placeholder="e.g. 100g, 1 cup"></div>
</div>
<div class="form-row">
    <div class="form-group"><label class="form-label">Calories <span class="required-asterisk">*</span></label><input type="number" name="calories" id="nCal" class="form-control" step="0.01" min="0" required></div>
    <div class="form-group"><label class="form-label">Protein (g)</label><input type="number" name="protein_g" id="nPro" class="form-control" step="0.01" min="0"></div>
</div>
<div class="form-row">
    <div class="form-group"><label class="form-label">Carbs (g)</label><input type="number" name="carbs_g" id="nCarbs" class="form-control" step="0.01" min="0"></div>
    <div class="form-group"><label class="form-label">Fat (g)</label><input type="number" name="fat_g" id="nFat" class="form-control" step="0.01" min="0"></div>
</div>
<div class="form-group"><label class="form-label">Fiber (g)</label><input type="number" name="fiber_g" id="nFiber" class="form-control" step="0.01" min="0"></div>
<div class="form-group"><label class="form-label">Notes</label><textarea name="notes" id="nNotes" class="form-control" rows="2"></textarea></div>
<div class="form-actions"><button type="submit" class="btn btn-primary btn-block">Save</button></div>
</form></div></div></div>

<script>
/* ===== User's past foods + built-in common foods database ===== */
const pastFoods = <?= json_encode($pastFoodsList) ?>;

const commonFoods = [
    {food_name:'Chicken Breast (Grilled)',serving_size:'100g',calories:165,protein_g:31,carbs_g:0,fat_g:3.6,fiber_g:0},
    {food_name:'Chicken Thigh (Grilled)',serving_size:'100g',calories:209,protein_g:26,carbs_g:0,fat_g:10.9,fiber_g:0},
    {food_name:'Chicken Wings',serving_size:'100g',calories:203,protein_g:30.5,carbs_g:0,fat_g:8.1,fiber_g:0},
    {food_name:'Salmon (Baked)',serving_size:'100g',calories:208,protein_g:20,carbs_g:0,fat_g:13,fiber_g:0},
    {food_name:'Tuna (Canned in Water)',serving_size:'100g',calories:116,protein_g:25.5,carbs_g:0,fat_g:0.8,fiber_g:0},
    {food_name:'Tilapia (Baked)',serving_size:'100g',calories:128,protein_g:26,carbs_g:0,fat_g:2.7,fiber_g:0},
    {food_name:'Shrimp (Boiled)',serving_size:'100g',calories:99,protein_g:24,carbs_g:0.2,fat_g:0.3,fiber_g:0},
    {food_name:'Beef Steak (Sirloin)',serving_size:'100g',calories:206,protein_g:26,carbs_g:0,fat_g:10.6,fiber_g:0},
    {food_name:'Ground Beef (85% lean)',serving_size:'100g',calories:215,protein_g:26,carbs_g:0,fat_g:11.7,fiber_g:0},
    {food_name:'Pork Chop (Grilled)',serving_size:'100g',calories:231,protein_g:26,carbs_g:0,fat_g:13.3,fiber_g:0},
    {food_name:'Egg (Whole, Boiled)',serving_size:'1 large',calories:78,protein_g:6.3,carbs_g:0.6,fat_g:5.3,fiber_g:0},
    {food_name:'Egg Whites',serving_size:'100g',calories:52,protein_g:11,carbs_g:0.7,fat_g:0.2,fiber_g:0},
    {food_name:'White Rice (Cooked)',serving_size:'1 cup',calories:206,protein_g:4.3,carbs_g:44.5,fat_g:0.4,fiber_g:0.6},
    {food_name:'Brown Rice (Cooked)',serving_size:'1 cup',calories:216,protein_g:5,carbs_g:44.8,fat_g:1.8,fiber_g:3.5},
    {food_name:'Pasta (Cooked)',serving_size:'1 cup',calories:220,protein_g:8,carbs_g:43,fat_g:1.3,fiber_g:2.5},
    {food_name:'Sweet Potato (Baked)',serving_size:'1 medium',calories:103,protein_g:2.3,carbs_g:24,fat_g:0.1,fiber_g:3.8},
    {food_name:'Potato (Baked)',serving_size:'1 medium',calories:161,protein_g:4.3,carbs_g:36.6,fat_g:0.2,fiber_g:3.8},
    {food_name:'Oatmeal (Cooked)',serving_size:'1 cup',calories:154,protein_g:5.4,carbs_g:27.4,fat_g:2.6,fiber_g:4},
    {food_name:'Whole Wheat Bread',serving_size:'1 slice',calories:81,protein_g:4,carbs_g:13.8,fat_g:1.1,fiber_g:1.9},
    {food_name:'White Bread',serving_size:'1 slice',calories:79,protein_g:2.7,carbs_g:14.7,fat_g:1,fiber_g:0.6},
    {food_name:'Banana',serving_size:'1 medium',calories:105,protein_g:1.3,carbs_g:27,fat_g:0.4,fiber_g:3.1},
    {food_name:'Apple',serving_size:'1 medium',calories:95,protein_g:0.5,carbs_g:25,fat_g:0.3,fiber_g:4.4},
    {food_name:'Orange',serving_size:'1 medium',calories:62,protein_g:1.2,carbs_g:15.4,fat_g:0.2,fiber_g:3.1},
    {food_name:'Broccoli (Steamed)',serving_size:'1 cup',calories:55,protein_g:3.7,carbs_g:11.2,fat_g:0.6,fiber_g:5.1},
    {food_name:'Spinach (Raw)',serving_size:'1 cup',calories:7,protein_g:0.9,carbs_g:1.1,fat_g:0.1,fiber_g:0.7},
    {food_name:'Avocado',serving_size:'1 whole',calories:322,protein_g:4,carbs_g:17,fat_g:29,fiber_g:13.5},
    {food_name:'Greek Yogurt (Plain)',serving_size:'1 cup',calories:130,protein_g:22,carbs_g:8,fat_g:0.7,fiber_g:0},
    {food_name:'Milk (Whole)',serving_size:'1 cup',calories:149,protein_g:8,carbs_g:12,fat_g:8,fiber_g:0},
    {food_name:'Milk (Skim)',serving_size:'1 cup',calories:83,protein_g:8.3,carbs_g:12.2,fat_g:0.2,fiber_g:0},
    {food_name:'Cheese (Cheddar)',serving_size:'1 oz',calories:113,protein_g:7,carbs_g:0.4,fat_g:9.3,fiber_g:0},
    {food_name:'Almonds',serving_size:'1 oz (28g)',calories:164,protein_g:6,carbs_g:6,fat_g:14,fiber_g:3.5},
    {food_name:'Peanut Butter',serving_size:'2 tbsp',calories:188,protein_g:8,carbs_g:6,fat_g:16,fiber_g:2},
    {food_name:'Whey Protein Shake',serving_size:'1 scoop',calories:120,protein_g:24,carbs_g:3,fat_g:1.5,fiber_g:0},
    {food_name:'Tofu (Firm)',serving_size:'100g',calories:144,protein_g:17,carbs_g:3,fat_g:8.7,fiber_g:2.3},
    {food_name:'Lentils (Cooked)',serving_size:'1 cup',calories:230,protein_g:18,carbs_g:40,fat_g:0.8,fiber_g:15.6},
    {food_name:'Black Beans (Cooked)',serving_size:'1 cup',calories:227,protein_g:15.2,carbs_g:40.8,fat_g:0.9,fiber_g:15},
    {food_name:'Quinoa (Cooked)',serving_size:'1 cup',calories:222,protein_g:8.1,carbs_g:39.4,fat_g:3.6,fiber_g:5.2},
    {food_name:'Tortilla (Flour)',serving_size:'1 medium',calories:146,protein_g:3.8,carbs_g:24.6,fat_g:3.6,fiber_g:1.3},
    {food_name:'Granola Bar',serving_size:'1 bar',calories:190,protein_g:3,carbs_g:29,fat_g:7,fiber_g:2},
    {food_name:'Fried Chicken',serving_size:'100g',calories:246,protein_g:25,carbs_g:6.8,fat_g:13.3,fiber_g:0.3},
];

function resetNutritionForm() {
    document.getElementById('nTitle').textContent = 'Add Nutrition';
    document.getElementById('nAction').value = 'create';
    document.getElementById('nutritionForm').reset();
    document.getElementById('nDate').value = new Date().toISOString().split('T')[0];
    openModal('nutritionModal');
}

function editN(r) {
    document.getElementById('nTitle').textContent = 'Edit Entry';
    document.getElementById('nAction').value = 'update';
    document.getElementById('nId').value = r.nutrition_id;
    document.getElementById('nDate').value = r.log_date;
    document.getElementById('nMeal').value = r.meal_type;
    document.getElementById('nFood').value = r.food_name;
    document.getElementById('nServing').value = r.serving_size || '';
    document.getElementById('nCal').value = r.calories || '';
    document.getElementById('nPro').value = r.protein_g || '';
    document.getElementById('nCarbs').value = r.carbs_g || '';
    document.getElementById('nFat').value = r.fat_g || '';
    document.getElementById('nFiber').value = r.fiber_g || '';
    document.getElementById('nNotes').value = r.notes || '';
    openModal('nutritionModal');
}

function reuseN(r) {
    // Legacy — kept for compatibility but not used
}

/* ===== Food autocomplete: search user's past foods + built-in database ===== */
let foodDebounce;
document.getElementById('nFood').addEventListener('input', function(e) {
    clearTimeout(foodDebounce);
    const query = e.target.value.trim().toLowerCase();
    const resDiv = document.getElementById('foodResults');
    if (query.length < 2) { resDiv.style.display = 'none'; return; }

    foodDebounce = setTimeout(() => {
        const results = [];

        // Search user's past foods first
        pastFoods.forEach(f => {
            if (f.food_name.toLowerCase().includes(query)) {
                results.push({ ...f, source: 'history', label: `${f.food_name}`, sublabel: `Logged ${f.times_logged}x — ${f.serving_size || 'no serving'}` });
            }
        });

        // Search built-in common foods
        commonFoods.forEach(f => {
            if (f.food_name.toLowerCase().includes(query)) {
                const dup = results.find(r => r.food_name.toLowerCase() === f.food_name.toLowerCase());
                if (!dup) {
                    results.push({ ...f, source: 'database', label: f.food_name, sublabel: `${f.serving_size} — ${f.calories} kcal` });
                }
            }
        });

        if (results.length > 0) {
            resDiv.innerHTML = '';
            results.slice(0, 12).forEach(f => {
                const isHistory = f.source === 'history';
                const div = document.createElement('div');
                div.style.cssText = 'padding:10px 14px;cursor:pointer;border-bottom:1px solid rgba(255,255,255,0.06);transition:background .15s;';
                div.innerHTML = `<div style="display:flex;align-items:center;gap:8px;">
                    <span style="color:${isHistory ? '#22c55e' : '#3b82f6'};font-size:12px;">${isHistory ? '★' : '●'}</span>
                    <div><strong style="font-size:14px;">${f.label}</strong><br>
                    <small style="color:#aaa;font-size:12px;">${isHistory ? '🕒 ' : '📋 '}${f.sublabel} | Pro: ${Number(f.protein_g).toFixed(1)}g | Carbs: ${Number(f.carbs_g).toFixed(1)}g | Fat: ${Number(f.fat_g).toFixed(1)}g</small></div>
                </div>`;
                div.addEventListener('mouseover', () => div.style.background = 'rgba(255,255,255,0.06)');
                div.addEventListener('mouseout', () => div.style.background = 'transparent');
                div.addEventListener('click', () => {
                    document.getElementById('nFood').value = f.food_name;
                    document.getElementById('nServing').value = f.serving_size || '';
                    document.getElementById('nCal').value = Number(f.calories).toFixed(0);
                    document.getElementById('nPro').value = Number(f.protein_g).toFixed(1);
                    document.getElementById('nCarbs').value = Number(f.carbs_g).toFixed(1);
                    document.getElementById('nFat').value = Number(f.fat_g).toFixed(1);
                    document.getElementById('nFiber').value = Number(f.fiber_g || 0).toFixed(1);
                    resDiv.style.display = 'none';
                });
                resDiv.appendChild(div);
            });

            const manualDiv = document.createElement('div');
            manualDiv.style.cssText = 'padding:10px 14px;cursor:pointer;border-top:1px solid rgba(255,255,255,0.1);text-align:center;color:#a78bfa;font-size:13px;';
            manualDiv.innerHTML = '<i>✏️ Enter values manually for "' + e.target.value + '"</i>';
            manualDiv.addEventListener('click', () => { resDiv.style.display = 'none'; });
            resDiv.appendChild(manualDiv);

            resDiv.style.display = 'block';
        } else {
            resDiv.innerHTML = '<div style="padding:10px 14px;color:#aaa;font-size:13px;">No matches found — enter nutrition values manually below.</div>';
            resDiv.style.display = 'block';
        }
    }, 250);
});

document.addEventListener('click', (e) => {
    if (e.target.id !== 'nFood') document.getElementById('foodResults').style.display = 'none';
});

/* Save confirmation */
document.getElementById('nutritionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    showConfirm('Save Entry', 'Are you sure you want to save this nutrition entry?', 'Save', 'btn-primary', () => {
        form.submit();
    });
});

/* Re-log meal confirmation */
document.querySelectorAll('.relog-nutrition-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        showConfirm('Re-log Meal', 'Log this meal again for today?', 'Log Again', 'btn-primary', () => {
            this.submit();
        });
    });
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
