<?php
require_once __DIR__ . '/../_bootstrap.php';

$user = requireApiAuth('employer');
$employer = getEmployerByUserId((int) $user['id']);
$pdo = getDB();
$jobId = (int) ($_GET['job_id'] ?? 0);

$job = $pdo->prepare('SELECT * FROM jobs WHERE id = ? AND employer_id = ?');
$job->execute([$jobId, $employer['id']]);
$jobRow = $job->fetch();
if (!$jobRow) {
    apiError('Job not found.', 404);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = apiBody();
    $appId = (int) ($body['application_id'] ?? 0);
    $status = clean($body['status'] ?? '');
    if (!in_array($status, ['pending', 'shortlisted', 'rejected', 'selected'], true)) {
        apiError('Invalid status.');
    }
    $pdo->prepare(
        "UPDATE applications a JOIN jobs j ON j.id = a.job_id
         SET a.status = ? WHERE a.id = ? AND j.employer_id = ?"
    )->execute([$status, $appId, $employer['id']]);
    apiJson(['ok' => true, 'message' => 'Status updated.']);
}

$apps = $pdo->prepare(
    "SELECT a.*, c.full_name, c.phone, c.skills, c.resume, c.photo, u.email
     FROM applications a
     JOIN candidates c ON c.id = a.candidate_id
     JOIN users u ON u.id = c.user_id
     WHERE a.job_id = ?
     ORDER BY a.applied_at DESC"
);
$apps->execute([$jobId]);

apiJson(['ok' => true, 'job' => $jobRow, 'applicants' => $apps->fetchAll()]);
