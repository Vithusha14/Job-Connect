<?php
require_once __DIR__ . '/../_bootstrap.php';

$user = requireApiAuth('candidate');
$candidate = getCandidateByUserId((int) $user['id']);
$pdo = getDB();
$filter = clean($_GET['status'] ?? '');

$where = 'a.candidate_id = ?';
$params = [$candidate['id']];
if (in_array($filter, ['pending', 'shortlisted', 'rejected', 'selected'], true)) {
    $where .= ' AND a.status = ?';
    $params[] = $filter;
}

$stmt = $pdo->prepare(
    "SELECT a.*, j.title, j.location, j.job_type, e.company_name
     FROM applications a
     JOIN jobs j ON j.id = a.job_id
     JOIN employers e ON e.id = j.employer_id
     WHERE {$where}
     ORDER BY a.applied_at DESC"
);
$stmt->execute($params);

apiJson(['ok' => true, 'applications' => $stmt->fetchAll()]);
