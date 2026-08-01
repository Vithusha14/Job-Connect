<?php
require_once __DIR__ . '/../_bootstrap.php';

$user = requireApiAuth('employer');
$employer = getEmployerByUserId((int) $user['id']);
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $cats = getCategories();
    $jobs = $pdo->prepare(
        "SELECT j.*, cat.name AS category_name,
          (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) AS applicant_count
         FROM jobs j JOIN categories cat ON cat.id = j.category_id
         WHERE j.employer_id = ? ORDER BY j.created_at DESC"
    );
    $jobs->execute([$employer['id']]);
    apiJson(['ok' => true, 'jobs' => $jobs->fetchAll(), 'categories' => $cats]);
}

$body = apiBody();
$action = $body['action'] ?? $_GET['action'] ?? '';

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = clean($body['title'] ?? '');
    $categoryId = (int) ($body['category_id'] ?? 0);
    $location = clean($body['location'] ?? '');
    $jobType = clean($body['job_type'] ?? 'Full-time');
    $deadline = $body['deadline'] ?? '';
    $description = trim($body['description'] ?? '');
    $requirements = trim($body['requirements'] ?? '');
    $vacancies = max(1, (int) ($body['vacancies'] ?? 1));
    $salaryMin = $body['salary_min'] !== '' && isset($body['salary_min']) ? (float) $body['salary_min'] : null;
    $salaryMax = $body['salary_max'] !== '' && isset($body['salary_max']) ? (float) $body['salary_max'] : null;

    if ($title === '' || $categoryId <= 0 || $location === '' || $description === '' || $requirements === '' || $deadline === '') {
        apiError('Please fill all required job fields.');
    }

    $pdo->prepare(
        'INSERT INTO jobs (employer_id, category_id, title, description, requirements, salary_min, salary_max, location, job_type, deadline, status, vacancies)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $employer['id'], $categoryId, $title, $description, $requirements,
        $salaryMin, $salaryMax, $location, $jobType, $deadline, 'pending_approval', $vacancies,
    ]);
    apiJson(['ok' => true, 'message' => 'Job submitted for admin approval.', 'id' => (int) $pdo->lastInsertId()], 201);
}

if (in_array($action, ['close', 'delete', 'reopen'], true) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $jobId = (int) ($body['job_id'] ?? 0);
    $check = $pdo->prepare('SELECT id FROM jobs WHERE id = ? AND employer_id = ?');
    $check->execute([$jobId, $employer['id']]);
    if (!$check->fetch()) {
        apiError('Job not found.', 404);
    }
    if ($action === 'close') {
        $pdo->prepare("UPDATE jobs SET status='closed' WHERE id=?")->execute([$jobId]);
    } elseif ($action === 'reopen') {
        $pdo->prepare("UPDATE jobs SET status='pending_approval' WHERE id=?")->execute([$jobId]);
    } else {
        $pdo->prepare('DELETE FROM jobs WHERE id=?')->execute([$jobId]);
    }
    apiJson(['ok' => true, 'message' => 'Job updated.']);
}

apiError('Invalid action.');
