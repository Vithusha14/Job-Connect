<?php
require_once __DIR__ . '/../_bootstrap.php';

$user = requireApiAuth('admin');
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = apiBody();
    $jobId = (int) ($body['job_id'] ?? 0);
    $action = $body['action'] ?? '';
    if ($action === 'approve') {
        $pdo->prepare("UPDATE jobs SET status='approved' WHERE id=?")->execute([$jobId]);
    } elseif ($action === 'reject') {
        $pdo->prepare("UPDATE jobs SET status='rejected' WHERE id=?")->execute([$jobId]);
    } elseif ($action === 'close') {
        $pdo->prepare("UPDATE jobs SET status='closed' WHERE id=?")->execute([$jobId]);
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM jobs WHERE id=?')->execute([$jobId]);
    } else {
        apiError('Invalid action.');
    }
    apiJson(['ok' => true, 'message' => 'Job updated.']);
}

$status = clean($_GET['status'] ?? '');
$where = '1=1';
$params = [];
if (in_array($status, ['pending_approval', 'approved', 'rejected', 'closed'], true)) {
    $where = 'j.status = ?';
    $params[] = $status;
}

$stmt = $pdo->prepare(
    "SELECT j.*, e.company_name, c.name AS category_name,
      (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) AS applicant_count
     FROM jobs j
     JOIN employers e ON e.id = j.employer_id
     JOIN categories c ON c.id = j.category_id
     WHERE {$where}
     ORDER BY j.created_at DESC"
);
$stmt->execute($params);

apiJson(['ok' => true, 'jobs' => $stmt->fetchAll()]);
