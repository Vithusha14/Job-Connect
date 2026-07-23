<?php
/**
 * Shared helper functions for the Online Job Recruitment System.
 */

require_once __DIR__ . '/db.php';

/**
 * Start session if not already started.
 */
function startAppSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }
}

/**
 * Escape output for HTML context.
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect helper.
 */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/**
 * Flash message helpers.
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Render flash alert HTML.
 */
function renderFlash(): string
{
    $flash = getFlash();
    if (!$flash) {
        return '';
    }

    $type = e($flash['type']);
    $msg  = e($flash['message']);
    $icons = [
        'success' => 'fa-check-circle',
        'error'   => 'fa-exclamation-circle',
        'warning' => 'fa-exclamation-triangle',
        'info'    => 'fa-info-circle',
    ];
    $icon = $icons[$flash['type']] ?? 'fa-info-circle';

    return "<div class=\"alert alert-{$type}\" role=\"alert\"><i class=\"fas {$icon}\"></i> {$msg}<button type=\"button\" class=\"alert-close\" aria-label=\"Close\">&times;</button></div>";
}

/**
 * Check if user is logged in.
 */
function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']) && !empty($_SESSION['role']);
}

/**
 * Get current user role.
 */
function currentRole(): ?string
{
    return $_SESSION['role'] ?? null;
}

/**
 * Get current user ID.
 */
function currentUserId(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

/**
 * Require login; redirect if not authenticated.
 */
function requireLogin(string $redirectTo = 'login.php'): void
{
    if (!isLoggedIn()) {
        setFlash('error', 'Please log in to continue.');
        redirect($redirectTo);
    }
}

/**
 * Require a specific role.
 */
function requireRole(string $role, string $redirectTo = 'login.php'): void
{
    requireLogin($redirectTo);
    if (currentRole() !== $role) {
        setFlash('error', 'Access denied. Insufficient permissions.');
        redirect($redirectTo);
    }
}

/**
 * Check if current user account is active.
 */
function requireActiveAccount(): void
{
    if (!isLoggedIn()) {
        return;
    }

    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT status FROM users WHERE id = ?');
    $stmt->execute([currentUserId()]);
    $user = $stmt->fetch();

    if (!$user || $user['status'] !== 'active') {
        session_destroy();
        startAppSession();
        setFlash('error', 'Your account has been blocked. Contact the administrator.');
        redirect(url('login.php'));
    }
}

/**
 * Compute base URL path relative to project root.
 * Works whether the site is at document root or a subdirectory.
 */
function baseUrl(string $path = ''): string
{
    // Detect project root from script location
    static $base = null;

    if ($base === null) {
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        // Strip known subfolders from path
        $base = preg_replace('#/(candidate|employer|admin)(/.*)?$#', '', dirname($script));
        if ($base === '/' || $base === '\\' || $base === '.') {
            $base = '';
        }
        // If we're already at root index, dirname may be the folder itself
        $dir = dirname($script);
        if (preg_match('#/(candidate|employer|admin)$#', $dir)) {
            $base = dirname($dir);
            if ($base === '/' || $base === '\\') {
                $base = '';
            }
        } else {
            $base = $dir === '/' || $dir === '\\' ? '' : $dir;
        }
    }

    $path = ltrim($path, '/');
    return rtrim($base, '/') . ($path !== '' ? '/' . $path : '');
}

/**
 * Asset URL helper.
 */
function asset(string $path): string
{
    return baseUrl('assets/' . ltrim($path, '/'));
}

/**
 * Upload URL helper.
 */
function uploadUrl(string $folder, string $file): string
{
    if (empty($file)) {
        return asset('images/default-avatar.png');
    }
    return baseUrl('uploads/' . $folder . '/' . $file);
}

/**
 * Format salary range for display.
 */
function formatSalary(?float $min, ?float $max): string
{
    if ($min === null && $max === null) {
        return 'Not disclosed';
    }
    if ($min !== null && $max !== null) {
        return '₹' . number_format($min) . ' - ₹' . number_format($max);
    }
    if ($min !== null) {
        return 'From ₹' . number_format($min);
    }
    return 'Up to ₹' . number_format($max);
}

/**
 * Format date for display.
 */
function formatDate(?string $date, string $format = 'd M Y'): string
{
    if (!$date) {
        return '—';
    }
    return date($format, strtotime($date));
}

/**
 * Application status badge class.
 */
function statusBadge(string $status): string
{
    $map = [
        'pending'     => 'badge-warning',
        'shortlisted' => 'badge-info',
        'rejected'    => 'badge-danger',
        'selected'    => 'badge-success',
        'active'      => 'badge-success',
        'closed'      => 'badge-secondary',
        'pending_approval' => 'badge-warning',
        'approved'    => 'badge-success',
        'rejected_job'=> 'badge-danger',
        'blocked'     => 'badge-danger',
    ];
    $class = $map[$status] ?? 'badge-secondary';
    $label = ucwords(str_replace('_', ' ', $status));
    if ($status === 'rejected_job') {
        $label = 'Rejected';
    }
    return '<span class="badge ' . $class . '">' . e($label) . '</span>';
}

/**
 * Validate uploaded file.
 *
 * @return array{ok:bool, error?:string, ext?:string}
 */
function validateUpload(array $file, array $allowedMimes, int $maxSize, array $allowedExts): array
{
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['ok' => false, 'error' => 'Invalid file upload.'];
    }

    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'No file selected.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Upload failed. Please try again.'];
    }

    if ($file['size'] > $maxSize) {
        $mb = round($maxSize / 1024 / 1024, 1);
        return ['ok' => false, 'error' => "File too large. Maximum size is {$mb} MB."];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);

    if (!in_array($mime, $allowedMimes, true)) {
        return ['ok' => false, 'error' => 'Invalid file type.'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts, true)) {
        return ['ok' => false, 'error' => 'Invalid file extension.'];
    }

    return ['ok' => true, 'ext' => $ext];
}

/**
 * Store an uploaded file with a unique name.
 *
 * @return string|false Filename on success
 */
function storeUpload(array $file, string $destDir, string $prefix, string $ext)
{
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }

    $filename = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target   = $destDir . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return false;
    }

    return $filename;
}

/**
 * Delete an uploaded file if it exists.
 */
function deleteUpload(string $dir, ?string $filename): void
{
    if ($filename && file_exists($dir . DIRECTORY_SEPARATOR . $filename)) {
        unlink($dir . DIRECTORY_SEPARATOR . $filename);
    }
}

/**
 * Sanitize text input.
 */
function clean(string $value): string
{
    return trim(strip_tags($value));
}

/**
 * Validate email format.
 */
function isValidEmail(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Get candidate profile by user ID.
 */
function getCandidateByUserId(int $userId): ?array
{
    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT c.*, u.email, u.status AS user_status FROM candidates c JOIN users u ON u.id = c.user_id WHERE c.user_id = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Get employer profile by user ID.
 */
function getEmployerByUserId(int $userId): ?array
{
    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT e.*, u.email, u.status AS user_status FROM employers e JOIN users u ON u.id = e.user_id WHERE e.user_id = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Get all active job categories.
 */
function getCategories(): array
{
    $pdo = getDB();
    return $pdo->query('SELECT * FROM categories ORDER BY name ASC')->fetchAll();
}

/**
 * Count helper for dashboards.
 */
function countRows(string $table, string $where = '1=1', array $params = []): int
{
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE {$where}");
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

/**
 * CSRF token generation and verification.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function verifyCsrf(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/**
 * Truncate text for listings.
 */
function excerpt(string $text, int $length = 120): string
{
    $text = strip_tags($text);
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . '…';
}
