<?php
/**
 * Public job detail page — apply / bookmark for candidates
 */
$pageDepth = 0;
require_once __DIR__ . '/includes/init.php';

$pdo = getDB();
$jobId = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT j.*, c.name AS category_name, e.company_name, e.logo, e.description AS company_desc,
            e.industry, e.website, e.address
     FROM jobs j
     JOIN categories c ON c.id = j.category_id
     JOIN employers e ON e.id = j.employer_id
     WHERE j.id = ?"
);
$stmt->execute([$jobId]);
$job = $stmt->fetch();

if (!$job) {
    setFlash('error', 'Job not found or not available.');
    redirect('jobs.php');
}

$canViewUnapproved = currentRole() === 'admin'
    || (currentRole() === 'employer' && isset($_SESSION['employer_id']) && (int) $job['employer_id'] === (int) $_SESSION['employer_id']);

if ($job['status'] !== 'approved' && !$canViewUnapproved) {
    setFlash('error', 'Job not found or not available.');
    redirect('jobs.php');
}

$candidateId = $_SESSION['candidate_id'] ?? null;
$hasApplied = false;
$isBookmarked = false;

if ($candidateId) {
    $s = $pdo->prepare('SELECT id FROM applications WHERE job_id = ? AND candidate_id = ?');
    $s->execute([$jobId, $candidateId]);
    $hasApplied = (bool) $s->fetch();

    $s = $pdo->prepare('SELECT id FROM bookmarks WHERE job_id = ? AND candidate_id = ?');
    $s->execute([$jobId, $candidateId]);
    $isBookmarked = (bool) $s->fetch();
}

// Handle apply / bookmark
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    requireLogin('login.php');
    requireRole('candidate', 'login.php');

    $candidate = getCandidateByUserId(currentUserId());
    $candidateId = (int) $candidate['id'];
    $_SESSION['candidate_id'] = $candidateId;
    $action = $_POST['action'] ?? '';

    if ($action === 'apply') {
        if ($job['status'] !== 'approved' || strtotime($job['deadline']) < strtotime('today')) {
            setFlash('error', 'This job is no longer accepting applications.');
            redirect('job-details.php?id=' . $jobId);
        }
        if (empty($candidate['resume'])) {
            setFlash('warning', 'Please upload your resume before applying.');
            redirect('candidate/profile.php');
        }

        $check = $pdo->prepare('SELECT id FROM applications WHERE job_id = ? AND candidate_id = ?');
        $check->execute([$jobId, $candidateId]);
        if ($check->fetch()) {
            setFlash('info', 'You have already applied to this job.');
        } else {
            $cover = clean($_POST['cover_letter'] ?? '');
            $ins = $pdo->prepare('INSERT INTO applications (job_id, candidate_id, cover_letter, status) VALUES (?, ?, ?, ?)');
            $ins->execute([$jobId, $candidateId, $cover ?: null, 'pending']);
            setFlash('success', 'Application submitted successfully!');
        }
        redirect('candidate/applications.php');
    }

    if ($action === 'bookmark') {
        if ($isBookmarked) {
            $pdo->prepare('DELETE FROM bookmarks WHERE job_id = ? AND candidate_id = ?')->execute([$jobId, $candidateId]);
            setFlash('success', 'Job removed from saved list.');
        } else {
            $pdo->prepare('INSERT IGNORE INTO bookmarks (candidate_id, job_id) VALUES (?, ?)')->execute([$candidateId, $jobId]);
            setFlash('success', 'Job saved to your bookmarks.');
        }
        redirect('job-details.php?id=' . $jobId);
    }
}

