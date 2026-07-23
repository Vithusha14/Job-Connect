<?php
/**
 * Admin — Approve / reject / remove job postings
 */
$pageDepth = 1;
require_once __DIR__ . '/../includes/init.php';
requireRole('admin', '../login.php');

$pdo = getDB();
$statusFilter = clean($_GET['status'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $jobId = (int) ($_POST['job_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'approve') {
        $pdo->prepare("UPDATE jobs SET status = 'approved' WHERE id = ?")->execute([$jobId]);
        setFlash('success', 'Job approved and published.');
    } elseif ($action === 'reject') {
        $pdo->prepare("UPDATE jobs SET status = 'rejected' WHERE id = ?")->execute([$jobId]);
        setFlash('success', 'Job rejected.');
    } elseif ($action === 'close') {
        $pdo->prepare("UPDATE jobs SET status = 'closed' WHERE id = ?")->execute([$jobId]);
        setFlash('success', 'Job closed.');
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM jobs WHERE id = ?')->execute([$jobId]);
        setFlash('success', 'Job removed.');
    }
    redirect('manage-jobs.php' . ($statusFilter ? '?status=' . urlencode($statusFilter) : ''));
}

$where = '1=1';
$params = [];
if (in_array($statusFilter, ['pending_approval', 'approved', 'rejected', 'closed'], true)) {
    $where = 'j.status = ?';
    $params[] = $statusFilter;
}

$stmt = $pdo->prepare(
    "SELECT j.*, e.company_name, c.name AS category_name,
      (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) AS applicant_count
     FROM jobs j
     JOIN employers e ON e.id = j.employer_id
     JOIN categories c ON c.id = j.category_id
     WHERE {$where}
     ORDER BY FIELD(j.status, 'pending_approval', 'approved', 'rejected', 'closed'), j.created_at DESC"
);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

$pageTitle = 'Manage Jobs';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <h1>Manage Job Postings</h1>
    <p>Approve, reject, close, or remove listings.</p>
  </div>
</div>

<div class="container">
  <div class="dashboard-layout">
    <?php require __DIR__ . '/_sidebar.php'; ?>

    <div class="content-panel">
      <div class="panel-header">
        <h2>All Jobs (<?= count($jobs) ?>)</h2>
        <form method="GET">
          <select name="status" class="form-control" style="width:auto;" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <?php foreach (['pending_approval' => 'Pending Approval', 'approved' => 'Approved', 'rejected' => 'Rejected', 'closed' => 'Closed'] as $val => $label): ?>
              <option value="<?= $val ?>" <?= $statusFilter === $val ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>

      <?php if (empty($jobs)): ?>
        <div class="empty-state">
          <i class="fas fa-briefcase"></i>
          <h3>No jobs found</h3>
        </div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Title</th>
                <th>Company</th>
                <th>Category</th>
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
                    <div class="text-muted" style="font-size:0.8rem;"><?= e($job['location']) ?> · <?= e($job['job_type']) ?></div>
                  </td>
                  <td><?= e($job['company_name']) ?></td>
                  <td><?= e($job['category_name']) ?></td>
                  <td><?= (int) $job['applicant_count'] ?></td>
                  <td><?= e(formatDate($job['deadline'])) ?></td>
                  <td><?= statusBadge($job['status']) ?></td>
                  <td class="actions">
                    <a href="../job-details.php?id=<?= (int) $job['id'] ?>" class="btn btn-ghost btn-sm" target="_blank">View</a>
                    <?php if ($job['status'] === 'pending_approval' || $job['status'] === 'rejected'): ?>
                      <form method="POST" style="display:inline;">
                        <?= csrfField() ?>
                        <input type="hidden" name="job_id" value="<?= (int) $job['id'] ?>">
                        <input type="hidden" name="action" value="approve">
                        <button class="btn btn-sm btn-success">Approve</button>
                      </form>
                    <?php endif; ?>
                    <?php if ($job['status'] === 'pending_approval' || $job['status'] === 'approved'): ?>
                      <form method="POST" style="display:inline;">
                        <?= csrfField() ?>
                        <input type="hidden" name="job_id" value="<?= (int) $job['id'] ?>">
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="btn btn-sm btn-outline"
                          data-confirm-title="Reject job"
                          data-confirm="Reject &quot;<?= e($job['title']) ?>&quot; by <?= e($job['company_name']) ?>?"
                          data-confirm-ok="Reject"
                          data-confirm-danger>Reject</button>
                      </form>
                    <?php endif; ?>
                    <?php if ($job['status'] === 'approved'): ?>
                      <form method="POST" style="display:inline;">
                        <?= csrfField() ?>
                        <input type="hidden" name="job_id" value="<?= (int) $job['id'] ?>">
                        <input type="hidden" name="action" value="close">
                        <button type="submit" class="btn btn-sm btn-outline"
                          data-confirm-title="Close job"
                          data-confirm="Close the posting &quot;<?= e($job['title']) ?>&quot;? Candidates will no longer be able to apply."
                          data-confirm-ok="Close Job">Close</button>
                      </form>
                    <?php endif; ?>
                    <form method="POST" style="display:inline;">
                      <?= csrfField() ?>
                      <input type="hidden" name="job_id" value="<?= (int) $job['id'] ?>">
                      <input type="hidden" name="action" value="delete">
                      <button type="submit" class="btn btn-sm btn-danger"
                        data-confirm-title="Delete job"
                        data-confirm="Permanently delete &quot;<?= e($job['title']) ?>&quot; and all its applications? This cannot be undone."
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
