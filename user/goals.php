<?php
$pageTitle = 'Goals';
require_once __DIR__ . '/../includes/header.php';
requireLogin();
$db = getDB(); $uid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRF()) {
    $action = $_POST['action'] ?? '';
    if ($action === 'create' || $action === 'update') {
        $d = ['goal_type'=>$_POST['goal_type'],'target_value'=>(float)$_POST['target_value'],'current_value'=>(float)($_POST['current_value']??0),
              'unit'=>$_POST['unit'],'start_date'=>$_POST['start_date'],'end_date'=>$_POST['end_date'],
              'status'=>'active','description'=>$_POST['description']?:null];

        $error = null;
        if ($d['goal_type'] === 'Lose Weight' && $d['target_value'] >= $d['current_value']) {
            $error = "For Weight Loss, your target value must be less than your current value.";
        } elseif ($d['goal_type'] === 'Gain Muscle' && $d['target_value'] <= $d['current_value']) {
            $error = "For Muscle Gain, your target value must be greater than your current value.";
        } elseif ($d['goal_type'] === 'Maintain Weight' && $d['target_value'] != $d['current_value']) {
            $error = "For Maintaining Weight, your target and current values should ideally start the same.";
        }

        if ($error) {
            setFlash('error', $error);
        } else {
            if ($action === 'create') {
                $db->prepare("INSERT INTO goals (user_id,goal_type,target_value,current_value,unit,start_date,end_date,status,description) VALUES (?,?,?,?,?,?,?,?,?)")
                    ->execute([$uid,$d['goal_type'],$d['target_value'],$d['current_value'],$d['unit'],$d['start_date'],$d['end_date'],$d['status'],$d['description']]);
                logAction($uid,'goal_create','Created goal'); setFlash('success','Goal created!');
            } else {
                $id = (int)$_POST['goal_id'];
                $db->prepare("UPDATE goals SET goal_type=?,target_value=?,current_value=?,unit=?,start_date=?,end_date=?,description=? WHERE goal_id=? AND user_id=?")
                    ->execute([$d['goal_type'],$d['target_value'],$d['current_value'],$d['unit'],$d['start_date'],$d['end_date'],$d['description'],$id,$uid]);
                logAction($uid,'goal_update',"Updated goal #$id"); setFlash('success','Goal updated!');
            }
        }
    } elseif ($action === 'complete') {
        $id = (int)$_POST['goal_id'];
        $db->prepare("UPDATE goals SET status='completed', current_value=target_value WHERE goal_id=? AND user_id=?")->execute([$id,$uid]);
        logAction($uid,'goal_complete',"Completed goal #$id"); setFlash('success','Goal marked as completed!');
    } elseif ($action === 'delete') {
        $id = (int)$_POST['goal_id'];
        $db->prepare("UPDATE goals SET deleted_at=NOW() WHERE goal_id=? AND user_id=?")->execute([$id,$uid]);
        logAction($uid,'goal_delete',"Deleted goal #$id"); setFlash('success','Goal deleted.');
    }
    redirect('/user/goals.php');
}

$progressFilter = $_GET['progress'] ?? '';
$sortOrder = $_GET['sort'] ?? 'newest';
$where = "user_id=? AND deleted_at IS NULL"; $params = [$uid];
if ($progressFilter === 'completed') { $where .= " AND status='completed'"; }
elseif ($progressFilter === 'in_progress') { $where .= " AND status IN ('active','paused')"; }
$orderBy = $sortOrder === 'oldest' ? 'created_at ASC' : 'created_at DESC';
$stmt = $db->prepare("SELECT * FROM goals WHERE $where ORDER BY $orderBy");
$stmt->execute($params); $goals = $stmt->fetchAll();

