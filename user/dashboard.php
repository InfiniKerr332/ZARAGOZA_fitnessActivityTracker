<?php
$pageTitle = 'Dashboard';
$needsChart = true;
require_once __DIR__ . '/../includes/header.php';
requireLogin();
$db = getDB();
$uid = $_SESSION['user_id'];

// AGGREGATION: Total activities, total calories, avg duration
$stats = $db->prepare("SELECT 
    COUNT(*) as total_activities,
    COALESCE(SUM(calories_burned), 0) as total_calories,
    COALESCE(AVG(duration_minutes), 0) as avg_duration,
    COALESCE(SUM(distance_km), 0) as total_distance
    FROM activities WHERE user_id = ? AND deleted_at IS NULL AND status = 'completed'");
$stats->execute([$uid]);
$s = $stats->fetch();

// Active goals count
$goalStats = $db->prepare("SELECT COUNT(*) as active_goals FROM goals WHERE user_id = ? AND status = 'active' AND deleted_at IS NULL");
$goalStats->execute([$uid]);
$g = $goalStats->fetch();

// Nutrition today - AGGREGATION
$todayNutrition = $db->prepare("SELECT 
    COALESCE(SUM(calories), 0) as total_cal,
    COALESCE(SUM(protein_g), 0) as total_protein,
    COALESCE(SUM(carbs_g), 0) as total_carbs,
    COALESCE(SUM(fat_g), 0) as total_fat
    FROM nutrition_logs WHERE user_id = ? AND log_date = CURDATE() AND deleted_at IS NULL");
$todayNutrition->execute([$uid]);
$tn = $todayNutrition->fetch();

// Latest body metric
$latestMetric = $db->prepare("SELECT * FROM body_metrics WHERE user_id = ? AND deleted_at IS NULL ORDER BY recorded_date DESC LIMIT 1");
$latestMetric->execute([$uid]);
$bm = $latestMetric->fetch();

// MULTIPLE JOIN: Recent activities with exercise name and category
$recent = $db->prepare("SELECT a.*, e.exercise_name, e.unit as exercise_unit, c.category_name, c.color_hex, c.icon
    FROM activities a
    JOIN exercise_types e ON a.exercise_id = e.exercise_id
    JOIN activity_categories c ON e.category_id = c.category_id
    WHERE a.user_id = ? AND a.deleted_at IS NULL
    ORDER BY a.activity_date DESC, a.created_at DESC LIMIT 5");
$recent->execute([$uid]);
$recentActivities = $recent->fetchAll();

// SUBQUERY: Weekly calories vs user goal
$user = getCurrentUser();
$dailyGoal = $user['daily_calorie_goal'] ?? 2000;

// Chart data: Last 7 days calories burned
$chartData = $db->prepare("SELECT DATE(activity_date) as day, SUM(calories_burned) as cals 
    FROM activities WHERE user_id = ? AND deleted_at IS NULL AND status = 'completed' AND activity_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(activity_date) ORDER BY day");
$chartData->execute([$uid]);
$chartRows = $chartData->fetchAll();
$chartLabels = []; $chartValues = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('M j', strtotime($d));
    $found = false;
    foreach ($chartRows as $r) { if ($r['day'] === $d) { $chartValues[] = (float)$r['cals']; $found = true; break; } }
    if (!$found) $chartValues[] = 0;
}

// Chart data: Activity by category (JOIN + AGGREGATION)
$catData = $db->prepare("SELECT c.category_name, c.color_hex, COUNT(*) as cnt
    FROM activities a
    JOIN exercise_types e ON a.exercise_id = e.exercise_id
    JOIN activity_categories c ON e.category_id = c.category_id
    WHERE a.user_id = ? AND a.deleted_at IS NULL
    GROUP BY c.category_id ORDER BY cnt DESC LIMIT 6");
$catData->execute([$uid]);
$catRows = $catData->fetchAll();
$catLabels = array_column($catRows, 'category_name');
$catValues = array_column($catRows, 'cnt');
$catColors = array_column($catRows, 'color_hex');

// Today's meal log for dashboard
$todayMeals = $db->prepare("SELECT * FROM nutrition_logs WHERE user_id = ? AND log_date = CURDATE() AND deleted_at IS NULL ORDER BY created_at DESC");
$todayMeals->execute([$uid]);
$todayMealsList = $todayMeals->fetchAll();
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon purple"><i data-lucide="flame"></i></div>
        <div class="stat-value"><?= formatNumber($s['total_activities']) ?></div>
        <div class="stat-label">Total Workouts</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i data-lucide="zap"></i></div>
        <div class="stat-value"><?= formatNumber($s['total_calories']) ?></div>
        <div class="stat-label">Calories Burned</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i data-lucide="clock"></i></div>
        <div class="stat-value"><?= number_format($s['avg_duration'], 0) ?><small style="font-size:14px">min</small></div>
        <div class="stat-label">Avg Duration</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i data-lucide="target"></i></div>
        <div class="stat-value"><?= $g['active_goals'] ?></div>
        <div class="stat-label">Active Goals</div>
    </div>
</div>

<!-- Today's Nutrition -->
<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(140px,1fr))">
    <div class="stat-card"><div class="stat-value" style="font-size:22px"><?= number_format($tn['total_cal']) ?></div><div class="stat-label">Today's Calories</div></div>
    <div class="stat-card"><div class="stat-value" style="font-size:22px"><?= number_format($tn['total_protein'],1) ?>g</div><div class="stat-label">Protein</div></div>
    <div class="stat-card"><div class="stat-value" style="font-size:22px"><?= number_format($tn['total_carbs'],1) ?>g</div><div class="stat-label">Carbs</div></div>
    <div class="stat-card"><div class="stat-value" style="font-size:22px"><?= number_format($tn['total_fat'],1) ?>g</div><div class="stat-label">Fat</div></div>
</div>

<!-- Charts -->
<div class="charts-grid">
    <div class="card">
        <div class="card-header"><h3 class="card-title">Calories Burned (Last 7 Days)</h3></div>
        <div class="chart-container"><canvas id="caloriesChart"></canvas></div>
    </div>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Workouts by Category</h3></div>
        <div class="chart-container"><canvas id="categoryChart"></canvas></div>
    </div>
</div>

<!-- Recent Activities -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Recent Activities</h3>
        <a href="<?= BASE_URL ?>/user/activities.php" class="btn btn-ghost btn-sm">View All →</a>
    </div>
    <?php if (empty($recentActivities)): ?>
    <div class="empty-state"><i data-lucide="activity"></i><h3>No activities yet</h3><p>Start logging your workouts!</p>
        <a href="<?= BASE_URL ?>/user/activities.php" class="btn btn-primary btn-sm">Log Activity</a></div>
    <?php else: ?>
    <div class="table-container">
        <table class="data-table">
            <thead><tr><th>Exercise</th><th>Category</th><th>Duration</th><th>Calories</th><th>Date</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($recentActivities as $a): ?>
            <tr>
                <td><strong><?= sanitize($a['exercise_name']) ?></strong></td>
                <td><span class="badge" style="background:<?= $a['color_hex'] ?>20;color:<?= $a['color_hex'] ?>"><?= sanitize($a['category_name']) ?></span></td>
                <td><?= $a['duration_minutes'] ? number_format($a['duration_minutes'],0) . ' min' : '—' ?></td>
                <td><?= $a['calories_burned'] ? number_format($a['calories_burned'],0) . ' kcal' : '—' ?></td>
                <td><?= formatDate($a['activity_date']) ?></td>
                <td><span class="badge badge-<?= $a['status']==='completed'?'success':'warning' ?>"><?= ucfirst($a['status']) ?></span></td>
                <td class="table-actions">
                    <?php if ($a['status'] === 'planned'): ?>
                    <form method="POST" action="<?= BASE_URL ?>/user/activities.php">
                        <?= csrfField() ?><input type="hidden" name="action" value="complete"><input type="hidden" name="activity_id" value="<?= $a['activity_id'] ?>"><input type="hidden" name="redirect_to" value="dashboard">
                        <button type="submit" class="btn btn-ghost btn-sm" style="color:#22c55e" title="Mark as completed"><i data-lucide="check-circle"></i></button>
                    </form>
                    <?php else: ?>
                    <span style="color:#22c55e"><i data-lucide="check" style="width:16px;height:16px"></i></span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Today's Meals -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Today's Meals</h3>
        <a href="<?= BASE_URL ?>/user/nutrition.php" class="btn btn-ghost btn-sm">View All →</a>
    </div>
    <?php if (empty($todayMealsList)): ?>
    <div class="empty-state"><i data-lucide="apple"></i><h3>No meals logged today</h3><p>Start tracking your nutrition!</p>
        <a href="<?= BASE_URL ?>/user/nutrition.php" class="btn btn-primary btn-sm">Log Meal</a></div>
    <?php else: ?>
    <div class="table-container">
        <table class="data-table">
            <thead><tr><th>Food</th><th>Meal</th><th>Calories</th><th>Protein</th><th>Carbs</th><th>Fat</th></tr></thead>
            <tbody>
            <?php foreach ($todayMealsList as $meal): ?>
            <tr>
                <td><strong><?= sanitize($meal['food_name']) ?></strong><?php if($meal['serving_size']): ?><br><small class="text-muted"><?= sanitize($meal['serving_size']) ?></small><?php endif; ?></td>
                <td><span class="badge badge-info"><?= ucfirst($meal['meal_type']) ?></span></td>
                <td><?= number_format($meal['calories']) ?></td>
                <td><?= number_format($meal['protein_g'],1) ?>g</td>
                <td><?= number_format($meal['carbs_g'],1) ?>g</td>
                <td><?= number_format($meal['fat_g'],1) ?>g</td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const chartOpts = { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}},
        scales:{x:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{color:'#666'}},y:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{color:'#666'}}} };
    new Chart(document.getElementById('caloriesChart'), {
        type:'bar', data:{labels:<?= json_encode($chartLabels) ?>,datasets:[{data:<?= json_encode($chartValues) ?>,
        backgroundColor:'rgba(69,93,211,0.6)',borderColor:'#455DD3',borderWidth:1,borderRadius:6}]}, options:chartOpts
    });
    new Chart(document.getElementById('categoryChart'), {
        type:'doughnut', data:{labels:<?= json_encode($catLabels) ?>,datasets:[{data:<?= json_encode($catValues) ?>,
        backgroundColor:<?= json_encode($catColors) ?>,borderWidth:0}]},
        options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{color:'#a0a0a0',padding:12}}}}
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
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
