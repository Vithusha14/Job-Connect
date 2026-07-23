<?php
/**
 * Registration — Candidate or Employer
 */
$pageDepth = 0;
require_once __DIR__ . '/includes/init.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$role = ($_GET['role'] ?? $_POST['role'] ?? 'candidate') === 'employer' ? 'employer' : 'candidate';
$errors = [];
$data = [
    'email' => '',
    'full_name' => '',
    'phone' => '',
    'company_name' => '',
    'industry' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $role = ($_POST['role'] ?? 'candidate') === 'employer' ? 'employer' : 'candidate';
        $data['email'] = clean($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $data['full_name'] = clean($_POST['full_name'] ?? '');
        $data['phone'] = clean($_POST['phone'] ?? '');
        $data['company_name'] = clean($_POST['company_name'] ?? '');
        $data['industry'] = clean($_POST['industry'] ?? '');

        if (!isValidEmail($data['email'])) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }

        if ($role === 'candidate') {
            if ($data['full_name'] === '') {
                $errors[] = 'Full name is required.';
            }
        } else {
            if ($data['company_name'] === '') {
                $errors[] = 'Company name is required.';
            }
        }

        if (!$errors) {
            $pdo = getDB();
            $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $check->execute([$data['email']]);
            if ($check->fetch()) {
                $errors[] = 'An account with this email already exists.';
            } else {
                try {
                    $pdo->beginTransaction();
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare('INSERT INTO users (email, password, role, status) VALUES (?, ?, ?, ?)');
                    $stmt->execute([$data['email'], $hash, $role, 'active']);
                    $userId = (int) $pdo->lastInsertId();

                    if ($role === 'candidate') {
                        $stmt = $pdo->prepare('INSERT INTO candidates (user_id, full_name, phone) VALUES (?, ?, ?)');
                        $stmt->execute([$userId, $data['full_name'], $data['phone'] ?: null]);
                        $candidateId = (int) $pdo->lastInsertId();
                        $pdo->commit();

                        $_SESSION['user_id'] = $userId;
                        $_SESSION['role'] = 'candidate';
                        $_SESSION['email'] = $data['email'];
                        $_SESSION['display_name'] = $data['full_name'];
                        $_SESSION['candidate_id'] = $candidateId;
                        setFlash('success', 'Account created successfully! Complete your profile to get started.');
                        redirect('candidate/profile.php');
                    } else {
                        $stmt = $pdo->prepare('INSERT INTO employers (user_id, company_name, industry, phone) VALUES (?, ?, ?, ?)');
                        $stmt->execute([$userId, $data['company_name'], $data['industry'] ?: null, $data['phone'] ?: null]);
                        $employerId = (int) $pdo->lastInsertId();
                        $pdo->commit();

                        $_SESSION['user_id'] = $userId;
                        $_SESSION['role'] = 'employer';
                        $_SESSION['email'] = $data['email'];
                        $_SESSION['display_name'] = $data['company_name'];
                        $_SESSION['employer_id'] = $employerId;
                        setFlash('success', 'Company account created! Complete your profile and post your first job.');
                        redirect('employer/profile.php');
                    }
                } catch (Exception $ex) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $errors[] = 'Registration failed. Please try again.';
                }
            }
        }
    }
}

$pageTitle = 'Register';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-page">
  <div class="container" style="display:grid;place-items:center;">
    <div class="auth-card wide">
      <h1>Create an account</h1>
      <p class="subtitle">Join JobConnect as a job seeker or employer</p>

      <?php foreach ($errors as $err): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= e($err) ?></div>
      <?php endforeach; ?>

      <div class="role-tabs">
        <button type="button" class="role-tab <?= $role === 'candidate' ? 'active' : '' ?>" data-role="candidate">
          <i class="fas fa-user-graduate"></i> Job Seeker
        </button>
        <button type="button" class="role-tab <?= $role === 'employer' ? 'active' : '' ?>" data-role="employer">
          <i class="fas fa-building"></i> Employer
        </button>
      </div>

      <form method="POST" action="" data-validate novalidate>
        <?= csrfField() ?>
        <input type="hidden" name="role" id="roleInput" value="<?= e($role) ?>">

        <div class="form-group">
          <label for="email">Email Address <span class="required">*</span></label>
          <input type="email" id="email" name="email" class="form-control" value="<?= e($data['email']) ?>" required>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="password">Password <span class="required">*</span></label>
            <input type="password" id="password" name="password" class="form-control" required minlength="6">
            <div class="form-hint">Minimum 6 characters</div>
          </div>
          <div class="form-group">
            <label for="confirm_password">Confirm Password <span class="required">*</span></label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
          </div>
        </div>

        <div id="candidateFields" style="<?= $role === 'candidate' ? '' : 'display:none;' ?>">
          <div class="form-row">
            <div class="form-group">
              <label for="full_name">Full Name <span class="required">*</span></label>
              <input type="text" id="full_name" name="full_name" class="form-control" value="<?= e($data['full_name']) ?>" data-required <?= $role === 'candidate' ? 'required' : '' ?>>
            </div>
            <div class="form-group">
              <label for="phone_c">Phone</label>
              <input type="text" id="phone_c" name="phone" class="form-control" value="<?= e($data['phone']) ?>" placeholder="10-digit mobile">
            </div>
          </div>
        </div>

        <div id="employerFields" style="<?= $role === 'employer' ? '' : 'display:none;' ?>">
          <div class="form-row">
            <div class="form-group">
              <label for="company_name">Company Name <span class="required">*</span></label>
              <input type="text" id="company_name" name="company_name" class="form-control" value="<?= e($data['company_name']) ?>" data-required <?= $role === 'employer' ? 'required' : '' ?>>
            </div>
            <div class="form-group">
              <label for="industry">Industry</label>
              <input type="text" id="industry" name="industry" class="form-control" value="<?= e($data['industry']) ?>" placeholder="e.g. Information Technology">
            </div>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg"><i class="fas fa-user-plus"></i> Create Account</button>
      </form>

      <p class="text-center mt-2 text-muted">
        Already registered? <a href="login.php">Login here</a>
      </p>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
