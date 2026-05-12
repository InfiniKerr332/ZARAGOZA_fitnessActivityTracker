<?php
$pageTitle = 'System Logs';
require_once __DIR__ . '/../includes/header.php';
requireAdmin();
$db = getDB();

$search = trim($_GET['search'] ?? '');
$actionFilter = $_GET['action_filter'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

$where = "1=1";
$params = [];

if ($search) {
    $where .= " AND (u.username LIKE ? OR l.details LIKE ? OR l.ip_address LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($actionFilter) {
    $where .= " AND l.action = ?";
    $params[] = $actionFilter;
}

$countStmt = $db->prepare("SELECT COUNT(*) FROM system_logs l JOIN users u ON l.user_id = u.user_id WHERE $where");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$pagination = paginate($total, $perPage, $page);

// JOIN: logs with users
$stmt = $db->prepare("SELECT l.*, u.username, u.first_name, u.last_name 
    FROM system_logs l 
    JOIN users u ON l.user_id = u.user_id 
    WHERE $where 
    ORDER BY l.created_at DESC 
    LIMIT $perPage OFFSET {$pagination['offset']}");
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get unique actions for filter
$actions = $db->query("SELECT DISTINCT action FROM system_logs ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="section-header">
    <h2>System Logs</h2>
</div>

<div class="toolbar">
    <form method="GET" style="display:flex; gap:8px; width:100%; flex-wrap:wrap; align-items:center;">
        <div class="toolbar-search" style="flex:1; margin:0; min-width:200px;">
            <i data-lucide="search"></i>
            <input type="text" name="search" class="form-control" placeholder="Search logs..." value="<?= sanitize($search) ?>">
        </div>
        <select name="action_filter" class="form-control" style="width:auto;">
            <option value="">All Actions</option>
            <?php foreach ($actions as $a): ?>
            <option value="<?= sanitize($a) ?>" <?= $actionFilter === $a ? 'selected' : '' ?>><?= sanitize($a) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary btn-sm" style="padding:0.5rem 1rem;">Filter</button>
        <a href="system-logs.php" class="btn btn-outline btn-sm" style="padding:0.5rem 1rem;">Reset</a>
    </form>
</div>

<div class="card">
    <?php if (empty($logs)): ?>
    <div class="empty-state">
        <i data-lucide="scroll-text"></i>
        <h3>No logs found</h3>
        <p>No system logs match your criteria.</p>
    </div>
    <?php else: ?>
    <div class="table-container" style="border:none">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Details</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $l): ?>
                <tr>
                    <td style="white-space:nowrap"><?= formatDate($l['created_at'], 'M j, Y H:i:s') ?></td>
                    <td>
                        <strong><?= sanitize($l['first_name'] . ' ' . $l['last_name']) ?></strong><br>
                        <small style="color:var(--text-muted)">@<?= sanitize($l['username']) ?></small>
                    </td>
                    <td><span class="badge badge-secondary"><?= sanitize($l['action']) ?></span></td>
                    <td><?= sanitize($l['details']) ?></td>
                    <td><span style="font-family:monospace;font-size:12px;color:var(--text-secondary)"><?= sanitize($l['ip_address']) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?= renderPagination($pagination, '?search=' . urlencode($search) . '&action_filter=' . urlencode($actionFilter)) ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
