<?php
require_once __DIR__ . '/../_bootstrap.php';

$user = requireApiAuth('candidate');
$candidate = getCandidateByUserId((int) $user['id']);
$cid = (int) $candidate['id'];
$pdo = getDB();

$totalApps = countRows('applications', 'candidate_id = ?', [$cid]);
$pending = countRows('applications', "candidate_id = ? AND status = 'pending'", [$cid]);
$shortlisted = countRows('applications', "candidate_id = ? AND status = 'shortlisted'", [$cid]);
$selected = countRows('applications', "candidate_id = ? AND status = 'selected'", [$cid]);
$bookmarks = countRows('bookmarks', 'candidate_id = ?', [$cid]);

$recent = $pdo->prepare(
    "SELECT a.id, a.status, a.applied_at, j.id AS job_id, j.title, j.location, e.company_name
     FROM applications a
     JOIN jobs j ON j.id = a.job_id
     JOIN employers e ON e.id = j.employer_id
     WHERE a.candidate_id = ?
     ORDER BY a.applied_at DESC LIMIT 5"
);
$recent->execute([$cid]);

apiJson([
    'ok' => true,
    'stats' => compact('totalApps', 'pending', 'shortlisted', 'selected', 'bookmarks'),
    'recent' => $recent->fetchAll(),
    'profile' => userPayload($user)['profile'] ?? null,
]);
