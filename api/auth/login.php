<?php
require_once __DIR__ . '/../_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiError('Method not allowed', 405);
}

$body = apiBody();
$email = clean($body['email'] ?? '');
$password = $body['password'] ?? '';

if (!isValidEmail($email) || $password === '') {
    apiError('Valid email and password are required.');
}

$pdo = getDB();
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    apiError('Invalid email or password.', 401);
}
if ($user['status'] !== 'active') {
    apiError('Your account has been blocked.', 403);
}

$payload = userPayload($user);
$token = createApiToken((int) $user['id'], $user['role']);

apiJson([
    'ok' => true,
    'token' => $token,
    'user' => $payload,
    'message' => 'Login successful',
]);
