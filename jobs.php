<?php
/**
 * Public job listing with search & filters
 */
$pageDepth = 0;
require_once __DIR__ . '/includes/init.php';

$pdo = getDB();

$q         = clean($_GET['q'] ?? '');
$location  = clean($_GET['location'] ?? '');
$category  = (int) ($_GET['category'] ?? 0);
$jobType   = clean($_GET['job_type'] ?? '');
$salaryMin = clean($_GET['salary_min'] ?? '');

$where  = ["j.status = 'approved'", 'j.deadline >= CURDATE()'];
$params = [];

if ($q !== '') {
    $where[] = '(j.title LIKE ? OR j.description LIKE ? OR e.company_name LIKE ? OR j.requirements LIKE ?)';
    $like = '%' . $q . '%';
    $params = array_merge($params, [$like, $like, $like, $like]);
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

$sql = 'SELECT j.*, c.name AS category_name, e.company_name, e.logo
        FROM jobs j
        JOIN categories c ON c.id = j.category_id
        JOIN employers e ON e.id = j.employer_id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY j.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

$categories = getCategories();
$locations = $pdo->query("SELECT DISTINCT location FROM jobs WHERE status = 'approved' ORDER BY location")->fetchAll(PDO::FETCH_COLUMN);
$jobTypes = ['Full-time', 'Part-time', 'Contract', 'Internship', 'Remote'];

$pageTitle = 'Browse Jobs';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <div class="breadcrumb"><a href="index.php">Home</a> / <span>Jobs</span></div>
    <h1>Browse Jobs</h1>
    <p><?= count($jobs) ?> opening<?= count($jobs) === 1 ? '' : 's' ?> found</p>
  </div>
</div>

<div class="container">
  <form class="filter-bar" method="GET" action="jobs.php">
    <div class="form-group">
      <label for="q">Keywords</label>
      <input type="text" id="q" name="q" class="form-control" value="<?= e($q) ?>" placeholder="Title, skill, company…">
    </div>
    <div class="form-group">
      <label for="location">Location</label>
      <select id="location" name="location" class="form-control">
        <option value="">All</option>
        <?php foreach ($locations as $loc): ?>
          <option value="<?= e($loc) ?>" <?= $location === $loc ? 'selected' : '' ?>><?= e($loc) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label for="category">Category</label>
      <select id="category" name="category" class="form-control">
        <option value="">All</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= (int) $cat['id'] ?>" <?= $category === (int) $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label for="job_type">Job Type</label>
      <select id="job_type" name="job_type" class="form-control">
        <option value="">All</option>
        <?php foreach ($jobTypes as $jt): ?>
          <option value="<?= e($jt) ?>" <?= $jobType === $jt ? 'selected' : '' ?>><?= e($jt) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label for="salary_min">Min Salary (₹)</label>
      <input type="number" id="salary_min" name="salary_min" class="form-control" value="<?= e($salaryMin) ?>" placeholder="e.g. 300000" min="0" step="10000">
    </div>
    <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
  </form>

  <?php if (empty($jobs)): ?>
    <div class="content-panel">
      <div class="empty-state">
        <i class="fas fa-search"></i>
        <h3>No jobs match your filters</h3>
        <p>Try adjusting your search criteria.</p>
        <a href="jobs.php" class="btn btn-outline mt-2">Clear Filters</a>
      </div>
    </div>
  <?php else: ?>
    <div class="jobs-grid">
      <?php foreach ($jobs as $job): ?>
        <article class="job-card">
          <div class="job-card-top">
            <div class="company-logo">
              <?php if ($job['logo']): ?>
                <img src="<?= e(url('uploads/logos/' . $job['logo'])) ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:10px;">
              <?php else: ?>
                <?= e(strtoupper(substr($job['company_name'], 0, 1))) ?>
              <?php endif; ?>
            </div>
            <div>
              <h3><a href="job-details.php?id=<?= (int) $job['id'] ?>"><?= e($job['title']) ?></a></h3>
              <div class="text-muted" style="font-size:0.85rem;"><?= e($job['company_name']) ?></div>
            </div>
          </div>
          <p class="text-muted" style="font-size:0.9rem;"><?= e(excerpt($job['description'], 100)) ?></p>
          <div class="job-meta">
            <span><i class="fas fa-map-marker-alt"></i> <?= e($job['location']) ?></span>
            <span><i class="fas fa-clock"></i> <?= e($job['job_type']) ?></span>
            <span><i class="fas fa-calendar"></i> <?= e(formatDate($job['deadline'])) ?></span>
          </div>
          <div class="job-card-footer">
            <span class="salary"><?= e(formatSalary($job['salary_min'], $job['salary_max'])) ?></span>
            <a href="job-details.php?id=<?= (int) $job['id'] ?>" class="btn btn-primary btn-sm">Apply</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
