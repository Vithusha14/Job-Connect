<?php
require_once __DIR__ . '/../_bootstrap.php';

$pdo = getDB();
$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    apiError('Job id is required.');
}

$stmt = $pdo->prepare(
    "SELECT j.*, c.name AS category_name, e.company_name, e.logo, e.description AS company_desc,
            e.industry, e.website, e.address
     FROM jobs j
     JOIN categories c ON c.id = j.category_id
     JOIN employers e ON e.id = j.employer_id
     WHERE j.id = ?"
);
$stmt->execute([$id]);
$job = $stmt->fetch();

if (!$job) {
    apiError('Job not found.', 404);
}

$auth = parseApiToken(getBearerToken());
$canView = $job['status'] === 'approved'
    || ($auth && $auth['role'] === 'admin')
    || ($auth && $auth['role'] === 'employer');

if (!$canView) {
    apiError('Job not available.', 404);
}

$hasApplied = false;
$isBookmarked = false;
if ($auth && $auth['role'] === 'candidate') {
    $c = getCandidateByUserId($auth['user_id']);
    if ($c) {
        $s = $pdo->prepare('SELECT id FROM applications WHERE job_id = ? AND candidate_id = ?');
        $s->execute([$id, $c['id']]);
        $hasApplied = (bool) $s->fetch();
        $s = $pdo->prepare('SELECT id FROM bookmarks WHERE job_id = ? AND candidate_id = ?');
        $s->execute([$id, $c['id']]);
        $isBookmarked = (bool) $s->fetch();
    }
}

apiJson([
    'ok' => true,
    'job' => $job,
    'has_applied' => $hasApplied,
    'is_bookmarked' => $isBookmarked,
]);
