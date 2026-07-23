<?php
/**
 * Admin — Manage candidates & employers (view / block / delete)
 */
$pageDepth = 1;
require_once __DIR__ . '/../includes/init.php';
requireRole('admin', '../login.php');

$pdo = getDB();
$tab = ($_GET['tab'] ?? 'candidates') === 'employers' ? 'employers' : 'candidates';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $userId = (int) ($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    // Never allow modifying the current admin or other admins via this form
    $u = $pdo->prepare("SELECT id, role FROM users WHERE id = ? AND role != 'admin'");
    $u->execute([$userId]);
    $target = $u->fetch();

    if ($target) {
        if ($action === 'block') {
            $pdo->prepare("UPDATE users SET status = 'blocked' WHERE id = ?")->execute([$userId]);
            setFlash('success', 'User blocked successfully.');
        } elseif ($action === 'unblock') {
            $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?")->execute([$userId]);
            setFlash('success', 'User unblocked successfully.');
        } elseif ($action === 'delete') {
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);
            setFlash('success', 'User deleted successfully.');
        }
    }
    redirect('manage-users.php?tab=' . $tab);
}

$candidates = $pdo->query(
    "SELECT u.id AS user_id, u.email, u.status, u.created_at, c.full_name, c.phone, c.location,
      (SELECT COUNT(*) FROM applications a WHERE a.candidate_id = c.id) AS app_count
     FROM users u
     JOIN candidates c ON c.user_id = u.id
     ORDER BY u.created_at DESC"
)->fetchAll();

$employers = $pdo->query(
    "SELECT u.id AS user_id, u.email, u.status, u.created_at, e.company_name, e.industry, e.phone,
      (SELECT COUNT(*) FROM jobs j WHERE j.employer_id = e.id) AS job_count
     FROM users u
     JOIN employers e ON e.user_id = u.id
     ORDER BY u.created_at DESC"
)->fetchAll();

$pageTitle = 'Manage Users';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <h1>Manage Users</h1>
    <p>View, block, or remove candidates and employers.</p>
  </div>
</div>

<div class="container">
  <div class="dashboard-layout">
    <?php require __DIR__ . '/_sidebar.php'; ?>

    <div class="content-panel">
      <div class="panel-header">
        <h2>Users</h2>
        <div class="flex gap-1">
          <a href="?tab=candidates" class="btn btn-sm <?= $tab === 'candidates' ? 'btn-primary' : 'btn-outline' ?>">
            Candidates (<?= count($candidates) ?>)
          </a>
          <a href="?tab=employers" class="btn btn-sm <?= $tab === 'employers' ? 'btn-primary' : 'btn-outline' ?>">
            Employers (<?= count($employers) ?>)
          </a>
        </div>
      </div>

      <?php if ($tab === 'candidates'): ?>
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Location</th>
                <th>Apps</th>
                <th>Status</th>
                <th>Joined</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($candidates as $c): ?>
                <tr>
                  <td><?= e($c['full_name']) ?></td>
                  <td><?= e($c['email']) ?></td>
                  <td><?= e($c['phone'] ?? '—') ?></td>
                  <td><?= e($c['location'] ?? '—') ?></td>
                  <td><?= (int) $c['app_count'] ?></td>
                  <td><?= statusBadge($c['status']) ?></td>
                  <td><?= e(formatDate($c['created_at'])) ?></td>
                  <td class="actions">
                    <?php if ($c['status'] === 'active'): ?>
                      <form method="POST" style="display:inline;">
                        <?= csrfField() ?>
                        <input type="hidden" name="user_id" value="<?= (int) $c['user_id'] ?>">
                        <input type="hidden" name="action" value="block">
                        <button type="submit" class="btn btn-sm btn-outline"
                          data-confirm-title="Block candidate"
                          data-confirm="Block <?= e($c['full_name']) ?> (<?= e($c['email']) ?>)? They will not be able to log in."
                          data-confirm-ok="Block"
                          data-confirm-danger>Block</button>
                      </form>
                    <?php else: ?>
                      <form method="POST" style="display:inline;">
                        <?= csrfField() ?>
                        <input type="hidden" name="user_id" value="<?= (int) $c['user_id'] ?>">
                        <input type="hidden" name="action" value="unblock">
                        <button type="submit" class="btn btn-sm btn-ghost">Unblock</button>
                      </form>
                    <?php endif; ?>
                    <form method="POST" style="display:inline;">
                      <?= csrfField() ?>
                      <input type="hidden" name="user_id" value="<?= (int) $c['user_id'] ?>">
                      <input type="hidden" name="action" value="delete">
                      <button type="submit" class="btn btn-sm btn-danger"
                        data-confirm-title="Delete candidate"
                        data-confirm="Permanently delete <?= e($c['full_name']) ?> and all their applications? This cannot be undone."
                        data-confirm-ok="Delete"
                        data-confirm-danger>Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Company</th>
                <th>Email</th>
                <th>Industry</th>
                <th>Jobs</th>
                <th>Status</th>
                <th>Joined</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($employers as $e): ?>
                <tr>
                  <td><?= e($e['company_name']) ?></td>
                  <td><?= e($e['email']) ?></td>
                  <td><?= e($e['industry'] ?? '—') ?></td>
                  <td><?= (int) $e['job_count'] ?></td>
                  <td><?= statusBadge($e['status']) ?></td>
                  <td><?= e(formatDate($e['created_at'])) ?></td>
                  <td class="actions">
                    <?php if ($e['status'] === 'active'): ?>
                      <form method="POST" style="display:inline;">
                        <?= csrfField() ?>
                        <input type="hidden" name="user_id" value="<?= (int) $e['user_id'] ?>">
                        <input type="hidden" name="action" value="block">
                        <button type="submit" class="btn btn-sm btn-outline"
                          data-confirm-title="Block employer"
                          data-confirm="Block <?= e($e['company_name']) ?> (<?= e($e['email']) ?>)? They will not be able to log in."
                          data-confirm-ok="Block"
                          data-confirm-danger>Block</button>
                      </form>
                    <?php else: ?>
                      <form method="POST" style="display:inline;">
                        <?= csrfField() ?>
                        <input type="hidden" name="user_id" value="<?= (int) $e['user_id'] ?>">
                        <input type="hidden" name="action" value="unblock">
                        <button type="submit" class="btn btn-sm btn-ghost">Unblock</button>
                      </form>
                    <?php endif; ?>
                    <form method="POST" style="display:inline;">
                      <?= csrfField() ?>
                      <input type="hidden" name="user_id" value="<?= (int) $e['user_id'] ?>">
                      <input type="hidden" name="action" value="delete">
                      <button type="submit" class="btn btn-sm btn-danger"
                        data-confirm-title="Delete employer"
                        data-confirm="Permanently delete <?= e($e['company_name']) ?> and all their job postings? This cannot be undone."
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
