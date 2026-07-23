<?php
/**
 * Employer Dashboard
 */
$pageDepth = 1;
require_once __DIR__ . '/../includes/init.php';
requireRole('employer', '../login.php');

$pdo = getDB();
$employer = getEmployerByUserId(currentUserId());
if (!$employer) {
    setFlash('error', 'Employer profile not found.');
    redirect('../logout.php');
}
$_SESSION['employer_id'] = (int) $employer['id'];
$eid = (int) $employer['id'];

$totalJobs = countRows('jobs', 'employer_id = ?', [$eid]);
$activeJobs = countRows('jobs', "employer_id = ? AND status = 'approved'", [$eid]);
$pendingJobs = countRows('jobs', "employer_id = ? AND status = 'pending_approval'", [$eid]);

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM applications a
     JOIN jobs j ON j.id = a.job_id
     WHERE j.employer_id = ?"
);
$stmt->execute([$eid]);
$totalApplicants = (int) $stmt->fetchColumn();

$recentJobs = $pdo->prepare(
    "SELECT j.*,
      (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) AS applicant_count
     FROM jobs j
     WHERE j.employer_id = ?
     ORDER BY j.created_at DESC
     LIMIT 5"
);
$recentJobs->execute([$eid]);
$jobs = $recentJobs->fetchAll();

$pageTitle = 'Employer Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <h1><?= e($employer['company_name']) ?></h1>
    <p>Manage job postings and review applicants.</p>
  </div>
</div>

<div class="container">
  <div class="dashboard-layout">
    <?php require __DIR__ . '/_sidebar.php'; ?>

    <div>
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon"><i class="fas fa-briefcase"></i></div>
          <div><strong><?= $totalJobs ?></strong><span>Jobs Posted</span></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
          <div><strong><?= $activeJobs ?></strong><span>Active Listings</span></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon orange"><i class="fas fa-clock"></i></div>
          <div><strong><?= $pendingJobs ?></strong><span>Awaiting Approval</span></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon cyan"><i class="fas fa-users"></i></div>
          <div><strong><?= $totalApplicants ?></strong><span>Total Applicants</span></div>
        </div>
      </div>

      <div class="content-panel">
        <div class="panel-header">
          <h2>Recent Job Postings</h2>
          <a href="post-job.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Post Job</a>
        </div>

        <?php if (empty($jobs)): ?>
          <div class="empty-state">
            <i class="fas fa-briefcase"></i>
            <h3>No jobs posted yet</h3>
            <p>Create your first job listing to start receiving applications.</p>
            <a href="post-job.php" class="btn btn-primary mt-2">Post a Job</a>
          </div>
        <?php else: ?>
          <div class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Title</th>
                  <th>Location</th>
                  <th>Applicants</th>
                  <th>Deadline</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($jobs as $job): ?>
                  <tr>
                    <td><?= e($job['title']) ?></td>
                    <td><?= e($job['location']) ?></td>
                    <td><?= (int) $job['applicant_count'] ?></td>
                    <td><?= e(formatDate($job['deadline'])) ?></td>
                    <td><?= statusBadge($job['status']) ?></td>
                    <td class="actions">
                      <a href="applicants.php?job_id=<?= (int) $job['id'] ?>" class="btn btn-ghost btn-sm">Applicants</a>
                      <a href="edit-job.php?id=<?= (int) $job['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
                    </td>
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
