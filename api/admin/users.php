<?php
require_once __DIR__ . '/../_bootstrap.php';

$user = requireApiAuth('admin');
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = apiBody();
    $userId = (int) ($body['user_id'] ?? 0);
    $action = $body['action'] ?? '';
    $u = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role != 'admin'");
    $u->execute([$userId]);
    if (!$u->fetch()) {
        apiError('User not found.', 404);
    }
    if ($action === 'block') {
        $pdo->prepare("UPDATE users SET status='blocked' WHERE id=?")->execute([$userId]);
    } elseif ($action === 'unblock') {
        $pdo->prepare("UPDATE users SET status='active' WHERE id=?")->execute([$userId]);
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$userId]);
    } else {
        apiError('Invalid action.');
    }
    apiJson(['ok' => true, 'message' => 'User updated.']);
}

$candidates = $pdo->query(
    "SELECT u.id AS user_id, u.email, u.status, u.created_at, c.full_name, c.phone, c.location
     FROM users u JOIN candidates c ON c.user_id = u.id ORDER BY u.created_at DESC"
)->fetchAll();
$employers = $pdo->query(
    "SELECT u.id AS user_id, u.email, u.status, u.created_at, e.company_name, e.industry
     FROM users u JOIN employers e ON e.user_id = u.id ORDER BY u.created_at DESC"
)->fetchAll();

apiJson(['ok' => true, 'candidates' => $candidates, 'employers' => $employers]);
