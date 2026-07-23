<?php
/**
 * User login (Candidate / Employer / Admin)
 */
$pageDepth = 0;
require_once __DIR__ . '/includes/init.php';

if (isLoggedIn()) {
    $r = currentRole();
    if ($r === 'admin') redirect('admin/dashboard.php');
    if ($r === 'employer') redirect('employer/dashboard.php');
    redirect('candidate/dashboard.php');
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $email = clean($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || !isValidEmail($email)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if ($password === '') {
            $errors[] = 'Please enter your password.';
        }

        if (!$errors) {
            $pdo = getDB();
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password'])) {
                $errors[] = 'Invalid email or password.';
            } elseif ($user['status'] !== 'active') {
                $errors[] = 'Your account has been blocked. Contact the administrator.';
            } else {
                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];

                // Display name
                if ($user['role'] === 'candidate') {
                    $c = getCandidateByUserId((int) $user['id']);
                    $_SESSION['display_name'] = $c['full_name'] ?? 'Candidate';
                    $_SESSION['candidate_id'] = (int) ($c['id'] ?? 0);
                    setFlash('success', 'Welcome back, ' . ($_SESSION['display_name']) . '!');
                    redirect('candidate/dashboard.php');
                } elseif ($user['role'] === 'employer') {
                    $e = getEmployerByUserId((int) $user['id']);
                    $_SESSION['display_name'] = $e['company_name'] ?? 'Employer';
                    $_SESSION['employer_id'] = (int) ($e['id'] ?? 0);
                    setFlash('success', 'Welcome back, ' . ($_SESSION['display_name']) . '!');
                    redirect('employer/dashboard.php');
                } else {
                    $_SESSION['display_name'] = 'Administrator';
                    setFlash('success', 'Welcome, Admin!');
                    redirect('admin/dashboard.php');
                }
            }
        }
    }
}

$pageTitle = 'Login';
$bodyClass = 'auth-body';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-page">
  <div class="container" style="display:grid;place-items:center;">
    <div class="auth-card">
      <h1>Welcome back</h1>
      <p class="subtitle">Sign in to your JobConnect account</p>

      <?php foreach ($errors as $err): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= e($err) ?></div>
      <?php endforeach; ?>

      <form method="POST" action="" data-validate novalidate>
        <?= csrfField() ?>

        <div class="form-group">
          <label for="email">Email Address <span class="required">*</span></label>
          <input type="email" id="email" name="email" class="form-control" value="<?= e($email) ?>" required placeholder="you@example.com" autocomplete="email">
        </div>

        <div class="form-group">
          <label for="password">Password <span class="required">*</span></label>
          <input type="password" id="password" name="password" class="form-control" required placeholder="Enter your password" autocomplete="current-password">
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg"><i class="fas fa-sign-in-alt"></i> Login</button>
      </form>

      <p class="text-center mt-2 text-muted">
        Don't have an account? <a href="register.php">Register here</a>
      </p>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
