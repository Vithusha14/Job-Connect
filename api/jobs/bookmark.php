<?php
require_once __DIR__ . '/../_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiError('Method not allowed', 405);
}

$user = requireApiAuth('candidate');
$candidate = getCandidateByUserId((int) $user['id']);
$body = apiBody();
$jobId = (int) ($body['job_id'] ?? 0);
$pdo = getDB();

$s = $pdo->prepare('SELECT id FROM bookmarks WHERE job_id = ? AND candidate_id = ?');
$s->execute([$jobId, $candidate['id']]);
if ($s->fetch()) {
    $pdo->prepare('DELETE FROM bookmarks WHERE job_id = ? AND candidate_id = ?')->execute([$jobId, $candidate['id']]);
    apiJson(['ok' => true, 'bookmarked' => false, 'message' => 'Removed from saved jobs.']);
}

$pdo->prepare('INSERT IGNORE INTO bookmarks (candidate_id, job_id) VALUES (?, ?)')->execute([$candidate['id'], $jobId]);
apiJson(['ok' => true, 'bookmarked' => true, 'message' => 'Job saved.']);
