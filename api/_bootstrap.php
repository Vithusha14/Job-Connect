<?php
/**
 * API bootstrap — JSON REST helpers for Next.js frontend
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

define('API_SECRET', getenv('API_SECRET') ?: 'jobconnect_api_secret_change_me');

header('Content-Type: application/json; charset=utf-8');

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = [
    'http://localhost:3000',
    'http://127.0.0.1:3000',
];
if (in_array($origin, $allowed, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function apiJson($data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function apiError(string $message, int $status = 400, array $extra = []): void
{
    apiJson(array_merge(['ok' => false, 'error' => $message], $extra), $status);
}

function apiBody(): array
{
    $raw = file_get_contents('php://input');
    if ($raw) {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            return $json;
        }
    }
    return $_POST ?: [];
}

function createApiToken(int $userId, string $role): string
{
    $exp = time() + (60 * 60 * 24 * 7); // 7 days
    $payload = $userId . '|' . $role . '|' . $exp;
    $sig = hash_hmac('sha256', $payload, API_SECRET);
    return rtrim(strtr(base64_encode($payload . '|' . $sig), '+/', '-_'), '=');
}

function parseApiToken(?string $token): ?array
{
    if (!$token) {
        return null;
    }
    $token = preg_replace('/^Bearer\s+/i', '', $token);
    $decoded = base64_decode(strtr($token, '-_', '+/'));
    if (!$decoded) {
        return null;
    }
    $parts = explode('|', $decoded);
    if (count($parts) !== 4) {
        return null;
    }
    [$userId, $role, $exp, $sig] = $parts;
    $payload = $userId . '|' . $role . '|' . $exp;
    $expected = hash_hmac('sha256', $payload, API_SECRET);
    if (!hash_equals($expected, $sig)) {
        return null;
    }
    if ((int) $exp < time()) {
        return null;
    }
    return [
        'user_id' => (int) $userId,
        'role' => $role,
    ];
}

function getBearerToken(): ?string
{
    $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if ($hdr) {
        return $hdr;
    }
    if (!empty($_GET['token'])) {
        return (string) $_GET['token'];
    }
    return null;
}

function requireApiAuth(?string $role = null): array
{
    $auth = parseApiToken(getBearerToken());
    if (!$auth) {
        apiError('Unauthorized. Please log in.', 401);
    }

    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT id, email, role, status FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$auth['user_id']]);
    $user = $stmt->fetch();
    if (!$user || $user['status'] !== 'active') {
        apiError('Account blocked or not found.', 403);
    }
    if ($role && $user['role'] !== $role) {
        apiError('Access denied for this role.', 403);
    }

    $user['user_id'] = (int) $user['id'];
    return $user;
}

function userPayload(array $user): array
{
    $pdo = getDB();
    $payload = [
        'id' => (int) $user['id'],
        'email' => $user['email'],
        'role' => $user['role'],
        'status' => $user['status'],
        'display_name' => 'User',
    ];

    if ($user['role'] === 'candidate') {
        $c = getCandidateByUserId((int) $user['id']);
        if ($c) {
            $payload['display_name'] = $c['full_name'];
            $payload['candidate_id'] = (int) $c['id'];
            $payload['profile'] = [
                'id' => (int) $c['id'],
                'full_name' => $c['full_name'],
                'phone' => $c['phone'],
                'photo' => $c['photo'],
                'skills' => $c['skills'],
                'education' => $c['education'],
                'experience' => $c['experience'],
                'resume' => $c['resume'],
                'bio' => $c['bio'],
                'location' => $c['location'],
            ];
        }
    } elseif ($user['role'] === 'employer') {
        $e = getEmployerByUserId((int) $user['id']);
        if ($e) {
            $payload['display_name'] = $e['company_name'];
            $payload['employer_id'] = (int) $e['id'];
            $payload['profile'] = [
                'id' => (int) $e['id'],
                'company_name' => $e['company_name'],
                'logo' => $e['logo'],
                'description' => $e['description'],
                'industry' => $e['industry'],
                'website' => $e['website'],
                'phone' => $e['phone'],
                'address' => $e['address'],
            ];
        }
    } else {
        $payload['display_name'] = 'Administrator';
    }

    return $payload;
}