$stmt = $db->prepare("SELECT weight_kg FROM body_metrics WHERE user_id = ? ORDER BY recorded_date DESC LIMIT 1");
$stmt->execute([$uid]);
$latestWeight = $stmt->fetchColumn();
if (!$latestWeight) {
    $stmt = $db->prepare("SELECT weight_kg FROM users WHERE user_id = ?");
    $stmt->execute([$uid]);
    $latestWeight = $stmt->fetchColumn();
}
$latestWeight = $latestWeight ?: 0;
?>
<div class="section-header">
    <h2>My Goals</h2>
    <button class="btn btn-primary" onclick="resetGoalForm()"><i data-lucide="plus"></i> New Goal</button>
</div>
<div class="toolbar">
    <form method="GET" style="display:flex; gap:8px; width:100%; flex-wrap:wrap; align-items:center;">
        <div style="flex:1"></div>
        <select name="progress" class="form-control" style="width:auto;">
            <option value="" <?= $progressFilter===''?'selected':'' ?>>All Goals</option>
            <option value="in_progress" <?= $progressFilter==='in_progress'?'selected':'' ?>>In Progress</option>
            <option value="completed" <?= $progressFilter==='completed'?'selected':'' ?>>Completed</option>
        </select>
        <select name="sort" class="form-control" style="width:auto;">
            <option value="newest" <?= $sortOrder==='newest'?'selected':'' ?>>Newest First</option>
            <option value="oldest" <?= $sortOrder==='oldest'?'selected':'' ?>>Oldest First</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm" style="padding:0.5rem 1rem;">Filter</button>
        <a href="goals.php" class="btn btn-outline btn-sm" style="padding:0.5rem 1rem;">Reset</a>
    </form>
</div>

<?php if(empty($goals)): ?>
<div class="card"><div class="empty-state"><i data-lucide="target"></i><h3>No goals set</h3><p>Create your first fitness goal!</p></div></div>
<?php else: ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:16px">
<?php foreach($goals as $g): 
    // Logic for progress percentage
    $pct = 0;
    if ($g['goal_type'] === 'Lose Weight') {
        // If current <= target, it's 100%. Otherwise, it's hard to calculate without start weight. 
        // We'll estimate based on a standard 10% body weight loss goal if we don't have start weight, 
        // or just show it as a simple ratio if current < target is achieved.
        if ($g['current_value'] <= $g['target_value']) $pct = 100;
        else $pct = max(0, 100 - (($g['current_value'] - $g['target_value']) / $g['target_value'] * 100)); // Rough estimate
    } else {
        $pct = $g['target_value'] > 0 ? min(100, ($g['current_value']/$g['target_value'])*100) : 0; 
    }
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><?= sanitize($g['goal_type']) ?></h3>
        <span class="badge badge-<?= match($g['status']){'active'=>'success','completed'=>'info','expired'=>'danger',default=>'secondary'} ?>"><?= ucfirst($g['status']) ?></span>
    </div>
    <?php if($g['description']): ?><p style="color:var(--text-secondary);font-size:13px;margin-bottom:12px"><?= sanitize($g['description']) ?></p><?php endif; ?>
    <div style="margin-bottom:8px">
        <span style="font-size:24px;font-weight:800"><?= number_format($g['current_value'],1) ?></span>
        <span style="color:var(--text-muted)">/ <?= number_format($g['target_value'],1) ?> <?= sanitize($g['unit']) ?></span>
    </div>
    <div class="progress-bar"><div class="progress-fill" style="width:<?= $pct ?>%"></div></div>
    <div style="display:flex;justify-content:space-between;margin-top:8px;font-size:12px;color:var(--text-muted)">
        <span><?= formatDate($g['start_date']) ?> — <?= formatDate($g['end_date']) ?></span>
        <span><?= number_format($pct,0) ?>%</span>
    </div>
    <div class="table-actions" style="margin-top:12px">
        <?php if ($g['status'] === 'active'): ?>
        <form method="POST" style="display:inline" class="complete-goal-form">
            <?= csrfField() ?><input type="hidden" name="action" value="complete"><input type="hidden" name="goal_id" value="<?= $g['goal_id'] ?>">
            <button class="btn btn-ghost btn-sm" style="color:#22c55e" title="Mark as completed"><i data-lucide="check-circle"></i> Complete</button>
        </form>
        <?php endif; ?>
        <button class="btn btn-ghost btn-sm" onclick='editG(<?= json_encode($g) ?>)'><i data-lucide="pencil"></i> Edit</button>
        <form method="POST" style="display:inline"><?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="goal_id" value="<?= $g['goal_id'] ?>"><button class="btn btn-ghost btn-sm" style="color:#ef4444"><i data-lucide="trash-2"></i> Delete</button></form>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<div class="modal-overlay" id="goalModal"><div class="modal">
