<?php
require_once __DIR__ . '/../_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiError('Method not allowed', 405);
}

$user = requireApiAuth('candidate');
$candidate = getCandidateByUserId((int) $user['id']);
if (!$candidate) {
    apiError('Candidate profile not found.', 404);
}

$body = apiBody();
$jobId = (int) ($body['job_id'] ?? 0);
$cover = clean($body['cover_letter'] ?? '');

$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ? AND status = 'approved' AND deadline >= CURDATE()");
$stmt->execute([$jobId]);
$job = $stmt->fetch();
if (!$job) {
    apiError('This job is not accepting applications.');
}
if (empty($candidate['resume'])) {
    apiError('Please upload your resume before applying.', 400);
}

$check = $pdo->prepare('SELECT id FROM applications WHERE job_id = ? AND candidate_id = ?');
$check->execute([$jobId, $candidate['id']]);
if ($check->fetch()) {
    apiError('You have already applied to this job.');
}

$pdo->prepare('INSERT INTO applications (job_id, candidate_id, cover_letter, status) VALUES (?, ?, ?, ?)')
    ->execute([$jobId, $candidate['id'], $cover ?: null, 'pending']);

apiJson(['ok' => true, 'message' => 'Application submitted successfully.']);
