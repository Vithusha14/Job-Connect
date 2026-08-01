<?php
require_once __DIR__ . '/../_bootstrap.php';

$pdo = getDB();
$q = clean($_GET['q'] ?? '');
$location = clean($_GET['location'] ?? '');
$category = (int) ($_GET['category'] ?? 0);
$jobType = clean($_GET['job_type'] ?? '');
$salaryMin = clean($_GET['salary_min'] ?? '');

$where = ["j.status = 'approved'", 'j.deadline >= CURDATE()'];
$params = [];

if ($q !== '') {
    $where[] = '(j.title LIKE ? OR j.description LIKE ? OR e.company_name LIKE ? OR j.requirements LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like);
}
if ($location !== '') {
    $where[] = 'j.location = ?';
    $params[] = $location;
}
if ($category > 0) {
    $where[] = 'j.category_id = ?';
    $params[] = $category;
}
if ($jobType !== '') {
    $where[] = 'j.job_type = ?';
    $params[] = $jobType;
}
if ($salaryMin !== '' && is_numeric($salaryMin)) {
    $where[] = 'j.salary_max >= ?';
    $params[] = (float) $salaryMin;
}

$sql = "SELECT j.id, j.title, j.description, j.location, j.job_type, j.salary_min, j.salary_max,
               j.deadline, j.created_at, c.name AS category_name, e.company_name, e.logo
        FROM jobs j
        JOIN categories c ON c.id = j.category_id
        JOIN employers e ON e.id = j.employer_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY j.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$locations = $pdo->query("SELECT DISTINCT location FROM jobs WHERE status = 'approved' ORDER BY location")->fetchAll(PDO::FETCH_COLUMN);

apiJson([
    'ok' => true,
    'jobs' => $jobs,
    'filters' => [
        'categories' => $categories,
        'locations' => $locations,
        'job_types' => ['Full-time', 'Part-time', 'Contract', 'Internship', 'Remote'],
    ],
]);
