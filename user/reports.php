<?php
$pageTitle = 'Reports';
$needsChart = true;
require_once __DIR__ . '/../includes/header.php';
requireLogin();
$db = getDB(); $uid = $_SESSION['user_id'];

// === CTE: Monthly Activity Summary with Running Totals ===
$monthlyCTE = $db->prepare("
    WITH monthly_stats AS (
        SELECT 
            DATE_FORMAT(a.activity_date, '%Y-%m') AS month,
            COUNT(*) AS workout_count,
            SUM(a.calories_burned) AS total_calories,
            SUM(a.duration_minutes) AS total_duration,
            AVG(a.calories_burned) AS avg_calories
        FROM activities a
        WHERE a.user_id = ? AND a.deleted_at IS NULL AND a.status = 'completed'
        GROUP BY DATE_FORMAT(a.activity_date, '%Y-%m')
    )
    SELECT 
        month,
        workout_count,
        COALESCE(total_calories, 0) AS total_calories,
        COALESCE(total_duration, 0) AS total_duration,
        COALESCE(avg_calories, 0) AS avg_calories,
        SUM(workout_count) OVER (ORDER BY month) AS cumulative_workouts,
        SUM(total_calories) OVER (ORDER BY month) AS cumulative_calories
    FROM monthly_stats
    ORDER BY month DESC
    LIMIT 12
");
$monthlyCTE->execute([$uid]);
$monthlyData = $monthlyCTE->fetchAll();

// === SUBQUERY 1: Exercises where user burns more than their average ===
$aboveAvg = $db->prepare("
    SELECT e.exercise_name, c.category_name, COUNT(*) as times,
           AVG(a.calories_burned) as avg_cal
    FROM activities a
    JOIN exercise_types e ON a.exercise_id = e.exercise_id
    JOIN activity_categories c ON e.category_id = c.category_id
    WHERE a.user_id = ? AND a.deleted_at IS NULL AND a.status = 'completed'
    AND a.calories_burned > (
        SELECT AVG(calories_burned) FROM activities 
        WHERE user_id = ? AND deleted_at IS NULL AND status = 'completed' AND calories_burned > 0
    )
    GROUP BY e.exercise_id
    ORDER BY avg_cal DESC LIMIT 10
");
$aboveAvg->execute([$uid, $uid]);
$aboveAvgRows = $aboveAvg->fetchAll();

// === SUBQUERY 2: Days where calorie intake exceeded burned ===
$calorieDays = $db->prepare("
    SELECT n.log_date, n.total_intake, COALESCE(a.total_burned, 0) as total_burned,
           (n.total_intake - COALESCE(a.total_burned, 0)) as surplus
    FROM (
        SELECT log_date, SUM(calories) as total_intake 
        FROM nutrition_logs WHERE user_id = ? AND deleted_at IS NULL GROUP BY log_date
    ) n
    LEFT JOIN (
        SELECT activity_date, SUM(calories_burned) as total_burned 
        FROM activities WHERE user_id = ? AND deleted_at IS NULL AND status = 'completed' GROUP BY activity_date
    ) a ON n.log_date = a.activity_date
    ORDER BY n.log_date DESC LIMIT 14
");
$calorieDays->execute([$uid, $uid]);
$calorieDayRows = $calorieDays->fetchAll();

// === SUBQUERY 3: User ranking by total calories across all users ===
$ranking = $db->prepare("
    SELECT ranked.rank_pos, ranked.total_cal, ranked.total_users
    FROM (
        SELECT u.user_id,
               SUM(a.calories_burned) as total_cal,
               RANK() OVER (ORDER BY SUM(a.calories_burned) DESC) as rank_pos,
               (SELECT COUNT(*) FROM users WHERE deleted_at IS NULL) as total_users
        FROM users u
        LEFT JOIN activities a ON u.user_id = a.user_id AND a.deleted_at IS NULL AND a.status = 'completed'
        WHERE u.deleted_at IS NULL
        GROUP BY u.user_id
    ) ranked
    WHERE ranked.user_id = ?
");
$ranking->execute([$uid]);
$rank = $ranking->fetch();

// === AGGREGATION: Category breakdown ===
$catBreakdown = $db->prepare("
    SELECT c.category_name, c.color_hex,
           COUNT(*) as count, SUM(a.calories_burned) as calories,
           SUM(a.duration_minutes) as duration,
           AVG(a.calories_burned) as avg_cal
    FROM activities a
    JOIN exercise_types e ON a.exercise_id = e.exercise_id
    JOIN activity_categories c ON e.category_id = c.category_id
    WHERE a.user_id = ? AND a.deleted_at IS NULL AND a.status = 'completed'
    GROUP BY c.category_id
    ORDER BY count DESC
");
$catBreakdown->execute([$uid]);
$catRows = $catBreakdown->fetchAll();

// Nutrition weekly summary
$weeklyNutrition = $db->prepare("
    SELECT AVG(daily_cal) as avg_cal, AVG(daily_protein) as avg_protein, 
           AVG(daily_carbs) as avg_carbs, AVG(daily_fat) as avg_fat
    FROM (
        SELECT log_date, SUM(calories) as daily_cal, SUM(protein_g) as daily_protein,
               SUM(carbs_g) as daily_carbs, SUM(fat_g) as daily_fat
        FROM nutrition_logs WHERE user_id = ? AND deleted_at IS NULL 
        AND log_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY log_date
    ) daily
");
$weeklyNutrition->execute([$uid]);
$wn = $weeklyNutrition->fetch();

// Body Metrics Overview
$metricsStmt = $db->prepare("SELECT * FROM body_metrics WHERE user_id=? AND deleted_at IS NULL ORDER BY recorded_date DESC LIMIT 5");
$metricsStmt->execute([$uid]);
$metricsRows = $metricsStmt->fetchAll();

// Goals Overview
$goalsStmt = $db->prepare("SELECT * FROM goals WHERE user_id=? AND deleted_at IS NULL ORDER BY created_at DESC");
$goalsStmt->execute([$uid]);
$goalsRows = $goalsStmt->fetchAll();


// Chart: monthly trends
$mLabels = []; $mCals = []; $mWorkouts = [];
foreach(array_reverse($monthlyData) as $m) {
    $mLabels[] = date('M Y', strtotime($m['month'].'-01'));
    $mCals[] = (float)$m['total_calories'];
    $mWorkouts[] = (int)$m['workout_count'];
}
?>

<div class="section-header">
    <h2>Reports & Insights</h2>
    <button class="btn btn-primary" onclick="openModal('exportModal')"><i data-lucide="download"></i> Download Report</button>
</div>

<!-- Overview Stats -->
<?php if($rank): ?>
<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));margin-bottom:24px">
    <div class="stat-card"><div class="stat-icon purple"><i data-lucide="trophy"></i></div>
        <div class="stat-value">#<?= $rank['rank_pos'] ?></div><div class="stat-label">Your Ranking (of <?= $rank['total_users'] ?> users)</div></div>
    <div class="stat-card"><div class="stat-icon orange"><i data-lucide="zap"></i></div>
        <div class="stat-value"><?= formatNumber($rank['total_cal'] ?? 0) ?></div><div class="stat-label">Total Calories Burned</div></div>
    <div class="stat-card"><div class="stat-icon green"><i data-lucide="utensils"></i></div>
        <div class="stat-value"><?= $wn['avg_cal'] ? number_format($wn['avg_cal'],0) : '—' ?></div><div class="stat-label">Avg Daily Intake (7d)</div></div>
    <div class="stat-card"><div class="stat-icon blue"><i data-lucide="dumbbell"></i></div>
        <div class="stat-value"><?= !empty($monthlyData) ? $monthlyData[0]['workout_count'] : '0' ?></div><div class="stat-label">Workouts This Month</div></div>
</div>
<?php endif; ?>

<!-- Charts -->
<div class="charts-grid">
    <div class="card">
        <div class="card-header"><h3 class="card-title"><i data-lucide="trending-up" style="width:18px;height:18px;color:var(--accent)"></i> Monthly Trend</h3></div>
        <div class="chart-container"><canvas id="trendChart"></canvas></div>
    </div>
    <div class="card">
        <div class="card-header"><h3 class="card-title"><i data-lucide="pie-chart" style="width:18px;height:18px;color:var(--accent)"></i> Category Breakdown</h3></div>
        <?php if($catRows): ?>
        <div class="chart-container"><canvas id="catChart"></canvas></div>
        <?php else: ?><div class="empty-state"><p>No activity data yet.</p></div><?php endif; ?>
    </div>
</div>

<!-- Monthly Summary -->
<div class="card" style="margin-top:20px">
    <div class="card-header"><h3 class="card-title">Monthly Summary</h3></div>
    <?php if(empty($monthlyData)): ?>
    <div class="empty-state"><p>No data available. Start logging activities!</p></div>
    <?php else: ?>
    <div class="table-container"><table class="data-table">
    <thead><tr><th>Month</th><th>Workouts</th><th>Calories</th><th>Duration</th><th>Avg Cal</th><th>Total Workouts</th><th>Total Calories</th></tr></thead>
    <tbody>
    <?php foreach($monthlyData as $m): ?>
    <tr>
        <td><strong><?= date('M Y', strtotime($m['month'].'-01')) ?></strong></td>
        <td><?= $m['workout_count'] ?></td>
        <td><?= number_format($m['total_calories'],0) ?> kcal</td>
        <td><?= number_format($m['total_duration'],0) ?> min</td>
        <td><?= number_format($m['avg_calories'],0) ?></td>
        <td><?= $m['cumulative_workouts'] ?></td>
        <td><?= number_format($m['cumulative_calories'],0) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody></table></div>
    <?php endif; ?>
</div>

<!-- Top Exercises -->
<div class="card" style="margin-top:20px">
    <div class="card-header"><h3 class="card-title">Top Exercises (Above Average Burn)</h3></div>
    <?php if(empty($aboveAvgRows)): ?>
    <div class="empty-state"><p>Log more activities to see insights.</p></div>
    <?php else: ?>
    <div class="table-container"><table class="data-table">
    <thead><tr><th>Exercise</th><th>Category</th><th>Times Done</th><th>Avg Calories</th></tr></thead>
    <tbody>
    <?php foreach($aboveAvgRows as $r): ?>
    <tr><td><strong><?= sanitize($r['exercise_name']) ?></strong></td><td><?= sanitize($r['category_name']) ?></td><td><?= $r['times'] ?></td><td><?= number_format($r['avg_cal'],0) ?> kcal</td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
    <?php endif; ?>
</div>

<!-- Calorie Balance -->
<div class="card" style="margin-top:20px">
    <div class="card-header"><h3 class="card-title">Daily Calorie Balance (Intake vs Burned)</h3></div>
    <?php if(empty($calorieDayRows)): ?>
    <div class="empty-state"><p>Log nutrition and activities to see your balance.</p></div>
    <?php else: ?>
    <div class="table-container"><table class="data-table">
    <thead><tr><th>Date</th><th>Intake</th><th>Burned</th><th>Balance</th></tr></thead>
    <tbody>
    <?php foreach($calorieDayRows as $r): $surplus = (float)$r['surplus']; ?>
    <tr>
        <td><?= formatDate($r['log_date']) ?></td>
        <td><?= number_format($r['total_intake'],0) ?> kcal</td>
        <td><?= number_format($r['total_burned'],0) ?> kcal</td>
        <td><span class="badge badge-<?= $surplus > 0 ? 'warning' : 'success' ?>"><?= ($surplus>0?'+':'').number_format($surplus,0) ?> kcal</span></td>
    </tr>
    <?php endforeach; ?>
    </tbody></table></div>
    <?php endif; ?>
</div>

<!-- Body Metrics Overview -->
<div class="card" style="margin-top:20px">
    <div class="card-header"><h3 class="card-title">Body Metrics Overview</h3></div>
    <?php if(empty($metricsRows)): ?>
    <div class="empty-state"><p>No body metrics logged yet.</p></div>
    <?php else: ?>
    <div class="table-container"><table class="data-table">
    <thead><tr><th>Date</th><th>Weight</th><th>BMI</th><th>Body Fat</th><th>Waist</th></tr></thead>
    <tbody>
    <?php foreach($metricsRows as $m): ?>
    <tr>
        <td><strong><?= formatDate($m['recorded_date']) ?></strong></td>
        <td><?= $m['weight_kg'] ? number_format($m['weight_kg'],1).' kg' : '—' ?></td>
        <td><?= $m['bmi'] ? number_format($m['bmi'],1) : '—' ?></td>
        <td><?= $m['body_fat_pct'] ? number_format($m['body_fat_pct'],1).'%' : '—' ?></td>
        <td><?= $m['waist_cm'] ? number_format($m['waist_cm'],1).' cm' : '—' ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody></table></div>
    <?php endif; ?>
</div>

<!-- Goals Overview -->
<div class="card" style="margin-top:20px">
    <div class="card-header"><h3 class="card-title">Goals Overview</h3></div>
    <?php if(empty($goalsRows)): ?>
    <div class="empty-state"><p>No active goals.</p></div>
    <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;padding:20px;">
    <?php foreach($goalsRows as $g): 
        $pct = 0;
        if ($g['goal_type'] === 'Lose Weight') {
            if ($g['current_value'] <= $g['target_value']) $pct = 100;
            else $pct = max(0, 100 - (($g['current_value'] - $g['target_value']) / $g['target_value'] * 100));
        } else {
            $pct = $g['target_value'] > 0 ? min(100, ($g['current_value']/$g['target_value'])*100) : 0; 
        }
    ?>
    <div style="background:var(--bg-lighter);border:1px solid var(--border-color);border-radius:12px;padding:16px;">
        <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
            <strong><?= sanitize($g['goal_type']) ?></strong>
            <span class="badge badge-<?= match($g['status']){'active'=>'success','completed'=>'info','expired'=>'danger',default=>'secondary'} ?>"><?= ucfirst($g['status']) ?></span>
        </div>
        <div style="font-size:20px;font-weight:700;margin-bottom:12px;">
            <?= number_format($g['current_value'],1) ?> <span style="font-size:14px;color:var(--text-muted);font-weight:400;">/ <?= number_format($g['target_value'],1) ?> <?= sanitize($g['unit']) ?></span>
        </div>
        <div class="progress-bar" style="height:6px;"><div class="progress-fill" style="width:<?= $pct ?>%"></div></div>
        <div style="text-align:right;font-size:11px;color:var(--text-muted);margin-top:4px;"><?= number_format($pct,0) ?>% Complete</div>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Export Modal — Report Type Selection -->
<div class="modal-overlay" id="exportModal"><div class="modal" style="max-width:640px">
<div class="modal-header"><h2>Download Report</h2><button class="modal-close" onclick="closeModal('exportModal')"><i data-lucide="x"></i></button></div>
<div class="modal-body">
    <p style="color:var(--text-secondary);margin-bottom:16px;">Choose a report to download.</p>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div class="card" style="cursor:pointer;padding:20px;text-align:center;transition:all .2s;" onclick="selectUserReport('monthly')" onmouseover="this.style.borderColor='var(--accent)'" onmouseout="this.style.borderColor='transparent'">
            <i data-lucide="calendar" style="width:32px;height:32px;color:var(--accent);margin-bottom:8px;display:block;margin-left:auto;margin-right:auto;"></i>
            <strong>Monthly Summary</strong>
            <p style="font-size:12px;color:var(--text-muted);margin-top:4px;">Workouts, calories, duration by month</p>
        </div>
        <div class="card" style="cursor:pointer;padding:20px;text-align:center;transition:all .2s;" onclick="selectUserReport('calorie_balance')" onmouseover="this.style.borderColor='var(--accent)'" onmouseout="this.style.borderColor='transparent'">
            <i data-lucide="scale" style="width:32px;height:32px;color:#f59e0b;margin-bottom:8px;display:block;margin-left:auto;margin-right:auto;"></i>
            <strong>Calorie Balance</strong>
            <p style="font-size:12px;color:var(--text-muted);margin-top:4px;">Daily intake vs calories burned</p>
        </div>
        <div class="card" style="cursor:pointer;padding:20px;text-align:center;transition:all .2s;" onclick="selectUserReport('activities')" onmouseover="this.style.borderColor='var(--accent)'" onmouseout="this.style.borderColor='transparent'">
            <i data-lucide="dumbbell" style="width:32px;height:32px;color:#22c55e;margin-bottom:8px;display:block;margin-left:auto;margin-right:auto;"></i>
            <strong>All Activities</strong>
            <p style="font-size:12px;color:var(--text-muted);margin-top:4px;">Complete workout history</p>
        </div>
        <div class="card" style="cursor:pointer;padding:20px;text-align:center;transition:all .2s;" onclick="selectUserReport('nutrition')" onmouseover="this.style.borderColor='var(--accent)'" onmouseout="this.style.borderColor='transparent'">
            <i data-lucide="apple" style="width:32px;height:32px;color:#ef4444;margin-bottom:8px;display:block;margin-left:auto;margin-right:auto;"></i>
            <strong>Nutrition Log</strong>
            <p style="font-size:12px;color:var(--text-muted);margin-top:4px;">All meals and macros</p>
        </div>
    </div>
</div></div></div>

<!-- Download Format Modal -->
<div class="modal-overlay" id="userFormatModal"><div class="modal" style="max-width:420px">
<div class="modal-header"><h2 id="userFormatTitle">Download Report</h2><button class="modal-close" onclick="closeModal('userFormatModal')"><i data-lucide="x"></i></button></div>
<div class="modal-body" style="text-align:center;padding:24px 30px;">
    <p style="color:var(--text-secondary);margin-bottom:20px;">Choose your download format:</p>
    <div style="display:flex;gap:12px;justify-content:center;">
        <button class="btn btn-primary" onclick="downloadUserReport('csv')"><i data-lucide="file-text"></i> Download CSV</button>
        <button class="btn btn-outline" onclick="downloadUserReport('pdf')"><i data-lucide="printer"></i> Print / PDF</button>
    </div>
    <button class="btn btn-ghost btn-sm" onclick="closeModal('userFormatModal');openModal('exportModal')" style="margin-top:16px;">← Back to reports</button>
</div></div></div>

<script>
let userReportType = '';
const userTitles = { monthly:'Monthly Summary', calorie_balance:'Calorie Balance', activities:'All Activities', nutrition:'Nutrition Log' };

function selectUserReport(type) {
    userReportType = type;
    closeModal('exportModal');
    document.getElementById('userFormatTitle').textContent = userTitles[type] + ' — Download';
    openModal('userFormatModal');
    if (typeof initLucideIcons === 'function') initLucideIcons();
}

function downloadUserReport(format) {
    const url = `<?= BASE_URL ?>/user/export.php?type=${userReportType}&format=${format}`;
    if (format === 'pdf') {
        window.open(url, '_blank');
    } else {
        window.location.href = url;
    }
    closeModal('userFormatModal');
}

// Initialize charts — wrapped in try/catch so errors don't block icons/buttons
(function initCharts() {
    function buildCharts() {
        try {
            if (typeof Chart === 'undefined') { console.warn('Chart.js not loaded'); return; }
            const trendEl = document.getElementById('trendChart');
            if (!trendEl) return;
            const lineOpts = {responsive:true,maintainAspectRatio:false,plugins:{legend:{labels:{color:'#a0a0a0'}}},scales:{x:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{color:'#666'}},y:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{color:'#666'}}}};
            new Chart(trendEl,{type:'line',data:{labels:<?= json_encode($mLabels) ?>,datasets:[
                {label:'Calories',data:<?= json_encode($mCals) ?>,borderColor:'#455DD3',backgroundColor:'rgba(69,93,211,0.1)',fill:true,tension:.3},
                {label:'Workouts',data:<?= json_encode($mWorkouts) ?>,borderColor:'#0075DE',backgroundColor:'rgba(0,117,222,0.1)',fill:true,tension:.3,yAxisID:'y1'}
            ]},options:{...lineOpts,scales:{...lineOpts.scales,y1:{position:'right',grid:{display:false},ticks:{color:'#666'}}}}});
            <?php if($catRows): ?>
            const catEl = document.getElementById('catChart');
            if (catEl) {
                new Chart(catEl,{type:'polarArea',data:{labels:<?= json_encode(array_column($catRows,'category_name')) ?>,datasets:[{data:<?= json_encode(array_map(fn($r)=>(int)$r['count'],$catRows)) ?>,backgroundColor:<?= json_encode(array_map(fn($r)=>$r['color_hex'].'80',$catRows)) ?>}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{color:'#a0a0a0'}}}}});
            }
            <?php endif; ?>
        } catch(e) { console.error('Chart init error:', e); }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', buildCharts);
    } else {
        buildCharts();
    }
})();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
