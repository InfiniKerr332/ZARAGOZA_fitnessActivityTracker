<?php
$pageTitle = 'Admin Reports';
$needsChart = true;
require_once __DIR__ . '/../includes/header.php';
requireAdmin();
$db = getDB();

// CTE: User engagement report with cumulative registrations
$engagementCTE = $db->query("
    WITH user_engagement AS (
        SELECT u.user_id, u.username, u.first_name, u.last_name, u.created_at as joined,
               COUNT(DISTINCT a.activity_id) as activities,
               COUNT(DISTINCT n.nutrition_id) as nutrition_entries,
               COUNT(DISTINCT g.goal_id) as goals,
               COALESCE(SUM(a.calories_burned), 0) as total_cal
        FROM users u
        LEFT JOIN activities a ON u.user_id = a.user_id AND a.deleted_at IS NULL
        LEFT JOIN nutrition_logs n ON u.user_id = n.user_id AND n.deleted_at IS NULL
        LEFT JOIN goals g ON u.user_id = g.user_id AND g.deleted_at IS NULL
        WHERE u.deleted_at IS NULL
        GROUP BY u.user_id
    )
    SELECT *, (activities + nutrition_entries + goals) as engagement_score
    FROM user_engagement ORDER BY engagement_score DESC LIMIT 15
")->fetchAll();

// SUBQUERY: Categories with above-average exercise count
$aboveAvgCats = $db->query("
    SELECT c.category_name, c.color_hex, COUNT(e.exercise_id) as ex_count,
           (SELECT AVG(cnt) FROM (SELECT COUNT(*) as cnt FROM exercise_types WHERE deleted_at IS NULL GROUP BY category_id) avg_tbl) as avg_count
    FROM activity_categories c
    LEFT JOIN exercise_types e ON c.category_id = e.category_id AND e.deleted_at IS NULL
    WHERE c.deleted_at IS NULL
    GROUP BY c.category_id
    HAVING ex_count > (SELECT AVG(cnt) FROM (SELECT COUNT(*) as cnt FROM exercise_types WHERE deleted_at IS NULL GROUP BY category_id) avg_tbl)
    ORDER BY ex_count DESC
")->fetchAll();

// System-wide monthly registrations
$regData = $db->query("SELECT DATE_FORMAT(created_at,'%Y-%m') as month, COUNT(*) as cnt FROM users WHERE deleted_at IS NULL GROUP BY month ORDER BY month DESC LIMIT 6")->fetchAll();
$regLabels = array_map(fn($r)=>date('M Y',strtotime($r['month'].'-01')),array_reverse($regData));
$regValues = array_map(fn($r)=>(int)$r['cnt'],array_reverse($regData));

// Activity distribution by difficulty (MULTIPLE JOIN + AGGREGATION)
$diffDist = $db->query("SELECT e.difficulty, COUNT(a.activity_id) as cnt, SUM(a.calories_burned) as cals
    FROM activities a
    JOIN exercise_types e ON a.exercise_id = e.exercise_id
    WHERE a.deleted_at IS NULL
    GROUP BY e.difficulty ORDER BY cnt DESC")->fetchAll();
?>

<div class="section-header">
    <h2>Admin Reports & Analytics</h2>
    <button class="btn btn-primary" onclick="openModal('adminExportModal')"><i data-lucide="download"></i> Download Report</button>
</div>

<!-- Registration Trend -->
<div class="charts-grid">
    <div class="card">
        <div class="card-header"><h3 class="card-title"><i data-lucide="user-plus" style="width:18px;height:18px;color:var(--accent)"></i> Monthly Registrations</h3></div>
        <div class="chart-container"><canvas id="regChart"></canvas></div>
    </div>
    <div class="card">
        <div class="card-header"><h3 class="card-title"><i data-lucide="bar-chart" style="width:18px;height:18px;color:var(--accent)"></i> Activities by Difficulty</h3></div>
        <?php if($diffDist): ?>
        <div class="chart-container"><canvas id="diffChart"></canvas></div>
        <?php else: ?><div class="empty-state"><p>No activity data.</p></div><?php endif; ?>
    </div>
</div>

<!-- User Engagement CTE Report -->
<div class="report-section">
    <h2><i data-lucide="users"></i> User Engagement Report (CTE)</h2>
    <div class="table-container"><table class="data-table">
    <thead><tr><th>User</th><th>Activities</th><th>Nutrition</th><th>Goals</th><th>Calories</th><th>Score</th><th>Joined</th></tr></thead>
    <tbody>
    <?php foreach($engagementCTE as $u): ?>
    <tr>
        <td><strong><?= sanitize($u['first_name'].' '.$u['last_name']) ?></strong><br><small style="color:var(--text-muted)">@<?= sanitize($u['username']) ?></small></td>
        <td><?= $u['activities'] ?></td><td><?= $u['nutrition_entries'] ?></td><td><?= $u['goals'] ?></td>
        <td><?= formatNumber($u['total_cal']) ?></td>
        <td><span class="badge badge-<?= $u['engagement_score']>10?'success':($u['engagement_score']>3?'warning':'secondary') ?>"><?= $u['engagement_score'] ?></span></td>
        <td><?= formatDate($u['joined']) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody></table></div>
</div>

<!-- Above Average Categories -->
<div class="report-section">
    <h2><i data-lucide="trending-up"></i> Categories with Above-Average Exercises</h2>
    <?php if(empty($aboveAvgCats)): ?>
    <div class="card"><div class="empty-state"><p>All categories have similar exercise counts.</p></div></div>
    <?php else: ?>
    <div class="table-container"><table class="data-table">
    <thead><tr><th>Category</th><th>Exercises</th><th>Average</th></tr></thead>
    <tbody>
    <?php foreach($aboveAvgCats as $c): ?>
    <tr><td><span class="badge" style="background:<?= $c['color_hex'] ?>20;color:<?= $c['color_hex'] ?>"><?= sanitize($c['category_name']) ?></span></td>
        <td><?= $c['ex_count'] ?></td><td><?= number_format($c['avg_count'],1) ?></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
    <?php endif; ?>
</div>

<!-- Admin Export Modal -->
<div class="modal-overlay" id="adminExportModal"><div class="modal" style="max-width:640px">
<div class="modal-header"><h2>Download Admin Report</h2><button class="modal-close" onclick="closeModal('adminExportModal')"><i data-lucide="x"></i></button></div>
<div class="modal-body">
    <p style="color:var(--text-secondary);margin-bottom:16px;">Choose a report to download.</p>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div class="card" style="cursor:pointer;padding:20px;text-align:center;transition:all .2s;" onclick="selectAdminReport('system_overview')" onmouseover="this.style.borderColor='var(--accent)'" onmouseout="this.style.borderColor='transparent'">
            <i data-lucide="layout-dashboard" style="width:32px;height:32px;color:var(--accent);margin-bottom:8px;display:block;margin-left:auto;margin-right:auto;"></i>
            <strong>System Overview</strong>
            <p style="font-size:12px;color:var(--text-muted);margin-top:4px;">Users, activities, nutrition & categories</p>
        </div>
        <div class="card" style="cursor:pointer;padding:20px;text-align:center;transition:all .2s;" onclick="selectAdminReport('engagement')" onmouseover="this.style.borderColor='var(--accent)'" onmouseout="this.style.borderColor='transparent'">
            <i data-lucide="users" style="width:32px;height:32px;color:#22c55e;margin-bottom:8px;display:block;margin-left:auto;margin-right:auto;"></i>
            <strong>User Engagement</strong>
            <p style="font-size:12px;color:var(--text-muted);margin-top:4px;">Activity scores & rankings per user</p>
        </div>
        <div class="card" style="cursor:pointer;padding:20px;text-align:center;transition:all .2s;" onclick="selectAdminReport('registrations')" onmouseover="this.style.borderColor='var(--accent)'" onmouseout="this.style.borderColor='transparent'">
            <i data-lucide="user-plus" style="width:32px;height:32px;color:#f59e0b;margin-bottom:8px;display:block;margin-left:auto;margin-right:auto;"></i>
            <strong>Registrations</strong>
            <p style="font-size:12px;color:var(--text-muted);margin-top:4px;">Monthly sign-up trends</p>
        </div>
        <div class="card" style="cursor:pointer;padding:20px;text-align:center;transition:all .2s;" onclick="selectAdminReport('categories')" onmouseover="this.style.borderColor='var(--accent)'" onmouseout="this.style.borderColor='transparent'">
            <i data-lucide="folder" style="width:32px;height:32px;color:#ef4444;margin-bottom:8px;display:block;margin-left:auto;margin-right:auto;"></i>
            <strong>Category Analysis</strong>
            <p style="font-size:12px;color:var(--text-muted);margin-top:4px;">Exercise distribution by category</p>
        </div>
    </div>
</div></div></div>

<!-- Admin Download Format Modal -->
<div class="modal-overlay" id="adminFormatModal"><div class="modal" style="max-width:420px">
<div class="modal-header"><h2 id="adminFormatTitle">Download Report</h2><button class="modal-close" onclick="closeModal('adminFormatModal')"><i data-lucide="x"></i></button></div>
<div class="modal-body" style="text-align:center;padding:24px 30px;">
    <p style="color:var(--text-secondary);margin-bottom:20px;">Choose your download format:</p>
    <div style="display:flex;gap:12px;justify-content:center;">
        <button class="btn btn-primary" onclick="downloadAdminReport('csv')"><i data-lucide="file-text"></i> Download CSV</button>
        <button class="btn btn-outline" onclick="downloadAdminReport('pdf')"><i data-lucide="printer"></i> Print / PDF</button>
    </div>
    <button class="btn btn-ghost btn-sm" onclick="closeModal('adminFormatModal');openModal('adminExportModal')" style="margin-top:16px;">← Back to reports</button>
</div></div></div>

<script>
let adminReportType = '';
const adminTitles = { system_overview:'System Overview', engagement:'User Engagement', registrations:'Monthly Registrations', categories:'Category Analysis' };

function selectAdminReport(type) {
    adminReportType = type;
    closeModal('adminExportModal');
    document.getElementById('adminFormatTitle').textContent = adminTitles[type] + ' — Download';
    openModal('adminFormatModal');
    if (typeof initLucideIcons === 'function') initLucideIcons();
}

function downloadAdminReport(format) {
    const url = `<?= BASE_URL ?>/admin/export.php?type=${adminReportType}&format=${format}`;
    if (format === 'pdf') {
        window.open(url, '_blank');
    } else {
        window.location.href = url;
    }
    closeModal('adminFormatModal');
}

document.addEventListener('DOMContentLoaded', () => {
    const opts = {responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{color:'#666'}},y:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{color:'#666'}}}};
    new Chart(document.getElementById('regChart'),{type:'bar',data:{labels:<?= json_encode($regLabels) ?>,datasets:[{data:<?= json_encode($regValues) ?>,backgroundColor:'rgba(0,117,222,0.6)',borderColor:'#0075DE',borderWidth:1,borderRadius:6}]},options:opts});
    <?php if($diffDist): ?>
    new Chart(document.getElementById('diffChart'),{type:'doughnut',data:{labels:<?= json_encode(array_map(fn($r)=>ucfirst($r['difficulty']),$diffDist)) ?>,datasets:[{data:<?= json_encode(array_map(fn($r)=>(int)$r['cnt'],$diffDist)) ?>,backgroundColor:['#22c55e','#f59e0b','#ef4444'],borderWidth:0}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{color:'#a0a0a0'}}}}});
    <?php endif; ?>
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
