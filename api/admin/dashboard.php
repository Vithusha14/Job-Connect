<?php
require_once __DIR__ . '/../_bootstrap.php';

$user = requireApiAuth('admin');
$pdo = getDB();

$totalUsers = countRows('users', "role != 'admin'");
$totalCandidates = countRows('candidates');
$totalEmployers = countRows('employers');
$totalJobs = countRows('jobs');
$approvedJobs = countRows('jobs', "status = 'approved'");
$pendingJobs = countRows('jobs', "status = 'pending_approval'");
$totalApps = countRows('applications');
$pendingApps = countRows('applications', "status = 'pending'");

$appStats = $pdo->query('SELECT status, COUNT(*) AS cnt FROM applications GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR);
$catStats = $pdo->query(
    "SELECT c.name, COUNT(j.id) AS cnt FROM categories c
     LEFT JOIN jobs j ON j.category_id = c.id
     GROUP BY c.id ORDER BY cnt DESC LIMIT 6"
)->fetchAll();

apiJson([
    'ok' => true,
    'stats' => compact(
        'totalUsers', 'totalCandidates', 'totalEmployers', 'totalJobs',
        'approvedJobs', 'pendingJobs', 'totalApps', 'pendingApps'
    ),
    'app_stats' => $appStats ?: new stdClass(),
    'category_stats' => $catStats,
]);
