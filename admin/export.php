<?php
/**
 * Admin Export Report Data - CSV and PDF
 */
require_once __DIR__ . '/../includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isLoggedIn() || !isAdmin()) { http_response_code(403); exit('Unauthorized'); }

$db = getDB();
$type = $_GET['type'] ?? 'engagement';
$format = $_GET['format'] ?? 'csv';

switch ($type) {
    case 'engagement':
        $stmt = $db->query("
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
            SELECT username, first_name, last_name, activities, nutrition_entries, goals, total_cal,
                   (activities + nutrition_entries + goals) as engagement_score, joined
            FROM user_engagement ORDER BY engagement_score DESC
        ");
        $rows = $stmt->fetchAll();
        $headers = ['Username','First Name','Last Name','Activities','Nutrition','Goals','Calories','Score','Joined'];
        $dataKeys = ['username','first_name','last_name','activities','nutrition_entries','goals','total_cal','engagement_score','joined'];
        $filename = 'user_engagement';
        $title = 'User Engagement';
        break;

    case 'system_overview':
        // Comprehensive admin overview: users, activities, nutrition, categories
        $userStats = $db->query("SELECT COUNT(*) as total_users, SUM(CASE WHEN role='admin' THEN 1 ELSE 0 END) as admins, SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_users FROM users WHERE deleted_at IS NULL")->fetch();
        $actStats = $db->query("SELECT COUNT(*) as total, SUM(calories_burned) as total_cal, SUM(duration_minutes) as total_min, AVG(calories_burned) as avg_cal FROM activities WHERE deleted_at IS NULL AND status = 'completed'")->fetch();
        $nutStats = $db->query("SELECT COUNT(*) as total, SUM(calories) as total_cal, AVG(calories) as avg_cal FROM nutrition_logs WHERE deleted_at IS NULL")->fetch();
        $catStats = $db->query("SELECT c.category_name, COUNT(e.exercise_id) as exercises, COALESCE(SUM(a.cnt),0) as times_used FROM activity_categories c LEFT JOIN exercise_types e ON c.category_id = e.category_id AND e.deleted_at IS NULL LEFT JOIN (SELECT exercise_id, COUNT(*) as cnt FROM activities WHERE deleted_at IS NULL GROUP BY exercise_id) a ON e.exercise_id = a.exercise_id WHERE c.deleted_at IS NULL GROUP BY c.category_id ORDER BY times_used DESC")->fetchAll();
        $regData = $db->query("SELECT DATE_FORMAT(created_at,'%Y-%m') as month, COUNT(*) as cnt FROM users WHERE deleted_at IS NULL GROUP BY month ORDER BY month DESC LIMIT 12")->fetchAll();

        // This is a multi-section report
        $isMultiSection = true;
        $filename = 'system_overview';
        $title = 'System Overview';
        break;

    case 'registrations':
        $stmt = $db->query("
            SELECT DATE_FORMAT(created_at,'%Y-%m') as month, COUNT(*) as registrations,
                   SUM(COUNT(*)) OVER (ORDER BY DATE_FORMAT(created_at,'%Y-%m')) as cumulative
            FROM users WHERE deleted_at IS NULL
            GROUP BY month ORDER BY month DESC LIMIT 24
        ");
        $rows = $stmt->fetchAll();
        $headers = ['Month','Registrations','Cumulative Total'];
        $dataKeys = ['month','registrations','cumulative'];
        $filename = 'monthly_registrations';
        $title = 'Monthly Registrations';
        break;

    case 'categories':
        $stmt = $db->query("
            SELECT c.category_name, c.color_hex, COUNT(e.exercise_id) as exercise_count,
                   (SELECT AVG(cnt) FROM (SELECT COUNT(*) as cnt FROM exercise_types WHERE deleted_at IS NULL GROUP BY category_id) t) as avg_count
            FROM activity_categories c
            LEFT JOIN exercise_types e ON c.category_id = e.category_id AND e.deleted_at IS NULL
            WHERE c.deleted_at IS NULL
            GROUP BY c.category_id
            ORDER BY exercise_count DESC
        ");
        $rows = $stmt->fetchAll();
        $headers = ['Category','Color','Exercise Count','System Average'];
        $dataKeys = ['category_name','color_hex','exercise_count','avg_count'];
        $filename = 'category_report';
        $title = 'Category Analysis';
        break;

    default:
        http_response_code(400);
        exit('Invalid report type.');
}

if ($format === 'csv') {
    header('Content-Type: text/csv');
    header("Content-Disposition: attachment; filename=\"fittrack_admin_{$filename}_" . date('Y-m-d') . ".csv\"");
    $output = fopen('php://output', 'w');

    if (isset($isMultiSection) && $isMultiSection) {
        // System Overview multi-section CSV
        fputcsv($output, ['SYSTEM OVERVIEW - Generated ' . date('Y-m-d H:i:s')]);
        fputcsv($output, []);
        fputcsv($output, ['USER STATISTICS']);
        fputcsv($output, ['Total Users', 'Admins', 'New Users (30d)']);
        fputcsv($output, [$userStats['total_users'], $userStats['admins'], $userStats['new_users']]);
        fputcsv($output, []);
        fputcsv($output, ['ACTIVITY STATISTICS']);
        fputcsv($output, ['Total Activities', 'Total Calories Burned', 'Total Duration (min)', 'Avg Cal/Activity']);
        fputcsv($output, [$actStats['total'], $actStats['total_cal'], $actStats['total_min'], round($actStats['avg_cal'])]);
        fputcsv($output, []);
        fputcsv($output, ['NUTRITION STATISTICS']);
        fputcsv($output, ['Total Entries', 'Total Calories Logged', 'Avg Cal/Entry']);
        fputcsv($output, [$nutStats['total'], $nutStats['total_cal'], round($nutStats['avg_cal'])]);
        fputcsv($output, []);
        fputcsv($output, ['CATEGORY USAGE']);
        fputcsv($output, ['Category', 'Exercises', 'Times Used']);
        foreach ($catStats as $c) fputcsv($output, [$c['category_name'], $c['exercises'], $c['times_used']]);
        fputcsv($output, []);
        fputcsv($output, ['MONTHLY REGISTRATIONS']);
        fputcsv($output, ['Month', 'Count']);
        foreach ($regData as $r) fputcsv($output, [$r['month'], $r['cnt']]);
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
    $user = getCurrentUser();
    ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>FitTrack Pro Admin - <?= $title ?></title>
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
        h3 { color: #111827; font-size: 16px; margin: 32px 0 8px; border-bottom: 1px solid #e5e7eb; padding-bottom: 8px; }
        table { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 12px; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; }
        th, td { border-bottom: 1px solid #e5e7eb; padding: 10px 14px; text-align: left; font-size: 13px; }
        th { background: #f9fafb; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; color: #6b7280; }
        tr:last-child td { border-bottom: none; }
        tr:nth-child(even) { background: #f9fafb; }
        .stat-row { display: flex; gap: 16px; margin: 16px 0; }
        .stat-box { flex: 1; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; text-align: center; }
        .stat-box .val { font-size: 24px; font-weight: 700; color: #111827; }
        .stat-box .lbl { font-size: 12px; color: #6b7280; margin-top: 4px; }
        .footer { margin-top: 48px; text-align: center; color: #9ca3af; font-size: 12px; border-top: 1px solid #e5e7eb; padding-top: 16px; }
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
            <p class="report-subtitle">Admin — <?= $title ?> Report</p>
        </div>
        <div class="report-meta">
            <strong>Generated By</strong>
            <?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?> (Admin)<br>
            <?= date('M j, Y \a\t g:i A') ?>
        </div>
    </div>

    <?php if (isset($isMultiSection) && $isMultiSection): ?>
        <h3>User Statistics</h3>
        <div class="stat-row">
            <div class="stat-box"><div class="val"><?= $userStats['total_users'] ?></div><div class="lbl">Total Users</div></div>
            <div class="stat-box"><div class="val"><?= $userStats['admins'] ?></div><div class="lbl">Admins</div></div>
            <div class="stat-box"><div class="val"><?= $userStats['new_users'] ?></div><div class="lbl">New (30d)</div></div>
        </div>

        <h3>Activity Statistics</h3>
        <div class="stat-row">
            <div class="stat-box"><div class="val"><?= number_format($actStats['total']) ?></div><div class="lbl">Total Activities</div></div>
            <div class="stat-box"><div class="val"><?= number_format($actStats['total_cal'] ?? 0) ?></div><div class="lbl">Calories Burned</div></div>
            <div class="stat-box"><div class="val"><?= number_format($actStats['total_min'] ?? 0) ?></div><div class="lbl">Minutes Logged</div></div>
        </div>

        <h3>Nutrition Statistics</h3>
        <div class="stat-row">
            <div class="stat-box"><div class="val"><?= number_format($nutStats['total']) ?></div><div class="lbl">Total Entries</div></div>
            <div class="stat-box"><div class="val"><?= number_format($nutStats['total_cal'] ?? 0) ?></div><div class="lbl">Total Calories</div></div>
            <div class="stat-box"><div class="val"><?= round($nutStats['avg_cal'] ?? 0) ?></div><div class="lbl">Avg Cal/Entry</div></div>
        </div>

        <h3>Category Usage</h3>
        <table>
            <thead><tr><th>Category</th><th>Exercises</th><th>Times Used</th></tr></thead>
            <tbody>
                <?php foreach($catStats as $c): ?>
                <tr><td><?= htmlspecialchars($c['category_name']) ?></td><td><?= $c['exercises'] ?></td><td><?= $c['times_used'] ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3>Monthly Registrations</h3>
        <table>
            <thead><tr><th>Month</th><th>Registrations</th></tr></thead>
            <tbody>
                <?php foreach($regData as $r): ?>
                <tr><td><?= date('M Y', strtotime($r['month'].'-01')) ?></td><td><?= $r['cnt'] ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <table>
            <thead><tr><?php foreach ($headers as $h): ?><th><?= $h ?></th><?php endforeach; ?></tr></thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="<?= count($headers) ?>" style="text-align:center;color:#9ca3af;padding:32px;">No data available.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                <tr><?php foreach ($dataKeys as $key): ?><td><?= htmlspecialchars($row[$key] ?? '—') ?></td><?php endforeach; ?></tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="footer">
        <strong>FitTrack Pro</strong> — AdvancedDB Finals Project (Admin Report)<br>
        Document strictly confidential. Generated automatically on <?= date('Y-m-d') ?>.
    </div>
</body>
</html>
    <?php
    exit;
}
?>
