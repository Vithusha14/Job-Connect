<?php
require_once __DIR__ . '/../_bootstrap.php';

$user = requireApiAuth('employer');
$employer = getEmployerByUserId((int) $user['id']);
$eid = (int) $employer['id'];
$pdo = getDB();

$totalJobs = countRows('jobs', 'employer_id = ?', [$eid]);
$activeJobs = countRows('jobs', "employer_id = ? AND status = 'approved'", [$eid]);
$pendingJobs = countRows('jobs', "employer_id = ? AND status = 'pending_approval'", [$eid]);
$stmt = $pdo->prepare('SELECT COUNT(*) FROM applications a JOIN jobs j ON j.id = a.job_id WHERE j.employer_id = ?');
$stmt->execute([$eid]);
$totalApplicants = (int) $stmt->fetchColumn();

$jobs = $pdo->prepare(
    "SELECT j.*, (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) AS applicant_count
     FROM jobs j WHERE j.employer_id = ? ORDER BY j.created_at DESC LIMIT 10"
);
$jobs->execute([$eid]);

apiJson([
    'ok' => true,
    'stats' => [
        'totalJobs' => $totalJobs,
        'activeJobs' => $activeJobs,
        'pendingJobs' => $pendingJobs,
        'totalApplicants' => $totalApplicants,
    ],
    'jobs' => $jobs->fetchAll(),
    'profile' => userPayload($user)['profile'] ?? null,
]);
