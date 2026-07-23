<?php
/**
 * Employer Company Profile
 */
$pageDepth = 1;
require_once __DIR__ . '/../includes/init.php';
requireRole('employer', '../login.php');

$pdo = getDB();
$employer = getEmployerByUserId(currentUserId());
$_SESSION['employer_id'] = (int) $employer['id'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $errors[] = 'Invalid security token.';
    } else {
        $company = clean($_POST['company_name'] ?? '');
        $industry = clean($_POST['industry'] ?? '');
        $website = clean($_POST['website'] ?? '');
        $phone = clean($_POST['phone'] ?? '');
        $address = clean($_POST['address'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($company === '') {
            $errors[] = 'Company name is required.';
        }
        if ($website !== '' && !filter_var($website, FILTER_VALIDATE_URL)) {
            $errors[] = 'Please enter a valid website URL (include https://).';
        }

        $logoName = $employer['logo'];
        if (!empty($_FILES['logo']['name'])) {
            $v = validateUpload($_FILES['logo'], ALLOWED_IMAGE_TYPES, MAX_IMAGE_SIZE, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
            if (!$v['ok']) {
                $errors[] = 'Logo: ' . $v['error'];
            } else {
                $stored = storeUpload($_FILES['logo'], LOGO_PATH, 'logo_' . $employer['id'], $v['ext']);
                if ($stored) {
                    deleteUpload(LOGO_PATH, $employer['logo']);
                    $logoName = $stored;
                } else {
                    $errors[] = 'Failed to upload logo.';
                }
            }
        }

        if (!$errors) {
            $stmt = $pdo->prepare(
                'UPDATE employers SET company_name=?, industry=?, website=?, phone=?, address=?, description=?, logo=? WHERE id=?'
            );
            $stmt->execute([
                $company, $industry ?: null, $website ?: null, $phone ?: null,
                $address ?: null, $description ?: null, $logoName, $employer['id']
            ]);
            $_SESSION['display_name'] = $company;
            setFlash('success', 'Company profile updated.');
            redirect('profile.php');
        }

        $employer = array_merge($employer, [
            'company_name' => $company,
            'industry' => $industry,
            'website' => $website,
            'phone' => $phone,
            'address' => $address,
            'description' => $description,
        ]);
    }
}

$pageTitle = 'Company Profile';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div class="container">
    <h1>Company Profile</h1>
    <p>Update your company details and logo.</p>
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
          <?php if ($employer['logo']): ?>
            <img src="<?= e(url('uploads/logos/' . $employer['logo'])) ?>" alt="Logo" style="width:100%;height:100%;object-fit:cover;border-radius:16px;">
          <?php else: ?>
            <?= e(strtoupper(substr($employer['company_name'], 0, 1))) ?>
          <?php endif; ?>
        </div>
        <div class="profile-info">
          <h2><?= e($employer['company_name']) ?></h2>
          <p><?= e($employer['email']) ?></p>
        </div>
      </div>

      <form method="POST" enctype="multipart/form-data" data-validate>
        <?= csrfField() ?>

        <div class="form-row">
          <div class="form-group">
            <label for="company_name">Company Name <span class="required">*</span></label>
            <input type="text" id="company_name" name="company_name" class="form-control" value="<?= e($employer['company_name']) ?>" required>
          </div>
          <div class="form-group">
            <label for="industry">Industry</label>
            <input type="text" id="industry" name="industry" class="form-control" value="<?= e($employer['industry'] ?? '') ?>">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="website">Website</label>
            <input type="url" id="website" name="website" class="form-control" value="<?= e($employer['website'] ?? '') ?>" placeholder="https://">
          </div>
          <div class="form-group">
            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone" class="form-control" value="<?= e($employer['phone'] ?? '') ?>">
          </div>
        </div>

        <div class="form-group">
          <label for="address">Address</label>
          <input type="text" id="address" name="address" class="form-control" value="<?= e($employer['address'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label for="description">Company Description</label>
          <textarea id="description" name="description" class="form-control"><?= e($employer['description'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
          <label for="logo">Company Logo</label>
          <input type="file" id="logo" name="logo" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp" data-max-mb="1">
          <div class="form-hint">JPG, PNG, GIF or WebP — max 1 MB</div>
        </div>

        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Profile</button>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