<div class="modal-header"><h2 id="gTitle">New Goal</h2><button class="modal-close" onclick="closeModal('goalModal')"><i data-lucide="x"></i></button></div>
<div class="modal-body"><form method="POST" id="goalForm"><?= csrfField() ?>
<input type="hidden" name="action" id="gAction" value="create"><input type="hidden" name="goal_id" id="gId">
<div class="form-group"><label class="form-label">Goal Type <span class="required-asterisk">*</span></label>
    <select name="goal_type" id="gType" class="form-control" required>
        <option value="" disabled selected>Select goal type...</option>
        <option value="Lose Weight">Lose Weight</option>
        <option value="Gain Muscle">Gain Muscle</option>
        <option value="Maintain Weight">Maintain Weight</option>
        <option value="Improve Fitness">Improve Fitness & Endurance</option>
        <option value="General Health">General Health & Wellness</option>
    </select>
</div>
<div class="form-row">
    <div class="form-group"><label class="form-label">Target Value <span class="required-asterisk">*</span></label><input type="number" name="target_value" id="gTarget" class="form-control" step="0.01" required></div>
    <div class="form-group"><label class="form-label">Current Value</label><input type="number" name="current_value" id="gCurrent" class="form-control" step="0.01" value="0"></div>
</div>
<div class="form-group"><label class="form-label">Unit <span class="required-asterisk">*</span></label>
    <select name="unit" id="gUnit" class="form-control" required>
        <option value="" disabled selected>Select unit...</option>
        <option value="kg">kg (Kilograms)</option>
        <option value="lbs">lbs (Pounds)</option>
        <option value="km">km (Kilometers)</option>
        <option value="miles">miles</option>
        <option value="sessions">sessions</option>
        <option value="days">days</option>
    </select>
</div>
<div class="form-row">
    <div class="form-group"><label class="form-label">Start Date <span class="required-asterisk">*</span></label><input type="date" name="start_date" id="gStart" class="form-control" required value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>"></div>
    <div class="form-group"><label class="form-label">End Date <span class="required-asterisk">*</span></label><input type="date" name="end_date" id="gEnd" class="form-control" required min="<?= date('Y-m-d') ?>"></div>
</div>
<div class="form-group"><label class="form-label">Description</label><textarea name="description" id="gDesc" class="form-control" rows="2"></textarea></div>
<div class="form-actions"><button type="submit" class="btn btn-primary btn-block">Save Goal</button></div>
</form></div></div></div>
<script>
let previousUnit = '';

function resetGoalForm() {
    document.getElementById('gTitle').textContent = 'New Goal';
    document.getElementById('gAction').value = 'create';
    document.getElementById('goalForm').reset();
    document.getElementById('gStart').value = new Date().toISOString().split('T')[0];
    previousUnit = '';
    openModal('goalModal');
}

function editG(g) {
    document.getElementById('gTitle').textContent = 'Edit Goal';
    document.getElementById('gAction').value = 'update';
    document.getElementById('gId').value = g.goal_id;
    document.getElementById('gType').value = g.goal_type;
    document.getElementById('gTarget').value = g.target_value;
    document.getElementById('gCurrent').value = g.current_value;
    document.getElementById('gUnit').value = g.unit;
    document.getElementById('gStart').value = g.start_date;
    document.getElementById('gEnd').value = g.end_date;
    document.getElementById('gDesc').value = g.description || '';
    previousUnit = g.unit;
    openModal('goalModal');
}

