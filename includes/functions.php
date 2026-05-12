<?php
/**
 * Helper Functions - Fitness Tracker
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Redirect to a URL
 */
function redirect(string $path): void {
    header("Location: " . BASE_URL . $path);
    exit;
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Require login - redirect to login page if not authenticated
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = 'Please log in to access this page.';
        redirect('/auth/login.php');
    }
}

/**
 * Require admin role
 */
function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        $_SESSION['flash_error'] = 'Access denied. Admin privileges required.';
        redirect('/user/dashboard.php');
    }
}

/**
 * Get current user data from DB
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ? AND deleted_at IS NULL");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

/**
 * Set flash message
 */
function setFlash(string $type, string $message): void {
    $_SESSION["flash_$type"] = $message;
}

/**
 * Get and clear flash message
 */
function getFlash(string $type): ?string {
    $key = "flash_$type";
    $msg = $_SESSION[$key] ?? null;
    unset($_SESSION[$key]);
    return $msg;
}

/**
 * Display flash messages HTML
 */
function displayFlashMessages(): string {
    $html = '';
    foreach (['success', 'error', 'warning', 'info'] as $type) {
        $msg = getFlash($type);
        if ($msg) {
            $icon = match($type) {
                'success' => 'check-circle',
                'error' => 'alert-circle',
                'warning' => 'alert-triangle',
                'info' => 'info',
            };
            $html .= "<div class='flash-message flash-{$type}' id='flash-{$type}'>
                <i data-lucide='{$icon}'></i>
                <span>" . htmlspecialchars($msg) . "</span>
                <button class='flash-close' onclick='this.parentElement.remove()'>&times;</button>
            </div>";
        }
    }
    return $html;
}

/**
 * Sanitize input
 */
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token
 */
function generateCSRF(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRF(): bool {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/**
 * CSRF hidden input field
 */
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRF() . '">';
}

/**
 * Log system action
 */
function logAction(int $userId, string $action, string $details = ''): void {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO system_logs (user_id, action, details, ip_address, user_agent, created_at) 
                          VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([
        $userId,
        $action,
        $details,
        $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255)
    ]);
}

/**
 * Generate random verification code
 */
function generateCode(int $length = 6): string {
    return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

/**
 * Format date for display
 */
function formatDate(string $date, string $format = 'M j, Y'): string {
    return date($format, strtotime($date));
}

/**
 * Format number with suffix (1.2k, 3.4M, etc.)
 */
function formatNumber(float $num): string {
    if ($num >= 1000000) return round($num / 1000000, 1) . 'M';
    if ($num >= 1000) return round($num / 1000, 1) . 'k';
    return number_format($num, $num == intval($num) ? 0 : 1);
}

/**
 * Calculate BMI
 */
function calculateBMI(float $weightKg, float $heightCm): float {
    $heightM = $heightCm / 100;
    return round($weightKg / ($heightM * $heightM), 2);
}

/**
 * Get BMI category
 */
function getBMICategory(float $bmi): string {
    if ($bmi < 18.5) return 'Underweight';
    if ($bmi < 25) return 'Normal';
    if ($bmi < 30) return 'Overweight';
    return 'Obese';
}

/**
 * Pagination helper
 */
function paginate(int $total, int $perPage = 10, int $currentPage = 1): array {
    $totalPages = max(1, ceil($total / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;
    
    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages,
    ];
}

/**
 * Render pagination HTML
 */
function renderPagination(array $pagination, string $baseUrl): string {
    if ($pagination['total_pages'] <= 1) return '';
    
    $html = '<div class="pagination">';
    
    if ($pagination['has_prev']) {
        $html .= '<a href="' . $baseUrl . '&page=' . ($pagination['current_page'] - 1) . '" class="pagination-btn"><i data-lucide="chevron-left"></i></a>';
    }
    
    for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++) {
        $active = $i === $pagination['current_page'] ? 'active' : '';
        $html .= '<a href="' . $baseUrl . '&page=' . $i . '" class="pagination-btn ' . $active . '">' . $i . '</a>';
    }
    
    if ($pagination['has_next']) {
        $html .= '<a href="' . $baseUrl . '&page=' . ($pagination['current_page'] + 1) . '" class="pagination-btn"><i data-lucide="chevron-right"></i></a>';
    }
    
    $html .= '</div>';
    return $html;
}

/**
 * Time ago helper
 */
function timeAgo(string $datetime): string {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->y > 0) return $diff->y . 'y ago';
    if ($diff->m > 0) return $diff->m . 'mo ago';
    if ($diff->d > 0) return $diff->d . 'd ago';
    if ($diff->h > 0) return $diff->h . 'h ago';
    if ($diff->i > 0) return $diff->i . 'm ago';
    return 'Just now';
}
