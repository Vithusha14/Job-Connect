<?php
/**
 * Candidate Dashboard
 */
$pageDepth = 1;
require_once __DIR__ . '/../includes/init.php';
requireRole('candidate', '../login.php');

$pdo = getDB();
$candidate = getCandidateByUserId(currentUserId());
if (!$candidate) {
    setFlash('error', 'Candidate profile not found.');
    redirect('../logout.php');
}
$_SESSION['candidate_id'] = (int) $candidate['id'];
$cid = (int) $candidate['id'];

$totalApps = countRows('applications', 'candidate_id = ?', [$cid]);
$pending = countRows('applications', "candidate_id = ? AND status = 'pending'", [$cid]);
$shortlisted = countRows('applications', "candidate_id = ? AND status = 'shortlisted'", [$cid]);
$selected = countRows('applications', "candidate_id = ? AND status = 'selected'", [$cid]);
$bookmarks = countRows('bookmarks', 'candidate_id = ?', [$cid]);

$recent = $pdo->prepare(
    "SELECT a.*, j.title, j.location, e.company_name
     FROM applications a
     JOIN jobs j ON j.id = a.job_id
     JOIN employers e ON e.id = j.employer_id
     WHERE a.candidate_id = ?
     ORDER BY a.applied_at DESC
     LIMIT 5"
);
$recent->execute([$cid]);
$recentApps = $recent->fetchAll();

$pageTitle = 'Candidate Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <h1>Welcome, <?= e($candidate['full_name']) ?></h1>
    <p>Track applications, update your profile, and discover new roles.</p>
  </div>
</div>

<div class="container">
  <div class="dashboard-layout">
    <?php require __DIR__ . '/_sidebar.php'; ?>

    <div>
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
          <div><strong><?= $totalApps ?></strong><span>Total Applications</span></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon orange"><i class="fas fa-hourglass-half"></i></div>
          <div><strong><?= $pending ?></strong><span>Pending</span></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon cyan"><i class="fas fa-star"></i></div>
          <div><strong><?= $shortlisted ?></strong><span>Shortlisted</span></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green"><i class="fas fa-check-double"></i></div>
          <div><strong><?= $selected ?></strong><span>Selected</span></div>
        </div>
      </div>

      <?php if (empty($candidate['resume']) || empty($candidate['skills'])): ?>
        <div class="alert alert-warning mb-2">
          <i class="fas fa-exclamation-triangle"></i>
          Complete your profile and upload a resume to apply for jobs.
          <a href="profile.php" class="btn btn-sm btn-outline" style="margin-left:0.5rem;">Update Profile</a>
        </div>
      <?php endif; ?>

      <div class="content-panel">
        <div class="panel-header">
          <h2>Recent Applications</h2>
          <a href="applications.php" class="btn btn-outline btn-sm">View All</a>
        </div>

        <?php if (empty($recentApps)): ?>
          <div class="empty-state">
            <i class="fas fa-briefcase"></i>
            <h3>No applications yet</h3>
            <p>Browse open positions and apply with one click.</p>
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
                  <th>Applied</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentApps as $app): ?>
                  <tr>
                    <td><a href="../job-details.php?id=<?= (int) $app['job_id'] ?>"><?= e($app['title']) ?></a></td>
                    <td><?= e($app['company_name']) ?></td>
                    <td><?= e($app['location']) ?></td>
                    <td><?= e(formatDate($app['applied_at'])) ?></td>
                    <td><?= statusBadge($app['status']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <div class="content-panel mt-2">
        <p class="text-muted"><i class="fas fa-bookmark"></i> You have <strong><?= $bookmarks ?></strong> saved job<?= $bookmarks === 1 ? '' : 's' ?>. <a href="bookmarks.php">Manage bookmarks</a></p>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
