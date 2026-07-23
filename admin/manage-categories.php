<?php
/**
 * Admin — Manage job categories
 */
$pageDepth = 1;
require_once __DIR__ . '/../includes/init.php';
requireRole('admin', '../login.php');

$pdo = getDB();
$errors = [];
$editId = (int) ($_GET['edit'] ?? 0);
$editCat = null;

if ($editId > 0) {
    $s = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
    $s->execute([$editId]);
    $editCat = $s->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = clean($_POST['name'] ?? '');
        $description = clean($_POST['description'] ?? '');
        $icon = clean($_POST['icon'] ?? 'fa-briefcase');

        if ($name === '') {
            $errors[] = 'Category name is required.';
        } else {
            try {
                if ($id > 0) {
                    $pdo->prepare('UPDATE categories SET name=?, description=?, icon=? WHERE id=?')
                        ->execute([$name, $description ?: null, $icon ?: 'fa-briefcase', $id]);
                    setFlash('success', 'Category updated.');
                } else {
                    $pdo->prepare('INSERT INTO categories (name, description, icon) VALUES (?, ?, ?)')
                        ->execute([$name, $description ?: null, $icon ?: 'fa-briefcase']);
                    setFlash('success', 'Category added.');
                }
                redirect('manage-categories.php');
            } catch (PDOException $ex) {
                $errors[] = 'Category name already exists.';
            }
        }
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $used = countRows('jobs', 'category_id = ?', [$id]);
        if ($used > 0) {
            setFlash('error', "Cannot delete: {$used} job(s) use this category. Reassign them first.");
        } else {
            $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
            setFlash('success', 'Category deleted.');
        }
        redirect('manage-categories.php');
    }
}

$categories = $pdo->query(
    "SELECT c.*, COUNT(j.id) AS job_count
     FROM categories c
     LEFT JOIN jobs j ON j.category_id = c.id
     GROUP BY c.id
     ORDER BY c.name ASC"
)->fetchAll();

$pageTitle = 'Manage Categories';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <h1>Job Categories</h1>
    <p>Add, edit, or remove categories used when posting jobs.</p>
  </div>
</div>

<div class="container">
  <div class="dashboard-layout">
    <?php require __DIR__ . '/_sidebar.php'; ?>

    <div>
      <div class="content-panel mb-2">
        <h2><?= $editCat ? 'Edit Category' : 'Add Category' ?></h2>
        <?php foreach ($errors as $err): ?>
          <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= e($err) ?></div>
        <?php endforeach; ?>

        <form method="POST" data-validate>
          <?= csrfField() ?>
          <input type="hidden" name="action" value="save">
          <input type="hidden" name="id" value="<?= $editCat ? (int) $editCat['id'] : 0 ?>">

          <div class="form-row">
            <div class="form-group">
              <label for="name">Name <span class="required">*</span></label>
              <input type="text" id="name" name="name" class="form-control" required
                     value="<?= e($editCat['name'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label for="icon">Icon (Font Awesome class)</label>
              <input type="text" id="icon" name="icon" class="form-control"
                     value="<?= e($editCat['icon'] ?? 'fa-briefcase') ?>" placeholder="fa-laptop-code">
              <div class="form-hint">Example: fa-briefcase, fa-laptop-code, fa-chart-line</div>
            </div>
          </div>

          <div class="form-group">
            <label for="description">Description</label>
            <input type="text" id="description" name="description" class="form-control"
                   value="<?= e($editCat['description'] ?? '') ?>">
          </div>

          <div class="flex gap-1">
            <button type="submit" class="btn btn-primary"><?= $editCat ? 'Update' : 'Add' ?> Category</button>
            <?php if ($editCat): ?>
              <a href="manage-categories.php" class="btn btn-outline">Cancel</a>
            <?php endif; ?>
          </div>
        </form>
      </div>

      <div class="content-panel">
        <h2>All Categories (<?= count($categories) ?>)</h2>
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Icon</th>
                <th>Name</th>
                <th>Description</th>
                <th>Jobs</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($categories as $cat): ?>
                <tr>
                  <td><i class="fas <?= e($cat['icon']) ?>" style="color:var(--primary-700);"></i></td>
                  <td><?= e($cat['name']) ?></td>
                  <td><?= e($cat['description'] ?? '—') ?></td>
                  <td><?= (int) $cat['job_count'] ?></td>
                  <td class="actions">
                    <a href="?edit=<?= (int) $cat['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
                    <form method="POST" style="display:inline;">
                      <?= csrfField() ?>
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= (int) $cat['id'] ?>">
                      <button type="submit" class="btn btn-danger btn-sm"
                        data-confirm-title="Delete category"
                        data-confirm="Delete category &quot;<?= e($cat['name']) ?>&quot;? This only works if no jobs use it."
                        data-confirm-ok="Delete"
                        data-confirm-danger>Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
