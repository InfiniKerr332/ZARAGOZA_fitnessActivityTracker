<?php
$pageTitle = 'Admin Dashboard';
$needsChart = true;
require_once __DIR__ . '/../includes/header.php';
requireAdmin();
$db = getDB();

// AGGREGATION: System-wide stats
$stats = $db->query("SELECT 
    (SELECT COUNT(*) FROM users WHERE deleted_at IS NULL) as total_users,
    (SELECT COUNT(*) FROM users WHERE deleted_at IS NULL AND role='admin') as total_admins,
    (SELECT COUNT(*) FROM activities WHERE deleted_at IS NULL) as total_activities,
    (SELECT COUNT(*) FROM nutrition_logs WHERE deleted_at IS NULL) as total_nutrition,
    (SELECT COUNT(*) FROM goals WHERE deleted_at IS NULL AND status='active') as active_goals,
    (SELECT COUNT(*) FROM body_metrics WHERE deleted_at IS NULL) as total_metrics,
    (SELECT SUM(calories_burned) FROM activities WHERE deleted_at IS NULL) as total_calories
")->fetch();

// Recent registrations
$recentUsers = $db->query("SELECT user_id, username, email, first_name, last_name, role, created_at 
    FROM users WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 5")->fetchAll();

// MULTIPLE JOIN: Most active users
$activeUsers = $db->query("SELECT u.username, u.first_name, u.last_name, 
    COUNT(a.activity_id) as activity_count,
    COALESCE(SUM(a.calories_burned),0) as total_cal
    FROM users u
    LEFT JOIN activities a ON u.user_id = a.user_id AND a.deleted_at IS NULL
    WHERE u.deleted_at IS NULL
    GROUP BY u.user_id ORDER BY activity_count DESC LIMIT 5")->fetchAll();

// Activities by category
$catStats = $db->query("SELECT c.category_name, c.color_hex, COUNT(a.activity_id) as cnt
    FROM activity_categories c
    LEFT JOIN exercise_types e ON c.category_id = e.category_id
    LEFT JOIN activities a ON e.exercise_id = a.exercise_id AND a.deleted_at IS NULL
    WHERE c.deleted_at IS NULL GROUP BY c.category_id ORDER BY cnt DESC")->fetchAll();

// Recent system logs
$logs = $db->query("SELECT l.*, u.username FROM system_logs l JOIN users u ON l.user_id = u.user_id ORDER BY l.created_at DESC LIMIT 8")->fetchAll();

$catLabels = array_column($catStats,'category_name');
$catValues = array_map(fn($r)=>(int)$r['cnt'], $catStats);
$catColors = array_column($catStats,'color_hex');
?>

<div class="stats-grid">
    <div class="stat-card"><div class="stat-icon blue"><i data-lucide="users"></i></div><div class="stat-value"><?= $stats['total_users'] ?></div><div class="stat-label">Total Users</div></div>
    <div class="stat-card"><div class="stat-icon purple"><i data-lucide="flame"></i></div><div class="stat-value"><?= formatNumber($stats['total_activities']) ?></div><div class="stat-label">Total Activities</div></div>
    <div class="stat-card"><div class="stat-icon orange"><i data-lucide="zap"></i></div><div class="stat-value"><?= formatNumber($stats['total_calories']??0) ?></div><div class="stat-label">Calories Burned (All)</div></div>
    <div class="stat-card"><div class="stat-icon green"><i data-lucide="target"></i></div><div class="stat-value"><?= $stats['active_goals'] ?></div><div class="stat-label">Active Goals</div></div>
</div>

<div class="charts-grid">
    <div class="card">
        <div class="card-header"><h3 class="card-title">Activities by Category</h3></div>
        <div class="chart-container"><canvas id="adminCatChart"></canvas></div>
    </div>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Most Active Users</h3></div>
        <div class="table-container" style="border:none">
        <table class="data-table">
        <thead><tr><th>User</th><th>Activities</th><th>Calories</th></tr></thead>
        <tbody>
        <?php foreach($activeUsers as $u): ?>
        <tr><td><strong><?= sanitize($u['first_name'].' '.$u['last_name']) ?></strong><br><small style="color:var(--text-muted)">@<?= sanitize($u['username']) ?></small></td>
            <td><?= $u['activity_count'] ?></td><td><?= formatNumber($u['total_cal']) ?></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
    </div>
</div>

<div class="charts-grid">
    <div class="card">
        <div class="card-header"><h3 class="card-title">Recent Registrations</h3></div>
        <div class="table-container" style="border:none"><table class="data-table">
        <thead><tr><th>User</th><th>Email</th><th>Role</th><th>Joined</th></tr></thead>
        <tbody>
        <?php foreach($recentUsers as $u): ?>
        <tr><td><strong><?= sanitize($u['first_name'].' '.$u['last_name']) ?></strong></td>
            <td><?= sanitize($u['email']) ?></td>
            <td><span class="badge badge-<?= $u['role']==='admin'?'info':'secondary' ?>"><?= ucfirst($u['role']) ?></span></td>
            <td><?= timeAgo($u['created_at']) ?></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
    </div>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Recent Activity Log</h3></div>
        <div class="table-container" style="border:none"><table class="data-table">
        <thead><tr><th>User</th><th>Action</th><th>Time</th></tr></thead>
        <tbody>
        <?php foreach($logs as $l): ?>
        <tr><td>@<?= sanitize($l['username']) ?></td><td><span class="badge badge-secondary"><?= sanitize($l['action']) ?></span></td><td><?= timeAgo($l['created_at']) ?></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    new Chart(document.getElementById('adminCatChart'),{type:'bar',data:{labels:<?= json_encode($catLabels) ?>,datasets:[{data:<?= json_encode($catValues) ?>,backgroundColor:<?= json_encode(array_map(fn($c)=>$c.'99',$catColors)) ?>,borderColor:<?= json_encode($catColors) ?>,borderWidth:1,borderRadius:6}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{color:'#666'}},y:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{color:'#666'}}}}});
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