$pageTitle = $job['title'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <div class="breadcrumb"><a href="index.php">Home</a> / <a href="jobs.php">Jobs</a> / <span><?= e($job['title']) ?></span></div>
    <h1><?= e($job['title']) ?></h1>
    <p><?= e($job['company_name']) ?> · <?= e($job['location']) ?></p>
  </div>
</div>

<div class="container">
  <div class="job-detail-layout">
    <div class="content-panel job-detail-main">
      <div class="job-meta mb-2">
        <span><i class="fas fa-map-marker-alt"></i> <?= e($job['location']) ?></span>
        <span><i class="fas fa-clock"></i> <?= e($job['job_type']) ?></span>
        <span><i class="fas fa-tag"></i> <?= e($job['category_name']) ?></span>
        <span><i class="fas fa-users"></i> <?= (int) $job['vacancies'] ?> vacanc<?= $job['vacancies'] == 1 ? 'y' : 'ies' ?></span>
        <?= statusBadge($job['status'] === 'approved' ? 'active' : $job['status']) ?>
      </div>

      <div class="detail-section">
        <h3>Job Description</h3>
        <p><?= nl2br(e($job['description'])) ?></p>
      </div>

      <div class="detail-section">
        <h3>Requirements</h3>
        <pre><?= e($job['requirements']) ?></pre>
      </div>

      <?php if (currentRole() === 'candidate' && !$hasApplied && $job['status'] === 'approved'): ?>
        <div class="detail-section">
          <h3>Apply for this position</h3>
          <form method="POST" data-validate>
            <?= csrfField() ?>
            <input type="hidden" name="action" value="apply">
            <div class="form-group">
              <label for="cover_letter">Cover Letter (optional)</label>
              <textarea id="cover_letter" name="cover_letter" class="form-control" placeholder="Briefly introduce yourself and why you're a great fit…"></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-paper-plane"></i> Apply Now</button>
          </form>
        </div>
      <?php elseif ($hasApplied): ?>
        <div class="alert alert-success mt-3"><i class="fas fa-check-circle"></i> You have already applied to this job. <a href="candidate/applications.php">Track status</a></div>
      <?php elseif (!isLoggedIn()): ?>
        <div class="alert alert-info mt-3"><i class="fas fa-info-circle"></i> <a href="login.php">Login</a> as a candidate to apply for this job.</div>
      <?php endif; ?>
    </div>

    <aside>
      <div class="sidebar-widget">
        <h3>Job Overview</h3>
        <div class="widget-row"><span>Salary</span><strong><?= e(formatSalary($job['salary_min'], $job['salary_max'])) ?></strong></div>
        <div class="widget-row"><span>Job Type</span><strong><?= e($job['job_type']) ?></strong></div>
        <div class="widget-row"><span>Location</span><strong><?= e($job['location']) ?></strong></div>
        <div class="widget-row"><span>Deadline</span><strong><?= e(formatDate($job['deadline'])) ?></strong></div>
        <div class="widget-row"><span>Posted</span><strong><?= e(formatDate($job['created_at'])) ?></strong></div>

        <div class="mt-2" style="display:flex;flex-direction:column;gap:0.5rem;">
          <?php if (currentRole() === 'candidate'): ?>
            <form method="POST">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="bookmark">
              <button type="submit" class="btn <?= $isBookmarked ? 'btn-primary' : 'btn-outline' ?> btn-block">
                <i class="fas fa-bookmark"></i> <?= $isBookmarked ? 'Saved' : 'Save Job' ?>
              </button>
            </form>
          <?php endif; ?>
          <a href="jobs.php" class="btn btn-ghost btn-block">Back to Jobs</a>
        </div>
      </div>

      <div class="sidebar-widget">
        <h3>About Company</h3>
        <div class="company-block">
          <div class="company-logo">
            <?php if ($job['logo']): ?>
              <img src="<?= e(url('uploads/logos/' . $job['logo'])) ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:10px;">
            <?php else: ?>
              <?= e(strtoupper(substr($job['company_name'], 0, 1))) ?>
            <?php endif; ?>
          </div>
          <div>
            <strong><?= e($job['company_name']) ?></strong>
            <?php if ($job['industry']): ?><div class="text-muted" style="font-size:0.85rem;"><?= e($job['industry']) ?></div><?php endif; ?>
          </div>
        </div>
        <?php if ($job['company_desc']): ?>
          <p class="text-muted" style="font-size:0.9rem;"><?= e(excerpt($job['company_desc'], 180)) ?></p>
        <?php endif; ?>
        <?php if ($job['website']): ?>
          <a href="<?= e($job['website']) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm mt-1"><i class="fas fa-external-link-alt"></i> Website</a>
        <?php endif; ?>
      </div>
    </aside>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
