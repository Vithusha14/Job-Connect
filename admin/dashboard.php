<?php
/**
 * Admin Dashboard — overall statistics with simple charts
 */
$pageDepth = 1;
require_once __DIR__ . '/../includes/init.php';
requireRole('admin', '../login.php');

$pdo = getDB();

$totalUsers = countRows('users', "role != 'admin'");
$totalCandidates = countRows('candidates');
$totalEmployers = countRows('employers');
$totalJobs = countRows('jobs');
$approvedJobs = countRows('jobs', "status = 'approved'");
$pendingJobs = countRows('jobs', "status = 'pending_approval'");
$totalApps = countRows('applications');
$pendingApps = countRows('applications', "status = 'pending'");

// Applications by status for chart
$appStats = $pdo->query(
    "SELECT status, COUNT(*) AS cnt FROM applications GROUP BY status"
)->fetchAll(PDO::FETCH_KEY_PAIR);

$statusLabels = ['pending', 'shortlisted', 'rejected', 'selected'];
$appCounts = array_map(function ($s) use ($appStats) {
    return (int) ($appStats[$s] ?? 0);
}, $statusLabels);
$maxApp = max(1, max($appCounts));

// Jobs by category
$catStats = $pdo->query(
    "SELECT c.name, COUNT(j.id) AS cnt
     FROM categories c
     LEFT JOIN jobs j ON j.category_id = c.id
     GROUP BY c.id
     ORDER BY cnt DESC
     LIMIT 6"
)->fetchAll();
$catCounts = array_map(function ($r) {
    return (int) $r['cnt'];
}, $catStats ?: [['cnt' => 0]]);
$maxCat = max(1, max($catCounts));
// Recent pending jobs
$pendingList = $pdo->query(
    "SELECT j.id, j.title, j.created_at, e.company_name
     FROM jobs j
     JOIN employers e ON e.id = j.employer_id
     WHERE j.status = 'pending_approval'
     ORDER BY j.created_at ASC
     LIMIT 5"
)->fetchAll();

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <h1>Admin Dashboard</h1>
    <p>Platform overview and moderation tools.</p>
  </div>
</div>

<div class="container">
  <div class="dashboard-layout">
    <?php require __DIR__ . '/_sidebar.php'; ?>

    <div>
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon"><i class="fas fa-users"></i></div>
          <div><strong><?= $totalUsers ?></strong><span>Total Users</span></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon cyan"><i class="fas fa-user-graduate"></i></div>
          <div><strong><?= $totalCandidates ?></strong><span>Candidates</span></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green"><i class="fas fa-building"></i></div>
          <div><strong><?= $totalEmployers ?></strong><span>Employers</span></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon orange"><i class="fas fa-briefcase"></i></div>
          <div><strong><?= $totalJobs ?></strong><span>Job Postings</span></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
          <div><strong><?= $approvedJobs ?></strong><span>Approved Jobs</span></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon orange"><i class="fas fa-hourglass-half"></i></div>
          <div><strong><?= $pendingJobs ?></strong><span>Pending Approval</span></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon cyan"><i class="fas fa-file-alt"></i></div>
          <div><strong><?= $totalApps ?></strong><span>Applications</span></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon red"><i class="fas fa-inbox"></i></div>
          <div><strong><?= $pendingApps ?></strong><span>Pending Reviews</span></div>
        </div>
      </div>

      <div class="charts-grid">
        <div class="chart-box">
          <h3>Applications by Status</h3>
          <div class="bar-chart">
            <?php foreach ($statusLabels as $st):
              $cnt = (int) ($appStats[$st] ?? 0);
              $pct = round(($cnt / $maxApp) * 100);
            ?>
              <div class="bar-row">
                <span><?= ucfirst($st) ?></span>
                <div class="bar-track"><div class="bar-fill" data-width="<?= $pct ?>"></div></div>
                <strong><?= $cnt ?></strong>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="chart-box">
          <h3>Jobs by Category</h3>
          <div class="bar-chart">
            <?php if (empty($catStats)): ?>
              <p class="text-muted">No data yet.</p>
            <?php else: ?>
              <?php foreach ($catStats as $row):
                $pct = round(((int) $row['cnt'] / $maxCat) * 100);
              ?>
                <div class="bar-row">
                  <span title="<?= e($row['name']) ?>"><?= e(excerpt($row['name'], 14)) ?></span>
                  <div class="bar-track"><div class="bar-fill" data-width="<?= $pct ?>"></div></div>
                  <strong><?= (int) $row['cnt'] ?></strong>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="content-panel mt-3">
        <div class="panel-header">
          <h2>Jobs Awaiting Approval</h2>
          <a href="manage-jobs.php?status=pending_approval" class="btn btn-outline btn-sm">View All</a>
        </div>
        <?php if (empty($pendingList)): ?>
          <p class="text-muted">No jobs pending approval. Great work!</p>
        <?php else: ?>
          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr><th>Title</th><th>Company</th><th>Submitted</th><th></th></tr>
              </thead>
              <tbody>
                <?php foreach ($pendingList as $pj): ?>
                  <tr>
                    <td><?= e($pj['title']) ?></td>
                    <td><?= e($pj['company_name']) ?></td>
                    <td><?= e(formatDate($pj['created_at'])) ?></td>
                    <td><a href="manage-jobs.php?status=pending_approval" class="btn btn-primary btn-sm">Review</a></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
