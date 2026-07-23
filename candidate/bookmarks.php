<?php
/**
 * Candidate — Saved / Bookmarked Jobs
 */
$pageDepth = 1;
require_once __DIR__ . '/../includes/init.php';
requireRole('candidate', '../login.php');

$pdo = getDB();
$candidate = getCandidateByUserId(currentUserId());
$cid = (int) $candidate['id'];
$_SESSION['candidate_id'] = $cid;

// Remove bookmark
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $jobId = (int) ($_POST['job_id'] ?? 0);
    $pdo->prepare('DELETE FROM bookmarks WHERE candidate_id = ? AND job_id = ?')->execute([$cid, $jobId]);
    setFlash('success', 'Job removed from saved list.');
    redirect('bookmarks.php');
}

$stmt = $pdo->prepare(
    "SELECT b.id AS bookmark_id, b.created_at AS saved_at, j.*, e.company_name, e.logo, c.name AS category_name
     FROM bookmarks b
     JOIN jobs j ON j.id = b.job_id
     JOIN employers e ON e.id = j.employer_id
     JOIN categories c ON c.id = j.category_id
     WHERE b.candidate_id = ?
     ORDER BY b.created_at DESC"
);
$stmt->execute([$cid]);
$saved = $stmt->fetchAll();

$pageTitle = 'Saved Jobs';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <h1>Saved Jobs</h1>
    <p>Jobs you have bookmarked for later.</p>
  </div>
</div>

<div class="container">
  <div class="dashboard-layout">
    <?php require __DIR__ . '/_sidebar.php'; ?>

    <div>
      <?php if (empty($saved)): ?>
        <div class="content-panel">
          <div class="empty-state">
            <i class="fas fa-bookmark"></i>
            <h3>No saved jobs</h3>
            <p>Tap the bookmark button on any job to save it here.</p>
            <a href="../jobs.php" class="btn btn-primary mt-2">Browse Jobs</a>
          </div>
        </div>
      <?php else: ?>
        <div class="jobs-grid">
          <?php foreach ($saved as $job): ?>
            <article class="job-card">
              <div class="job-card-top">
                <div class="company-logo"><?= e(strtoupper(substr($job['company_name'], 0, 1))) ?></div>
                <div>
                  <h3><a href="../job-details.php?id=<?= (int) $job['id'] ?>"><?= e($job['title']) ?></a></h3>
                  <div class="text-muted" style="font-size:0.85rem;"><?= e($job['company_name']) ?></div>
                </div>
              </div>
              <div class="job-meta">
                <span><i class="fas fa-map-marker-alt"></i> <?= e($job['location']) ?></span>
                <span><i class="fas fa-clock"></i> <?= e($job['job_type']) ?></span>
              </div>
              <div class="job-card-footer">
                <span class="salary"><?= e(formatSalary($job['salary_min'], $job['salary_max'])) ?></span>
                <div class="flex gap-1">
                  <a href="../job-details.php?id=<?= (int) $job['id'] ?>" class="btn btn-primary btn-sm">View</a>
                    <form method="POST">
                      <?= csrfField() ?>
                      <input type="hidden" name="job_id" value="<?= (int) $job['id'] ?>">
                      <button type="submit" class="btn btn-outline btn-sm"
                        data-confirm-title="Remove saved job"
                        data-confirm="Remove &quot;<?= e($job['title']) ?>&quot; from your saved jobs?"
                        data-confirm-ok="Remove"
                        data-confirm-danger>
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
