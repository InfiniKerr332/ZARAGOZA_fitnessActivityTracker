<?php
$pageTitle = 'Body Metrics';
require_once __DIR__ . '/../includes/header.php';
requireLogin();
$db = getDB(); $uid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF()) {
    $action = $_POST['action'] ?? '';
    $user = getCurrentUser();
    if ($action === 'create' || $action === 'update') {
        $w = $_POST['weight_kg'] ?: null;
        if (!$w || (float)$w <= 0) {
            setFlash('error', 'Weight is required.'); redirect('/user/body-metrics.php');
        }
        $bmi = $user['height_cm'] ? calculateBMI((float)$w, (float)$user['height_cm']) : null;
        $bf = null;
        if ($bmi && $user['date_of_birth']) {
            $age = (int) date_diff(date_create($user['date_of_birth']), date_create('now'))->format('%y');
            $bf = round((1.20 * $bmi) + (0.23 * $age) - 10.8, 1);
            $bf = max(3, min(60, $bf));
        }
        if ($action === 'create') {
            $db->prepare("INSERT INTO body_metrics (user_id,recorded_date,weight_kg,body_fat_pct,bmi,waist_cm,chest_cm,arm_cm,notes) VALUES (?,?,?,?,?,?,?,?,?)")
                ->execute([$uid,$_POST['recorded_date'],$w,$bf,$bmi,$_POST['waist_cm']?:null,$_POST['chest_cm']?:null,$_POST['arm_cm']?:null,$_POST['notes']?:null]);
            if ($w) { $db->prepare("UPDATE users SET weight_kg=? WHERE user_id=?")->execute([$w,$uid]); }
            logAction($uid,'metric_create','Logged body metric'); setFlash('success','Metric recorded!');
        } else {
            $id = (int)$_POST['metric_id'];
            $db->prepare("UPDATE body_metrics SET recorded_date=?,weight_kg=?,body_fat_pct=?,bmi=?,waist_cm=?,chest_cm=?,arm_cm=?,notes=? WHERE metric_id=? AND user_id=?")
                ->execute([$_POST['recorded_date'],$w,$bf,$bmi,$_POST['waist_cm']?:null,$_POST['chest_cm']?:null,$_POST['arm_cm']?:null,$_POST['notes']?:null,$id,$uid]);
            logAction($uid,'metric_update',"Updated metric #$id"); setFlash('success','Metric updated!');
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['metric_id'];
        $db->prepare("UPDATE body_metrics SET deleted_at=NOW() WHERE metric_id=? AND user_id=?")->execute([$id,$uid]);
        logAction($uid,'metric_delete',"Deleted metric #$id"); setFlash('success','Metric deleted.');
    }
    redirect('/user/body-metrics.php');
}

$sortOrder = $_GET['sort'] ?? 'latest';
$orderBy = $sortOrder === 'oldest' ? 'recorded_date ASC' : 'recorded_date DESC';
$rows = $db->prepare("SELECT * FROM body_metrics WHERE user_id=? AND deleted_at IS NULL ORDER BY $orderBy");
$rows->execute([$uid]); $rows = $rows->fetchAll();
$user = getCurrentUser();

$latestMetric = $rows[0] ?? null;
$defaultWeight = $latestMetric['weight_kg'] ?? $user['weight_kg'] ?? '';
$defaultFat = $latestMetric['body_fat_pct'] ?? '';
$defaultWaist = $latestMetric['waist_cm'] ?? '';
$defaultChest = $latestMetric['chest_cm'] ?? '';
$defaultArm = $latestMetric['arm_cm'] ?? '';
?>
<div class="section-header">
    <h2>Body Metrics</h2>
    <button class="btn btn-primary" onclick="openNewBM()"><i data-lucide="plus"></i> Record Metric</button>
</div>

<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-value" style="font-size:22px">
            <?= $user['height_cm'] ? number_format($user['height_cm'],1).' cm' : '<a href="profile.php" style="font-size:14px;color:var(--primary-color)">Set Height</a>' ?>
        </div>
        <div class="stat-label">Height</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="font-size:22px"><?= $user['weight_kg'] ? number_format($user['weight_kg'],1).' kg' : '—' ?></div>
        <div class="stat-label">Current Weight</div>
    </div>
    <div class="stat-card">
        <?php 
        $bmi = ($user['weight_kg'] && $user['height_cm']) ? calculateBMI((float)$user['weight_kg'],(float)$user['height_cm']) : null;
        ?>
        <div class="stat-value" style="font-size:22px"><?= $bmi ? $bmi : '—' ?></div>
        <div class="stat-label">BMI <?= $bmi ? '('.getBMICategory($bmi).')' : '' ?></div>
    </div>
</div>

<div class="toolbar">
    <form method="GET" style="display:flex; gap:8px; width:100%; flex-wrap:wrap; align-items:center;">
        <div style="flex:1"></div>
        <select name="sort" class="form-control" style="width:auto;">
            <option value="latest" <?= $sortOrder==='latest'?'selected':'' ?>>Latest First</option>
            <option value="oldest" <?= $sortOrder==='oldest'?'selected':'' ?>>Oldest First</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm" style="padding:0.5rem 1rem;">Filter</button>
        <a href="body-metrics.php" class="btn btn-outline btn-sm" style="padding:0.5rem 1rem;">Reset</a>
    </form>
</div>

<?php if(empty($rows)): ?>
<div class="card"><div class="empty-state"><i data-lucide="ruler"></i><h3>No metrics recorded</h3><p>Start tracking your body measurements!</p></div></div>
<?php else: ?>
<div class="table-container"><table class="data-table">
<thead><tr><th>Date</th><th>Weight</th><th>BMI</th><th>Body Fat</th><th>Waist</th><th>Chest</th><th>Arm</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach($rows as $r): ?>
<tr>
    <td><?= formatDate($r['recorded_date']) ?></td>
    <td><?= $r['weight_kg'] ? number_format($r['weight_kg'],1).' kg' : '—' ?></td>
    <td><?= $r['bmi'] ? number_format($r['bmi'],1) : '—' ?></td>
    <td><?= $r['body_fat_pct'] ? number_format($r['body_fat_pct'],1).'%' : '—' ?></td>
    <td><?= $r['waist_cm'] ? number_format($r['waist_cm'],1).' cm' : '—' ?></td>
    <td><?= $r['chest_cm'] ? number_format($r['chest_cm'],1).' cm' : '—' ?></td>
    <td><?= $r['arm_cm'] ? number_format($r['arm_cm'],1).' cm' : '—' ?></td>
    <td class="table-actions">
        <button class="btn btn-ghost btn-sm" onclick='editBM(<?= json_encode($r) ?>)'><i data-lucide="pencil"></i></button>
        <form method="POST" style="display:inline" onsubmit="return confirm('Delete?')"><?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="metric_id" value="<?= $r['metric_id'] ?>"><button class="btn btn-ghost btn-sm" style="color:#ef4444"><i data-lucide="trash-2"></i></button></form>
    </td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>

<div class="modal-overlay" id="bmModal"><div class="modal">
<div class="modal-header"><h2 id="bmTitle">Record Metric</h2><button class="modal-close" onclick="closeModal('bmModal')"><i data-lucide="x"></i></button></div>
<div class="modal-body"><form method="POST" id="bmForm"><?= csrfField() ?>
<input type="hidden" name="action" id="bmAction" value="create"><input type="hidden" name="metric_id" id="bmId">
<input type="hidden" name="bmi" id="bmBmi"><input type="hidden" name="body_fat_pct" id="bmFat">
<div class="form-group"><label class="form-label">Date <span class="required-asterisk">*</span></label><input type="date" name="recorded_date" id="bmDate" class="form-control" required value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>"></div>
<div class="form-group"><label class="form-label">Weight (kg) <span class="required-asterisk">*</span></label><input type="number" name="weight_kg" id="bmWeight" class="form-control" step="0.01" min="1" required placeholder="Enter your weight"></div>
<div id="bmComputedValues" style="display:none;margin-bottom:16px">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <div style="background:rgba(69,93,211,0.08);border:1px solid rgba(69,93,211,0.2);border-radius:10px;padding:14px;text-align:center">
            <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);margin-bottom:4px">BMI</div>
            <div id="bmBmiDisplay" style="font-size:24px;font-weight:800;color:#455DD3">—</div>
            <div id="bmBmiCategory" style="font-size:11px;margin-top:2px"></div>
        </div>
        <div style="background:rgba(34,197,94,0.08);border:1px solid rgba(34,197,94,0.2);border-radius:10px;padding:14px;text-align:center">
            <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);margin-bottom:4px">Est. Body Fat</div>
            <div id="bmFatDisplay" style="font-size:24px;font-weight:800;color:#22c55e">—</div>
            <div style="font-size:11px;color:var(--text-secondary);margin-top:2px">Deurenberg est.</div>
        </div>
    </div>
