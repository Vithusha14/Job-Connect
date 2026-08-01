<?php
require_once __DIR__ . '/_bootstrap.php';

$pdo = getDB();

// Home stats + featured jobs + categories
$totalJobs = (int) $pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'approved' AND deadline >= CURDATE()")->fetchColumn();
$totalCompanies = (int) $pdo->query('SELECT COUNT(*) FROM employers')->fetchColumn();
$totalCandidates = (int) $pdo->query('SELECT COUNT(*) FROM candidates')->fetchColumn();

$featured = $pdo->query(
    "SELECT j.id, j.title, j.location, j.job_type, j.salary_min, j.salary_max, j.deadline,
            c.name AS category_name, e.company_name, e.logo
     FROM jobs j
     JOIN categories c ON c.id = j.category_id
     JOIN employers e ON e.id = j.employer_id
     WHERE j.status = 'approved' AND j.deadline >= CURDATE()
     ORDER BY j.created_at DESC
     LIMIT 6"
)->fetchAll();

$categories = $pdo->query(
    "SELECT cat.id, cat.name, cat.icon, cat.description, COUNT(j.id) AS job_count
     FROM categories cat
     LEFT JOIN jobs j ON j.category_id = cat.id AND j.status = 'approved' AND j.deadline >= CURDATE()
     GROUP BY cat.id
     ORDER BY cat.name ASC"
)->fetchAll();

$locations = $pdo->query("SELECT DISTINCT location FROM jobs WHERE status = 'approved' ORDER BY location")->fetchAll(PDO::FETCH_COLUMN);

apiJson([
    'ok' => true,
    'stats' => [
        'jobs' => $totalJobs,
        'companies' => $totalCompanies,
        'candidates' => $totalCandidates,
    ],
    'featured' => $featured,
    'categories' => $categories,
    'locations' => $locations,
]);