const currentWeight = <?= $latestWeight ?>;
document.getElementById('gType').addEventListener('change', (e) => {
    const val = e.target.value.toLowerCase();
    if (document.getElementById('gAction').value === 'create') {
        if (val.includes('weight') || val.includes('muscle')) {
            document.getElementById('gUnit').value = 'kg';
            previousUnit = 'kg';
            document.getElementById('gCurrent').value = currentWeight;
            if (val.includes('lose')) {
                document.getElementById('gTarget').placeholder = 'Must be less than ' + currentWeight;
            } else if (val.includes('gain')) {
                document.getElementById('gTarget').placeholder = 'Must be more than ' + currentWeight;
            } else {
                document.getElementById('gTarget').placeholder = 'Target weight';
            }
        } else if (val.includes('fitness')) {
            document.getElementById('gUnit').value = 'sessions';
            previousUnit = 'sessions';
            document.getElementById('gCurrent').value = 0;
            document.getElementById('gTarget').placeholder = 'e.g. 20 sessions';
        } else {
            document.getElementById('gUnit').value = 'days';
            previousUnit = 'days';
            document.getElementById('gCurrent').value = 0;
            document.getElementById('gTarget').placeholder = 'Target value';
        }
    }
});

/* Unit Auto-Conversion Logic */
document.getElementById('gUnit').addEventListener('change', function() {
    const newUnit = this.value;
    const targetInput = document.getElementById('gTarget');
    const currentInput = document.getElementById('gCurrent');
    
    let target = parseFloat(targetInput.value);
    let current = parseFloat(currentInput.value);

    // kg <-> lbs conversion (1 kg = 2.20462 lbs)
    if (previousUnit === 'kg' && newUnit === 'lbs') {
        if(!isNaN(target)) targetInput.value = (target * 2.20462).toFixed(2);
        if(!isNaN(current)) currentInput.value = (current * 2.20462).toFixed(2);
    } else if (previousUnit === 'lbs' && newUnit === 'kg') {
        if(!isNaN(target)) targetInput.value = (target / 2.20462).toFixed(2);
        if(!isNaN(current)) currentInput.value = (current / 2.20462).toFixed(2);
    }
    // km <-> miles conversion (1 km = 0.621371 miles)
    else if (previousUnit === 'km' && newUnit === 'miles') {
        if(!isNaN(target)) targetInput.value = (target * 0.621371).toFixed(2);
        if(!isNaN(current)) currentInput.value = (current * 0.621371).toFixed(2);
    } else if (previousUnit === 'miles' && newUnit === 'km') {
        if(!isNaN(target)) targetInput.value = (target / 0.621371).toFixed(2);
        if(!isNaN(current)) currentInput.value = (current / 0.621371).toFixed(2);
    }
    
    previousUnit = newUnit;
});

/* Save confirmation and validation */
document.getElementById('goalForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    const type = document.getElementById('gType').value;
    const target = parseFloat(document.getElementById('gTarget').value);
    const current = parseFloat(document.getElementById('gCurrent').value);
    
    if (type === 'Lose Weight' && target >= current) {
        alert('For Weight Loss, your target value must be less than your current value.');
        return;
    }
    if (type === 'Gain Muscle' && target <= current) {
        alert('For Muscle Gain, your target value must be greater than your current value.');
        return;
    }

    showConfirm('Save Goal', 'Are you sure you want to save this goal?', 'Save', 'btn-primary', () => {
        form.submit();
    });
});

/* Complete goal confirmation */
document.querySelectorAll('.complete-goal-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        showConfirm('Complete Goal', 'Mark this goal as completed?', 'Complete', 'btn-primary', () => {
            this.submit();
        });
    });
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