</div>
<div class="form-row">
    <div class="form-group"><label class="form-label">Waist (cm)</label><input type="number" name="waist_cm" id="bmWaist" class="form-control" step="0.01" min="0" placeholder="Optional"></div>
    <div class="form-group"><label class="form-label">Chest (cm)</label><input type="number" name="chest_cm" id="bmChest" class="form-control" step="0.01" min="0" placeholder="Optional"></div>
</div>
<div class="form-group"><label class="form-label">Arm (cm)</label><input type="number" name="arm_cm" id="bmArm" class="form-control" step="0.01" min="0" placeholder="Optional"></div>
<div class="form-group"><label class="form-label">Notes</label><textarea name="notes" id="bmNotes" class="form-control" rows="2"></textarea></div>
<div class="form-actions"><button type="submit" class="btn btn-primary btn-block">Save</button></div>
</form></div></div></div>
<script>
const userHeight = <?= (float)($user['height_cm'] ?? 0) ?>;
const userAge = <?= $user['date_of_birth'] ? (int)date_diff(date_create($user['date_of_birth']), date_create('now'))->format('%y') : 0 ?>;

function calcBodyMetrics() {
    const w = parseFloat(document.getElementById('bmWeight').value) || 0;
    const el = document.getElementById('bmComputedValues');
    
    // Always show the computed box if there is a weight
    if (w > 0) {
        el.style.display = 'block';
        
        if (userHeight > 0) {
            const hm = userHeight / 100;
            const bmi = w / (hm * hm);
            let cat = 'Obese', catColor = '#ef4444';
            if (bmi < 18.5) { cat = 'Underweight'; catColor = '#f59e0b'; }
            else if (bmi < 25) { cat = 'Normal'; catColor = '#22c55e'; }
            else if (bmi < 30) { cat = 'Overweight'; catColor = '#f59e0b'; }
            
            let bf = (1.20 * bmi) + (0.23 * userAge) - 10.8;
            bf = Math.max(3, Math.min(60, bf));
            
            document.getElementById('bmBmiDisplay').textContent = bmi.toFixed(1);
            const catEl = document.getElementById('bmBmiCategory');
            catEl.textContent = cat; catEl.style.color = catColor;
            
            document.getElementById('bmFatDisplay').textContent = bf.toFixed(1) + '%';
            
            document.getElementById('bmBmi').value = bmi.toFixed(2);
            document.getElementById('bmFat').value = bf.toFixed(1);
        } else {
            // Missing height
            document.getElementById('bmBmiDisplay').textContent = '—';
            const catEl = document.getElementById('bmBmiCategory');
            catEl.textContent = 'Set height in profile'; catEl.style.color = '#ef4444';
            
            document.getElementById('bmFatDisplay').textContent = '—';
            
            document.getElementById('bmBmi').value = '';
            document.getElementById('bmFat').value = '';
        }
    } else {
        el.style.display = 'none';
        document.getElementById('bmBmi').value = '';
        document.getElementById('bmFat').value = '';
    }
}
document.getElementById('bmWeight').addEventListener('input', calcBodyMetrics);

