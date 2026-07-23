<?php
/**
 * Employer — Manage job postings
 */
$pageDepth = 1;
require_once __DIR__ . '/../includes/init.php';
requireRole('employer', '../login.php');

$pdo = getDB();
$employer = getEmployerByUserId(currentUserId());
$eid = (int) $employer['id'];
$_SESSION['employer_id'] = $eid;

// Close or delete job
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $jobId = (int) ($_POST['job_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    $check = $pdo->prepare('SELECT id FROM jobs WHERE id = ? AND employer_id = ?');
    $check->execute([$jobId, $eid]);
    if ($check->fetch()) {
        if ($action === 'close') {
            $pdo->prepare("UPDATE jobs SET status = 'closed' WHERE id = ?")->execute([$jobId]);
            setFlash('success', 'Job posting closed.');
        } elseif ($action === 'delete') {
            $pdo->prepare('DELETE FROM jobs WHERE id = ?')->execute([$jobId]);
            setFlash('success', 'Job posting deleted.');
        } elseif ($action === 'reopen') {
            $pdo->prepare("UPDATE jobs SET status = 'pending_approval' WHERE id = ?")->execute([$jobId]);
            setFlash('success', 'Job submitted again for approval.');
        }
    }
    redirect('manage-jobs.php');
}

$stmt = $pdo->prepare(
    "SELECT j.*, cat.name AS category_name,
      (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) AS applicant_count
     FROM jobs j
     JOIN categories cat ON cat.id = j.category_id
     WHERE j.employer_id = ?
     ORDER BY j.created_at DESC"
);
$stmt->execute([$eid]);
$jobs = $stmt->fetchAll();

$pageTitle = 'Manage Jobs';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <h1>Manage Jobs</h1>
    <p>Edit, close, or review applicants for your postings.</p>
  </div>
</div>

<div class="container">
  <div class="dashboard-layout">
    <?php require __DIR__ . '/_sidebar.php'; ?>

    <div class="content-panel">
      <div class="panel-header">
        <h2>Your Job Postings (<?= count($jobs) ?>)</h2>
        <a href="post-job.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> New Job</a>
      </div>

      <?php if (empty($jobs)): ?>
        <div class="empty-state">
          <i class="fas fa-briefcase"></i>
          <h3>No jobs yet</h3>
          <a href="post-job.php" class="btn btn-primary mt-2">Post your first job</a>
        </div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Title</th>
                <th>Category</th>
                <th>Type</th>
                <th>Applicants</th>
                <th>Deadline</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($jobs as $job): ?>
                <tr>
                  <td>
                    <strong><?= e($job['title']) ?></strong>
                    <div class="text-muted" style="font-size:0.8rem;"><?= e($job['location']) ?></div>
                  </td>
                  <td><?= e($job['category_name']) ?></td>
                  <td><?= e($job['job_type']) ?></td>
                  <td>
                    <a href="applicants.php?job_id=<?= (int) $job['id'] ?>"><?= (int) $job['applicant_count'] ?></a>
                  </td>
                  <td><?= e(formatDate($job['deadline'])) ?></td>
                  <td><?= statusBadge($job['status']) ?></td>
                  <td class="actions">
                    <a href="edit-job.php?id=<?= (int) $job['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
                    <a href="applicants.php?job_id=<?= (int) $job['id'] ?>" class="btn btn-ghost btn-sm">Applicants</a>
                    <?php if ($job['status'] !== 'closed'): ?>
                      <form method="POST" style="display:inline;">
                        <?= csrfField() ?>
                        <input type="hidden" name="job_id" value="<?= (int) $job['id'] ?>">
                        <input type="hidden" name="action" value="close">
                        <button type="submit" class="btn btn-sm btn-outline"
                          data-confirm-title="Close job"
                          data-confirm="Close &quot;<?= e($job['title']) ?>&quot;? It will stop accepting new applications."
                          data-confirm-ok="Close Job">Close</button>
                      </form>
                    <?php else: ?>
                      <form method="POST" style="display:inline;">
                        <?= csrfField() ?>
                        <input type="hidden" name="job_id" value="<?= (int) $job['id'] ?>">
                        <input type="hidden" name="action" value="reopen">
                        <button type="submit" class="btn btn-sm btn-ghost">Reopen</button>
                      </form>
                    <?php endif; ?>
                    <form method="POST" style="display:inline;">
                      <?= csrfField() ?>
                      <input type="hidden" name="job_id" value="<?= (int) $job['id'] ?>">
                      <input type="hidden" name="action" value="delete">
                      <button type="submit" class="btn btn-sm btn-danger"
                        data-confirm-title="Delete job"
                        data-confirm="Permanently delete &quot;<?= e($job['title']) ?>&quot; and all applications? This cannot be undone."
                        data-confirm-ok="Delete"
                        data-confirm-danger>Delete</button>
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
