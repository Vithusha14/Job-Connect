<?php
/**
 * Candidate Profile — edit details, photo & resume upload
 */
$pageDepth = 1;
require_once __DIR__ . '/../includes/init.php';
requireRole('candidate', '../login.php');

$pdo = getDB();
$candidate = getCandidateByUserId(currentUserId());
if (!$candidate) {
    setFlash('error', 'Profile not found.');
    redirect('../logout.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $errors[] = 'Invalid security token.';
    } else {
        $fullName = clean($_POST['full_name'] ?? '');
        $phone = clean($_POST['phone'] ?? '');
        $location = clean($_POST['location'] ?? '');
        $skills = clean($_POST['skills'] ?? '');
        $education = trim($_POST['education'] ?? '');
        $experience = trim($_POST['experience'] ?? '');
        $bio = trim($_POST['bio'] ?? '');

        if ($fullName === '') {
            $errors[] = 'Full name is required.';
        }

        $photoName = $candidate['photo'];
        $resumeName = $candidate['resume'];

        // Photo upload
        if (!empty($_FILES['photo']['name'])) {
            $v = validateUpload($_FILES['photo'], ALLOWED_IMAGE_TYPES, MAX_IMAGE_SIZE, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
            if (!$v['ok']) {
                $errors[] = 'Photo: ' . $v['error'];
            } else {
                $stored = storeUpload($_FILES['photo'], PHOTO_PATH, 'photo_' . $candidate['id'], $v['ext']);
                if ($stored) {
                    deleteUpload(PHOTO_PATH, $candidate['photo']);
                    $photoName = $stored;
                } else {
                    $errors[] = 'Failed to upload photo.';
                }
            }
        }

        // Resume upload (PDF only)
        if (!empty($_FILES['resume']['name'])) {
            $v = validateUpload($_FILES['resume'], ALLOWED_RESUME_TYPES, MAX_RESUME_SIZE, ['pdf']);
            if (!$v['ok']) {
                $errors[] = 'Resume: ' . $v['error'];
            } else {
                $stored = storeUpload($_FILES['resume'], RESUME_PATH, 'resume_' . $candidate['id'], $v['ext']);
                if ($stored) {
                    deleteUpload(RESUME_PATH, $candidate['resume']);
                    $resumeName = $stored;
                } else {
                    $errors[] = 'Failed to upload resume.';
                }
            }
        }

        if (!$errors) {
            $stmt = $pdo->prepare(
                'UPDATE candidates SET full_name=?, phone=?, location=?, skills=?, education=?, experience=?, bio=?, photo=?, resume=? WHERE id=?'
            );
            $stmt->execute([
                $fullName, $phone ?: null, $location ?: null, $skills ?: null,
                $education ?: null, $experience ?: null, $bio ?: null,
                $photoName, $resumeName, $candidate['id']
            ]);
            $_SESSION['display_name'] = $fullName;
            setFlash('success', 'Profile updated successfully.');
            redirect('profile.php');
        }

        // Refresh local data for form redisplay
        $candidate = array_merge($candidate, compact('fullName') + [
            'full_name' => $fullName,
            'phone' => $phone,
            'location' => $location,
            'skills' => $skills,
            'education' => $education,
            'experience' => $experience,
            'bio' => $bio,
        ]);
    }
}

$pageTitle = 'My Profile';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <h1>My Profile</h1>
    <p>Keep your profile and resume up to date for better opportunities.</p>
  </div>
</div>

<div class="container">
  <div class="dashboard-layout">
    <?php require __DIR__ . '/_sidebar.php'; ?>

    <div class="content-panel">
      <?php foreach ($errors as $err): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= e($err) ?></div>
      <?php endforeach; ?>

      <div class="profile-header">
        <div class="avatar avatar-lg">
          <?php if ($candidate['photo']): ?>
            <img src="<?= e(url('uploads/photos/' . $candidate['photo'])) ?>" alt="Photo" style="width:100%;height:100%;object-fit:cover;border-radius:16px;">
          <?php else: ?>
            <?= e(strtoupper(substr($candidate['full_name'], 0, 1))) ?>
          <?php endif; ?>
        </div>
        <div class="profile-info">
          <h2><?= e($candidate['full_name']) ?></h2>
          <p><?= e($candidate['email']) ?></p>
          <?php if ($candidate['resume']): ?>
            <a href="<?= e(url('uploads/resumes/' . $candidate['resume'])) ?>" target="_blank" class="btn btn-outline btn-sm mt-1">
              <i class="fas fa-file-pdf"></i> View Resume
            </a>
          <?php endif; ?>
        </div>
      </div>

      <form method="POST" enctype="multipart/form-data" data-validate>
        <?= csrfField() ?>

        <div class="form-row">
          <div class="form-group">
            <label for="full_name">Full Name <span class="required">*</span></label>
            <input type="text" id="full_name" name="full_name" class="form-control" value="<?= e($candidate['full_name']) ?>" required>
          </div>
          <div class="form-group">
            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone" class="form-control" value="<?= e($candidate['phone'] ?? '') ?>">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="location">Location</label>
            <input type="text" id="location" name="location" class="form-control" value="<?= e($candidate['location'] ?? '') ?>" placeholder="City">
          </div>
          <div class="form-group">
            <label for="email_ro">Email</label>
            <input type="email" id="email_ro" class="form-control" value="<?= e($candidate['email']) ?>" disabled>
          </div>
        </div>

        <div class="form-group">
          <label for="skills">Skills</label>
          <input type="text" id="skills" name="skills" class="form-control" value="<?= e($candidate['skills'] ?? '') ?>" placeholder="e.g. PHP, MySQL, JavaScript">
          <div class="form-hint">Comma-separated list of your key skills</div>
        </div>

        <div class="form-group">
          <label for="education">Education</label>
          <textarea id="education" name="education" class="form-control"><?= e($candidate['education'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
          <label for="experience">Experience</label>
          <textarea id="experience" name="experience" class="form-control"><?= e($candidate['experience'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
          <label for="bio">Bio / Summary</label>
          <textarea id="bio" name="bio" class="form-control"><?= e($candidate['bio'] ?? '') ?></textarea>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="photo">Profile Photo</label>
            <input type="file" id="photo" name="photo" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp" data-max-mb="1">
            <div class="form-hint">JPG, PNG, GIF or WebP — max 1 MB</div>
          </div>
          <div class="form-group">
            <label for="resume">Resume (PDF)</label>
            <input type="file" id="resume" name="resume" class="form-control" accept="application/pdf" data-max-mb="2">
            <div class="form-hint">PDF only — max 2 MB</div>
          </div>
        </div>

        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Profile</button>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
