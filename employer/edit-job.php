<?php
/**
 * Employer — Edit job posting
 */
$pageDepth = 1;
require_once __DIR__ . '/../includes/init.php';
requireRole('employer', '../login.php');

$pdo = getDB();
$employer = getEmployerByUserId(currentUserId());
$eid = (int) $employer['id'];
$jobId = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM jobs WHERE id = ? AND employer_id = ?');
$stmt->execute([$jobId, $eid]);
$job = $stmt->fetch();

if (!$job) {
    setFlash('error', 'Job not found.');
    redirect('manage-jobs.php');
}

$categories = getCategories();
$jobTypes = ['Full-time', 'Part-time', 'Contract', 'Internship', 'Remote'];
$errors = [];
$data = $job;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $errors[] = 'Invalid security token.';
    } else {
        $data['title'] = clean($_POST['title'] ?? '');
        $data['category_id'] = (int) ($_POST['category_id'] ?? 0);
        $data['location'] = clean($_POST['location'] ?? '');
        $data['job_type'] = clean($_POST['job_type'] ?? '');
        $data['salary_min'] = $_POST['salary_min'] ?? '';
        $data['salary_max'] = $_POST['salary_max'] ?? '';
        $data['deadline'] = $_POST['deadline'] ?? '';
        $data['vacancies'] = (int) ($_POST['vacancies'] ?? 1);
        $data['description'] = trim($_POST['description'] ?? '');
        $data['requirements'] = trim($_POST['requirements'] ?? '');

        if ($data['title'] === '') $errors[] = 'Job title is required.';
        if ($data['category_id'] <= 0) $errors[] = 'Select a category.';
        if ($data['location'] === '') $errors[] = 'Location is required.';
        if ($data['description'] === '' || $data['requirements'] === '') $errors[] = 'Description and requirements are required.';
        if ($data['deadline'] === '') $errors[] = 'Deadline is required.';
        if (!in_array($data['job_type'], $jobTypes, true)) $errors[] = 'Invalid job type.';

        if (!$errors) {
            // Editing an approved job keeps status; rejected/pending stay pending for re-review if significant
            $newStatus = $job['status'];
            if ($job['status'] === 'rejected') {
                $newStatus = 'pending_approval';
            }

            $upd = $pdo->prepare(
                'UPDATE jobs SET category_id=?, title=?, description=?, requirements=?, salary_min=?, salary_max=?,
                 location=?, job_type=?, deadline=?, vacancies=?, status=? WHERE id=? AND employer_id=?'
            );
            $upd->execute([
                $data['category_id'], $data['title'], $data['description'], $data['requirements'],
                $data['salary_min'] !== '' ? (float) $data['salary_min'] : null,
                $data['salary_max'] !== '' ? (float) $data['salary_max'] : null,
                $data['location'], $data['job_type'], $data['deadline'],
                max(1, $data['vacancies']), $newStatus, $jobId, $eid,
            ]);
            setFlash('success', 'Job updated successfully.');
            redirect('manage-jobs.php');
        }
    }
}

$pageTitle = 'Edit Job';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <h1>Edit Job</h1>
    <p><?= e($job['title']) ?></p>
  </div>
</div>

<div class="container">
  <div class="dashboard-layout">
    <?php require __DIR__ . '/_sidebar.php'; ?>

    <div class="content-panel">
      <?php foreach ($errors as $err): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= e($err) ?></div>
      <?php endforeach; ?>

      <form method="POST" data-validate>
        <?= csrfField() ?>

        <div class="form-group">
          <label for="title">Job Title <span class="required">*</span></label>
          <input type="text" id="title" name="title" class="form-control" value="<?= e($data['title']) ?>" required>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="category_id">Category <span class="required">*</span></label>
            <select id="category_id" name="category_id" class="form-control" required>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= (int) $cat['id'] ?>" <?= (int) $data['category_id'] === (int) $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="job_type">Job Type</label>
            <select id="job_type" name="job_type" class="form-control">
              <?php foreach ($jobTypes as $jt): ?>
                <option value="<?= e($jt) ?>" <?= $data['job_type'] === $jt ? 'selected' : '' ?>><?= e($jt) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="location">Location <span class="required">*</span></label>
            <input type="text" id="location" name="location" class="form-control" value="<?= e($data['location']) ?>" required>
          </div>
          <div class="form-group">
            <label for="vacancies">Vacancies</label>
            <input type="number" id="vacancies" name="vacancies" class="form-control" value="<?= (int) $data['vacancies'] ?>" min="1">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="salary_min">Salary Min (₹)</label>
            <input type="number" id="salary_min" name="salary_min" class="form-control" value="<?= e($data['salary_min'] ?? '') ?>" min="0" step="10000">
          </div>
          <div class="form-group">
            <label for="salary_max">Salary Max (₹)</label>
            <input type="number" id="salary_max" name="salary_max" class="form-control" value="<?= e($data['salary_max'] ?? '') ?>" min="0" step="10000">
          </div>
        </div>

        <div class="form-group">
          <label for="deadline">Deadline <span class="required">*</span></label>
          <input type="date" id="deadline" name="deadline" class="form-control" value="<?= e($data['deadline']) ?>" required>
        </div>

        <div class="form-group">
          <label for="description">Description <span class="required">*</span></label>
          <textarea id="description" name="description" class="form-control" required><?= e($data['description']) ?></textarea>
        </div>

        <div class="form-group">
          <label for="requirements">Requirements <span class="required">*</span></label>
          <textarea id="requirements" name="requirements" class="form-control" required><?= e($data['requirements']) ?></textarea>
        </div>

        <div class="flex gap-1">
          <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Job</button>
          <a href="manage-jobs.php" class="btn btn-outline">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
