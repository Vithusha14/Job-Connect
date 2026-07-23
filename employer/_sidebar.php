<?php
/**
 * Employer sidebar navigation
 */
$current = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
  <a href="dashboard.php" class="sidebar-link <?= $current === 'dashboard.php' ? 'active' : '' ?>">
    <i class="fas fa-tachometer-alt"></i> Dashboard
  </a>
  <a href="profile.php" class="sidebar-link <?= $current === 'profile.php' ? 'active' : '' ?>">
    <i class="fas fa-building"></i> Company Profile
  </a>
  <a href="post-job.php" class="sidebar-link <?= $current === 'post-job.php' ? 'active' : '' ?>">
    <i class="fas fa-plus-circle"></i> Post a Job
  </a>
  <a href="manage-jobs.php" class="sidebar-link <?= in_array($current, ['manage-jobs.php', 'edit-job.php', 'applicants.php'], true) ? 'active' : '' ?>">
    <i class="fas fa-briefcase"></i> Manage Jobs
  </a>
  <a href="../logout.php" class="sidebar-link">
    <i class="fas fa-sign-out-alt"></i> Logout
  </a>
</aside>
