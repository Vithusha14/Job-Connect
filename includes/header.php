<?php
/**
 * Site header / navigation
 * Expects: $pageTitle (string), $pageDepth (int, optional), $bodyClass (string, optional)
 */
if (!isset($pageTitle)) {
    $pageTitle = APP_NAME;
}
$bodyClass = $bodyClass ?? '';
$prefix = pathPrefix();
$role = currentRole();
?>
<?php
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="JobConnect - Online Job Recruitment System connecting job seekers and employers.">
  <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="<?= e($prefix) ?>assets/css/style.css">
</head>
<body class="<?= e($bodyClass) ?>">

<header class="site-header">
  <div class="container header-inner">
    <a href="<?= e($prefix) ?>index.php" class="logo" aria-label="<?= e(APP_NAME) ?> home">
      <span class="logo-mark"><i class="fas fa-briefcase"></i></span>
      <span class="logo-text"><?= e(APP_NAME) ?></span>
    </a>

    <button class="nav-toggle" type="button" aria-label="Toggle menu" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>

    <nav class="main-nav" id="mainNav">
      <ul class="nav-links">
        <li><a href="<?= e($prefix) ?>index.php">Home</a></li>
        <li><a href="<?= e($prefix) ?>jobs.php">Browse Jobs</a></li>
        <?php if ($role === 'candidate'): ?>
          <li><a href="<?= e($prefix) ?>candidate/dashboard.php">Dashboard</a></li>
          <li><a href="<?= e($prefix) ?>candidate/applications.php">Applications</a></li>
          <li><a href="<?= e($prefix) ?>candidate/bookmarks.php">Saved</a></li>
        <?php elseif ($role === 'employer'): ?>
          <li><a href="<?= e($prefix) ?>employer/dashboard.php">Dashboard</a></li>
          <li><a href="<?= e($prefix) ?>employer/post-job.php">Post Job</a></li>
          <li><a href="<?= e($prefix) ?>employer/manage-jobs.php">My Jobs</a></li>
        <?php elseif ($role === 'admin'): ?>
          <li><a href="<?= e($prefix) ?>admin/dashboard.php">Admin Panel</a></li>
        <?php endif; ?>
      </ul>

      <div class="nav-actions">
        <?php if (isLoggedIn()): ?>
          <span class="nav-user">
            <i class="fas fa-user-circle"></i>
            <?= e($_SESSION['display_name'] ?? 'User') ?>
          </span>
          <?php if ($role === 'candidate'): ?>
            <a href="<?= e($prefix) ?>candidate/profile.php" class="btn btn-outline btn-sm">Profile</a>
          <?php elseif ($role === 'employer'): ?>
            <a href="<?= e($prefix) ?>employer/profile.php" class="btn btn-outline btn-sm">Company</a>
          <?php endif; ?>
          <a href="<?= e($prefix) ?>logout.php" class="btn btn-primary btn-sm">Logout</a>
        <?php else: ?>
          <a href="<?= e($prefix) ?>login.php" class="btn btn-outline btn-sm">Login</a>
          <a href="<?= e($prefix) ?>register.php" class="btn btn-primary btn-sm">Register</a>
        <?php endif; ?>
      </div>
    </nav>
  </div>
</header>

<main class="site-main">
  <div class="container flash-container">
    <?= renderFlash() ?>
  </div>
