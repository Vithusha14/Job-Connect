<?php
/**
 * Employer — Post a new job
 */
$pageDepth = 1;
require_once __DIR__ . '/../includes/init.php';
requireRole('employer', '../login.php');

$pdo = getDB();
$employer = getEmployerByUserId(currentUserId());
$_SESSION['employer_id'] = (int) $employer['id'];
$categories = getCategories();
$jobTypes = ['Full-time', 'Part-time', 'Contract', 'Internship', 'Remote'];

$errors = [];
$data = [
    'title' => '', 'category_id' => '', 'location' => '', 'job_type' => 'Full-time',
    'salary_min' => '', 'salary_max' => '', 'deadline' => '', 'vacancies' => '1',
    'description' => '', 'requirements' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $errors[] = 'Invalid security token.';
    } else {
        foreach ($data as $key => $_) {
            $data[$key] = is_string($_POST[$key] ?? '') ? trim($_POST[$key]) : ($_POST[$key] ?? '');
        }
        $data['title'] = clean($data['title']);
        $data['location'] = clean($data['location']);

        if ($data['title'] === '') $errors[] = 'Job title is required.';
        if ((int) $data['category_id'] <= 0) $errors[] = 'Please select a category.';
        if ($data['location'] === '') $errors[] = 'Location is required.';
        if ($data['description'] === '') $errors[] = 'Description is required.';
        if ($data['requirements'] === '') $errors[] = 'Requirements are required.';
        if ($data['deadline'] === '' || strtotime($data['deadline']) < strtotime('today')) {
            $errors[] = 'Please choose a valid future deadline.';
        }
        if (!in_array($data['job_type'], $jobTypes, true)) {
            $errors[] = 'Invalid job type.';
        }
        if ($data['salary_min'] !== '' && $data['salary_max'] !== '' && (float) $data['salary_min'] > (float) $data['salary_max']) {
            $errors[] = 'Minimum salary cannot exceed maximum salary.';
        }

        if (!$errors) {
            $stmt = $pdo->prepare(
                'INSERT INTO jobs (employer_id, category_id, title, description, requirements, salary_min, salary_max, location, job_type, deadline, status, vacancies)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $employer['id'],
                (int) $data['category_id'],
                $data['title'],
                $data['description'],
                $data['requirements'],
                $data['salary_min'] !== '' ? (float) $data['salary_min'] : null,
                $data['salary_max'] !== '' ? (float) $data['salary_max'] : null,
                $data['location'],
                $data['job_type'],
                $data['deadline'],
                'pending_approval',
                max(1, (int) $data['vacancies']),
            ]);
            setFlash('success', 'Job posted successfully! It will be visible after admin approval.');
            redirect('manage-jobs.php');
        }
    }
}

$pageTitle = 'Post a Job';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <h1>Post a New Job</h1>
    <p>Fill in the details below. New postings require admin approval.</p>
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
          <input type="text" id="title" name="title" class="form-control" value="<?= e($data['title']) ?>" required placeholder="e.g. Senior PHP Developer">
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="category_id">Category <span class="required">*</span></label>
            <select id="category_id" name="category_id" class="form-control" required>
              <option value="">Select category</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= (int) $cat['id'] ?>" <?= (int) $data['category_id'] === (int) $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="job_type">Job Type <span class="required">*</span></label>
            <select id="job_type" name="job_type" class="form-control" required>
              <?php foreach ($jobTypes as $jt): ?>
                <option value="<?= e($jt) ?>" <?= $data['job_type'] === $jt ? 'selected' : '' ?>><?= e($jt) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="location">Location <span class="required">*</span></label>
            <input type="text" id="location" name="location" class="form-control" value="<?= e($data['location']) ?>" required placeholder="City or Remote">
          </div>
          <div class="form-group">
            <label for="vacancies">Vacancies</label>
            <input type="number" id="vacancies" name="vacancies" class="form-control" value="<?= e($data['vacancies']) ?>" min="1" max="100">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="salary_min">Salary Min (₹ / year)</label>
            <input type="number" id="salary_min" name="salary_min" class="form-control" value="<?= e($data['salary_min']) ?>" min="0" step="10000">
          </div>
          <div class="form-group">
            <label for="salary_max">Salary Max (₹ / year)</label>
            <input type="number" id="salary_max" name="salary_max" class="form-control" value="<?= e($data['salary_max']) ?>" min="0" step="10000">
          </div>
        </div>

        <div class="form-group">
          <label for="deadline">Application Deadline <span class="required">*</span></label>
          <input type="date" id="deadline" name="deadline" class="form-control" value="<?= e($data['deadline']) ?>" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
        </div>

        <div class="form-group">
          <label for="description">Job Description <span class="required">*</span></label>
          <textarea id="description" name="description" class="form-control" required><?= e($data['description']) ?></textarea>
        </div>

        <div class="form-group">
          <label for="requirements">Requirements <span class="required">*</span></label>
          <textarea id="requirements" name="requirements" class="form-control" required><?= e($data['requirements']) ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-paper-plane"></i> Submit for Approval</button>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