function openNewBM() {
    document.getElementById('bmTitle').textContent = 'Record Metric';
    document.getElementById('bmAction').value = 'create';
    document.getElementById('bmForm').reset();
    document.getElementById('bmDate').value = new Date().toISOString().split('T')[0];
    document.getElementById('bmWeight').value = '<?= $defaultWeight ?>';
    document.getElementById('bmWaist').value = '<?= $defaultWaist ?>';
    document.getElementById('bmChest').value = '<?= $defaultChest ?>';
    document.getElementById('bmArm').value = '<?= $defaultArm ?>';
    calcBodyMetrics();
    openModal('bmModal');
}
function editBM(r) {
    document.getElementById('bmTitle').textContent = 'Edit Metric';
    document.getElementById('bmAction').value = 'update';
    document.getElementById('bmId').value = r.metric_id;
    document.getElementById('bmDate').value = r.recorded_date;
    document.getElementById('bmWeight').value = r.weight_kg || '';
    document.getElementById('bmWaist').value = r.waist_cm || '';
    document.getElementById('bmChest').value = r.chest_cm || '';
    document.getElementById('bmArm').value = r.arm_cm || '';
    document.getElementById('bmNotes').value = r.notes || '';
    calcBodyMetrics();
    openModal('bmModal');
}

/* Save confirmation */
document.getElementById('bmForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    showConfirm('Save Metric', 'Are you sure you want to save this body metric?', 'Save', 'btn-primary', () => {
        form.submit();
    });
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
