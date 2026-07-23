<?php
/**
 * Employer — View & update applicants for a job
 */
$pageDepth = 1;
require_once __DIR__ . '/../includes/init.php';
requireRole('employer', '../login.php');

$pdo = getDB();
$employer = getEmployerByUserId(currentUserId());
$eid = (int) $employer['id'];
$jobId = (int) ($_GET['job_id'] ?? $_POST['job_id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM jobs WHERE id = ? AND employer_id = ?');
$stmt->execute([$jobId, $eid]);
$job = $stmt->fetch();

if (!$job) {
    setFlash('error', 'Job not found.');
    redirect('manage-jobs.php');
}

// Update applicant status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $appId = (int) ($_POST['application_id'] ?? 0);
    $status = clean($_POST['status'] ?? '');
    $allowed = ['pending', 'shortlisted', 'rejected', 'selected'];

    if (in_array($status, $allowed, true)) {
        $upd = $pdo->prepare(
            "UPDATE applications a
             JOIN jobs j ON j.id = a.job_id
             SET a.status = ?
             WHERE a.id = ? AND j.employer_id = ?"
        );
        $upd->execute([$status, $appId, $eid]);
        setFlash('success', 'Applicant status updated to ' . ucfirst($status) . '.');
    }
    redirect('applicants.php?job_id=' . $jobId);
}

$apps = $pdo->prepare(
    "SELECT a.*, c.full_name, c.phone, c.skills, c.resume, c.photo, c.education, c.experience, u.email
     FROM applications a
     JOIN candidates c ON c.id = a.candidate_id
     JOIN users u ON u.id = c.user_id
     WHERE a.job_id = ?
     ORDER BY a.applied_at DESC"
);
$apps->execute([$jobId]);
$applicants = $apps->fetchAll();

$pageTitle = 'Applicants';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <div class="breadcrumb"><a href="manage-jobs.php">Manage Jobs</a> / <span>Applicants</span></div>
    <h1>Applicants — <?= e($job['title']) ?></h1>
    <p><?= count($applicants) ?> application<?= count($applicants) === 1 ? '' : 's' ?> received</p>
  </div>
</div>

<div class="container">
  <div class="dashboard-layout">
    <?php require __DIR__ . '/_sidebar.php'; ?>

    <div class="content-panel">
      <?php if (empty($applicants)): ?>
        <div class="empty-state">
          <i class="fas fa-user-friends"></i>
          <h3>No applicants yet</h3>
          <p>Share your job listing to attract candidates.</p>
          <a href="manage-jobs.php" class="btn btn-outline mt-2">Back to Jobs</a>
        </div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Candidate</th>
                <th>Contact</th>
                <th>Skills</th>
                <th>Applied</th>
                <th>Resume</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($applicants as $app): ?>
                <tr>
                  <td>
                    <div class="flex gap-1 align-center">
                      <div class="avatar avatar-sm">
                        <?php if ($app['photo']): ?>
                          <img src="<?= e(url('uploads/photos/' . $app['photo'])) ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:8px;">
                        <?php else: ?>
                          <?= e(strtoupper(substr($app['full_name'], 0, 1))) ?>
                        <?php endif; ?>
                      </div>
                      <div>
                        <strong><?= e($app['full_name']) ?></strong>
                        <?php if ($app['cover_letter']): ?>
                          <div class="text-muted" style="font-size:0.8rem;" title="<?= e($app['cover_letter']) ?>"><?= e(excerpt($app['cover_letter'], 60)) ?></div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div><?= e($app['email']) ?></div>
                    <div class="text-muted" style="font-size:0.85rem;"><?= e($app['phone'] ?? '—') ?></div>
                  </td>
                  <td style="max-width:180px;font-size:0.85rem;"><?= e(excerpt($app['skills'] ?? '—', 80)) ?></td>
                  <td><?= e(formatDate($app['applied_at'])) ?></td>
                  <td>
                    <?php if ($app['resume']): ?>
                      <a href="<?= e(url('uploads/resumes/' . $app['resume'])) ?>" target="_blank" class="btn btn-outline btn-sm">
                        <i class="fas fa-file-pdf"></i> View
                      </a>
                    <?php else: ?>
                      <span class="text-muted">No resume</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <form method="POST" class="flex gap-1 align-center">
                      <?= csrfField() ?>
                      <input type="hidden" name="job_id" value="<?= $jobId ?>">
                      <input type="hidden" name="application_id" value="<?= (int) $app['id'] ?>">
                      <select name="status" class="status-select" onchange="this.form.submit()">
                        <?php foreach (['pending', 'shortlisted', 'rejected', 'selected'] as $st): ?>
                          <option value="<?= $st ?>" <?= $app['status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </form>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
