<?php
require_once __DIR__ . '/../_bootstrap.php';

$user = requireApiAuth('candidate');
$candidate = getCandidateByUserId((int) $user['id']);
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'DELETE' || ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'remove')) {
    $body = apiBody();
    $jobId = (int) ($body['job_id'] ?? $_GET['job_id'] ?? 0);
    $pdo->prepare('DELETE FROM bookmarks WHERE candidate_id = ? AND job_id = ?')->execute([$candidate['id'], $jobId]);
    apiJson(['ok' => true, 'message' => 'Removed from saved jobs.']);
}

$stmt = $pdo->prepare(
    "SELECT b.created_at AS saved_at, j.*, e.company_name, e.logo, c.name AS category_name
     FROM bookmarks b
     JOIN jobs j ON j.id = b.job_id
     JOIN employers e ON e.id = j.employer_id
     JOIN categories c ON c.id = j.category_id
     WHERE b.candidate_id = ?
     ORDER BY b.created_at DESC"
);
$stmt->execute([$candidate['id']]);

apiJson(['ok' => true, 'bookmarks' => $stmt->fetchAll()]);
