<?php
/**
 * Candidate — My Applications
 */
$pageDepth = 1;
require_once __DIR__ . '/../includes/init.php';
requireRole('candidate', '../login.php');

$pdo = getDB();
$candidate = getCandidateByUserId(currentUserId());
$cid = (int) $candidate['id'];
$_SESSION['candidate_id'] = $cid;

$filter = clean($_GET['status'] ?? '');
$where = 'a.candidate_id = ?';
$params = [$cid];
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
$apps = $stmt->fetchAll();

$pageTitle = 'My Applications';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <h1>My Applications</h1>
    <p>Track the status of every job you have applied to.</p>
  </div>
</div>

<div class="container">
  <div class="dashboard-layout">
    <?php require __DIR__ . '/_sidebar.php'; ?>

    <div class="content-panel">
      <div class="panel-header">
        <h2>Applications (<?= count($apps) ?>)</h2>
        <form method="GET" class="flex gap-1 align-center">
          <select name="status" class="form-control" style="width:auto;" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <?php foreach (['pending', 'shortlisted', 'rejected', 'selected'] as $st): ?>
              <option value="<?= $st ?>" <?= $filter === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>

      <?php if (empty($apps)): ?>
        <div class="empty-state">
          <i class="fas fa-folder-open"></i>
          <h3>No applications found</h3>
          <p>Start applying to jobs that match your skills.</p>
          <a href="../jobs.php" class="btn btn-primary mt-2">Browse Jobs</a>
        </div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Job Title</th>
                <th>Company</th>
                <th>Location</th>
                <th>Type</th>
                <th>Applied On</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($apps as $app): ?>
                <tr>
                  <td><?= e($app['title']) ?></td>
                  <td><?= e($app['company_name']) ?></td>
                  <td><?= e($app['location']) ?></td>
                  <td><?= e($app['job_type']) ?></td>
                  <td><?= e(formatDate($app['applied_at'])) ?></td>
                  <td><?= statusBadge($app['status']) ?></td>
                  <td><a href="../job-details.php?id=<?= (int) $app['job_id'] ?>" class="btn btn-ghost btn-sm">View</a></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
