<?php
require_once __DIR__ . '/../_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiError('Method not allowed', 405);
}

$body = apiBody();
$role = ($body['role'] ?? 'candidate') === 'employer' ? 'employer' : 'candidate';
$email = clean($body['email'] ?? '');
$password = $body['password'] ?? '';
$confirm = $body['confirm_password'] ?? '';
$fullName = clean($body['full_name'] ?? '');
$phone = clean($body['phone'] ?? '');
$company = clean($body['company_name'] ?? '');
$industry = clean($body['industry'] ?? '');

if (!isValidEmail($email)) {
    apiError('Please enter a valid email.');
}
if (strlen($password) < 6) {
    apiError('Password must be at least 6 characters.');
}
if ($password !== $confirm) {
    apiError('Passwords do not match.');
}
if ($role === 'candidate' && $fullName === '') {
    apiError('Full name is required.');
}
if ($role === 'employer' && $company === '') {
    apiError('Company name is required.');
}

$pdo = getDB();
$check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$check->execute([$email]);
if ($check->fetch()) {
    apiError('An account with this email already exists.');
}

try {
    $pdo->beginTransaction();
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (email, password, role, status) VALUES (?, ?, ?, ?)');
    $stmt->execute([$email, $hash, $role, 'active']);
    $userId = (int) $pdo->lastInsertId();

    if ($role === 'candidate') {
        $pdo->prepare('INSERT INTO candidates (user_id, full_name, phone) VALUES (?, ?, ?)')
            ->execute([$userId, $fullName, $phone ?: null]);
    } else {
        $pdo->prepare('INSERT INTO employers (user_id, company_name, industry, phone) VALUES (?, ?, ?, ?)')
            ->execute([$userId, $company, $industry ?: null, $phone ?: null]);
    }
    $pdo->commit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    apiError('Registration failed. Please try again.', 500);
}

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();
$payload = userPayload($user);
$token = createApiToken($userId, $role);

apiJson([
    'ok' => true,
    'token' => $token,
    'user' => $payload,
    'message' => 'Account created successfully',
], 201);
