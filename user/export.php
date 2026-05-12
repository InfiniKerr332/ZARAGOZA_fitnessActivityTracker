<?php
/**
 * Export Report Data - CSV and PDF
 */
require_once __DIR__ . '/../includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isLoggedIn()) { http_response_code(403); exit('Unauthorized'); }

$db = getDB();
$uid = $_SESSION['user_id'];
$type = $_GET['type'] ?? 'monthly';
$format = $_GET['format'] ?? 'csv';

switch ($type) {
    case 'monthly':
        $stmt = $db->prepare("
            SELECT n.log_date, n.total_intake, COALESCE(a.total_burned, 0) as total_burned,
                   (n.total_intake - COALESCE(a.total_burned, 0)) as surplus
            FROM (SELECT log_date, SUM(calories) as total_intake FROM nutrition_logs WHERE user_id = ? AND deleted_at IS NULL GROUP BY log_date) n
            LEFT JOIN (SELECT activity_date, SUM(calories_burned) as total_burned FROM activities WHERE user_id = ? AND deleted_at IS NULL AND status = 'completed' GROUP BY activity_date) a ON n.log_date = a.activity_date
            ORDER BY n.log_date DESC LIMIT 7
        ");
        $stmt->execute([$uid, $uid]);
        $calorieRows = $stmt->fetchAll();

        $stmt = $db->prepare("SELECT a.activity_date, e.exercise_name, a.duration_minutes, a.calories_burned FROM activities a JOIN exercise_types e ON a.exercise_id = e.exercise_id WHERE a.user_id = ? AND a.deleted_at IS NULL ORDER BY a.activity_date DESC LIMIT 10");
        $stmt->execute([$uid]);
        $actRows = $stmt->fetchAll();

        $stmt = $db->prepare("SELECT log_date, food_name, meal_type, calories FROM nutrition_logs WHERE user_id = ? AND deleted_at IS NULL ORDER BY log_date DESC LIMIT 10");
        $stmt->execute([$uid]);
        $nutRows = $stmt->fetchAll();

        $stmt = $db->prepare("SELECT recorded_date, weight_kg, body_fat_pct, bmi FROM body_metrics WHERE user_id = ? AND deleted_at IS NULL ORDER BY recorded_date DESC LIMIT 10");
        $stmt->execute([$uid]);
        $metricRows = $stmt->fetchAll();

        $stmt = $db->prepare("SELECT goal_type, target_value, current_value, unit, status FROM goals WHERE user_id = ? AND deleted_at IS NULL ORDER BY created_at DESC LIMIT 10");
        $stmt->execute([$uid]);
        $goalRows = $stmt->fetchAll();

        $filename = 'comprehensive_summary';
        break;

    case 'calorie_balance':
        $stmt = $db->prepare("
            SELECT n.log_date, n.total_intake, COALESCE(a.total_burned, 0) as total_burned,
                   (n.total_intake - COALESCE(a.total_burned, 0)) as surplus
            FROM (SELECT log_date, SUM(calories) as total_intake FROM nutrition_logs WHERE user_id = ? AND deleted_at IS NULL GROUP BY log_date) n
            LEFT JOIN (SELECT activity_date, SUM(calories_burned) as total_burned FROM activities WHERE user_id = ? AND deleted_at IS NULL AND status = 'completed' GROUP BY activity_date) a ON n.log_date = a.activity_date
            ORDER BY n.log_date DESC LIMIT 30
        ");
        $stmt->execute([$uid, $uid]);
        $rows = $stmt->fetchAll();
        $headers = ['Date','Calories Intake','Calories Burned','Balance'];
        $dataKeys = ['log_date','total_intake','total_burned','surplus'];
        $filename = 'calorie_balance';
        break;

    case 'activities':
        $stmt = $db->prepare("
            SELECT a.activity_date, e.exercise_name, c.category_name, a.duration_minutes,
                   a.distance_km, a.sets, a.reps, a.weight_used_kg, a.calories_burned, a.status
            FROM activities a
            JOIN exercise_types e ON a.exercise_id = e.exercise_id
            JOIN activity_categories c ON e.category_id = c.category_id
            WHERE a.user_id = ? AND a.deleted_at IS NULL ORDER BY a.activity_date DESC
        ");
        $stmt->execute([$uid]);
        $rows = $stmt->fetchAll();
        $headers = ['Date','Exercise','Category','Duration (min)','Distance (km)','Sets','Reps','Weight (kg)','Calories','Status'];
        $dataKeys = ['activity_date','exercise_name','category_name','duration_minutes','distance_km','sets','reps','weight_used_kg','calories_burned','status'];
        $filename = 'activities';
        break;

    case 'nutrition':
        $stmt = $db->prepare("
            SELECT log_date, meal_type, food_name, serving_size, calories, protein_g, carbs_g, fat_g, fiber_g
            FROM nutrition_logs WHERE user_id = ? AND deleted_at IS NULL ORDER BY log_date DESC
        ");
        $stmt->execute([$uid]);
        $rows = $stmt->fetchAll();
        $headers = ['Date','Meal','Food','Serving','Calories','Protein (g)','Carbs (g)','Fat (g)','Fiber (g)'];
        $dataKeys = ['log_date','meal_type','food_name','serving_size','calories','protein_g','carbs_g','fat_g','fiber_g'];
        $filename = 'nutrition_log';
        break;

    default:
        http_response_code(400);
        exit('Invalid report type.');
}

if ($format === 'csv') {
    header('Content-Type: text/csv');
    header("Content-Disposition: attachment; filename=\"fittrack_{$filename}_" . date('Y-m-d') . ".csv\"");
    $output = fopen('php://output', 'w');
    
    if ($type === 'monthly') {
        fputcsv($output, ['CALORIE BALANCE (LATEST 7 DAYS)']);
        fputcsv($output, ['Date', 'Intake', 'Burned', 'Balance']);
        foreach($calorieRows as $r) fputcsv($output, [$r['log_date'], $r['total_intake'], $r['total_burned'], $r['surplus']]);
        fputcsv($output, []);
        fputcsv($output, ['LATEST ACTIVITIES']);
        fputcsv($output, ['Date', 'Exercise', 'Duration (min)', 'Calories']);
        foreach($actRows as $r) fputcsv($output, [$r['activity_date'], $r['exercise_name'], $r['duration_minutes'], $r['calories_burned']]);
        fputcsv($output, []);
        fputcsv($output, ['LATEST NUTRITION']);
        fputcsv($output, ['Date', 'Food', 'Meal', 'Calories']);
        foreach($nutRows as $r) fputcsv($output, [$r['log_date'], $r['food_name'], $r['meal_type'], $r['calories']]);
        fputcsv($output, []);
        fputcsv($output, ['BODY METRICS (LATEST)']);
        fputcsv($output, ['Date', 'Weight (kg)', 'Body Fat %', 'BMI']);
        foreach($metricRows as $r) fputcsv($output, [$r['recorded_date'], $r['weight_kg'], $r['body_fat_pct'], $r['bmi']]);
        fputcsv($output, []);
        fputcsv($output, ['GOALS OVERVIEW']);
        fputcsv($output, ['Type', 'Target', 'Current', 'Unit', 'Status']);
        foreach($goalRows as $r) fputcsv($output, [$r['goal_type'], $r['target_value'], $r['current_value'], $r['unit'], ucfirst($r['status'])]);
    } else {
        fputcsv($output, $headers);
        foreach ($rows as $row) {
            $line = [];
            foreach ($dataKeys as $key) {
                $line[] = $row[$key] ?? '';
            }
            fputcsv($output, $line);
        }
    }
    fclose($output);
    exit;
} elseif ($format === 'pdf') {
    // Generate a clean HTML-to-PDF printable page
    $user = getCurrentUser();
    $title = ucwords(str_replace('_', ' ', $filename));
    ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>FitTrack Pro - <?= $title ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', Arial, sans-serif; background: #fff; color: #374151; padding: 40px; max-width: 1000px; margin: 0 auto; }
        .report-header { display: flex; align-items: center; border-bottom: 2px solid #e5e7eb; padding-bottom: 24px; margin-bottom: 32px; }
        .report-logo { width: 80px; height: 80px; margin-right: 24px; border-radius: 50%; overflow: hidden; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .report-logo img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .report-title-wrapper { flex: 1; }
        .report-title { font-size: 28px; font-weight: 700; margin: 0 0 4px 0; color: #111827; letter-spacing: -0.5px; }
        .report-subtitle { font-size: 15px; color: #455DD3; font-weight: 600; margin: 0; text-transform: uppercase; letter-spacing: 1px; }
        .report-meta { text-align: right; font-size: 13px; color: #6b7280; line-height: 1.5; }
        .report-meta strong { color: #111827; display: block; margin-bottom: 2px; font-size: 14px; }
        
        table { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 16px; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; }
        th, td { border-bottom: 1px solid #e5e7eb; padding: 12px 16px; text-align: left; font-size: 14px; }
        th { background: #f9fafb; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; color: #6b7280; }
        tr:last-child td { border-bottom: none; }
        tr:nth-child(even) { background: #f9fafb; }
        
        .footer { margin-top: 48px; text-align: center; color: #9ca3af; font-size: 12px; border-top: 1px solid #e5e7eb; padding-top: 16px; }
        h3 { color: #111827; font-size: 16px; margin: 32px 0 8px; border-bottom: 1px solid #e5e7eb; padding-bottom: 8px; }
        
        .no-print { margin-bottom: 24px; text-align: right; background: #f3f4f6; padding: 16px; border-radius: 8px; display: flex; justify-content: flex-end; gap: 12px; }
        .btn-print { padding: 10px 24px; background: #455DD3; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-family: 'Inter', sans-serif; display: flex; align-items: center; gap: 8px; }
        .btn-close { padding: 10px 24px; background: #6b7280; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-family: 'Inter', sans-serif; }
        
        @media print { 
            body { padding: 0; } 
            .no-print { display: none !important; } 
            table { border: none; border-radius: 0; }
            th, td { border: 1px solid #e5e7eb; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.close()" class="btn-close">Close Preview</button>
        <button onclick="window.print()" class="btn-print">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            Print / Save as PDF
        </button>
    </div>
    
    <div class="report-header">
        <div class="report-logo">
            <img src="<?= BASE_URL ?>/assets/image/logo21.png" alt="FitTrack Pro Logo">
        </div>
        <div class="report-title-wrapper">
            <h1 class="report-title">FitTrack Pro</h1>
            <p class="report-subtitle"><?= $title ?> Report</p>
        </div>
        <div class="report-meta">
            <strong>Generated For</strong>
            <?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?><br>
            <?= date('M j, Y \a\t g:i A') ?>
        </div>
    </div>

    <?php if ($type === 'monthly'): ?>
        <h3>Calorie Balance (Latest)</h3>
        <table>
            <thead><tr><th>Date</th><th>Intake</th><th>Burned</th><th>Balance</th></tr></thead>
            <tbody>
                <?php foreach($calorieRows as $r): $s = (float)$r['surplus']; ?>
                <tr><td><?= date('M j, Y', strtotime($r['log_date'])) ?></td><td><?= number_format($r['total_intake'],0) ?> kcal</td><td><?= number_format($r['total_burned'],0) ?> kcal</td><td><?= ($s>0?'+':'').number_format($s,0) ?> kcal</td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3>Latest Activities</h3>
        <table>
            <thead><tr><th>Date</th><th>Exercise</th><th>Duration</th><th>Calories</th></tr></thead>
            <tbody>
                <?php foreach($actRows as $r): ?>
                <tr><td><?= date('M j, Y', strtotime($r['activity_date'])) ?></td><td><?= htmlspecialchars($r['exercise_name']) ?></td><td><?= $r['duration_minutes'] ?> min</td><td><?= $r['calories_burned'] ?> kcal</td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3>Latest Nutrition</h3>
        <table>
            <thead><tr><th>Date</th><th>Food</th><th>Meal</th><th>Calories</th></tr></thead>
            <tbody>
                <?php foreach($nutRows as $r): ?>
                <tr><td><?= date('M j, Y', strtotime($r['log_date'])) ?></td><td><?= htmlspecialchars($r['food_name']) ?></td><td><?= ucfirst($r['meal_type']) ?></td><td><?= $r['calories'] ?> kcal</td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3>Body Metrics (Latest)</h3>
        <table>
            <thead><tr><th>Date</th><th>Weight</th><th>Body Fat %</th><th>BMI</th></tr></thead>
            <tbody>
                <?php foreach($metricRows as $r): ?>
                <tr><td><?= date('M j, Y', strtotime($r['recorded_date'])) ?></td><td><?= $r['weight_kg'] ?> kg</td><td><?= $r['body_fat_pct'] ? $r['body_fat_pct'].'%' : '—' ?></td><td><?= $r['bmi'] ? $r['bmi'] : '—' ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3>Goals Overview</h3>
        <table>
            <thead><tr><th>Goal Type</th><th>Target</th><th>Current</th><th>Status</th></tr></thead>
            <tbody>
                <?php if(empty($goalRows)): ?>
                <tr><td colspan="4" style="text-align:center;color:#9ca3af;padding:16px;">No goals set.</td></tr>
                <?php else: ?>
                <?php foreach($goalRows as $r): ?>
                <tr><td><?= htmlspecialchars($r['goal_type']) ?></td><td><?= $r['target_value'] ?> <?= $r['unit'] ?></td><td><?= $r['current_value'] ?> <?= $r['unit'] ?></td><td><span style="font-weight:600;color:<?= $r['status']==='active'?'#22c55e':($r['status']==='completed'?'#455DD3':'#6b7280') ?>"><?= ucfirst($r['status']) ?></span></td></tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    <?php else: ?>
        <table>
            <thead><tr><?php foreach ($headers as $h): ?><th><?= $h ?></th><?php endforeach; ?></tr></thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="<?= count($headers) ?>" style="text-align:center;color:#9ca3af;padding:32px;">No data available for this report.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                <tr><?php foreach ($dataKeys as $key): ?><td><?= htmlspecialchars($row[$key] ?? '—') ?></td><?php endforeach; ?></tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>
    
    <div class="footer">
        <strong>FitTrack Pro</strong> — AdvancedDB Finals Project<br>
        Document strictly confidential. Generated automatically on <?= date('Y-m-d') ?>.
    </div>
</body>
</html>
    <?php
    exit;
}
?>
