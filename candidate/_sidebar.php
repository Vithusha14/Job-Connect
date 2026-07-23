<?php
/**
 * Candidate sidebar navigation
 */
$current = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
  <a href="dashboard.php" class="sidebar-link <?= $current === 'dashboard.php' ? 'active' : '' ?>">
    <i class="fas fa-tachometer-alt"></i> Dashboard
  </a>
  <a href="profile.php" class="sidebar-link <?= $current === 'profile.php' ? 'active' : '' ?>">
    <i class="fas fa-user"></i> My Profile
  </a>
  <a href="../jobs.php" class="sidebar-link">
    <i class="fas fa-search"></i> Browse Jobs
  </a>
  <a href="applications.php" class="sidebar-link <?= $current === 'applications.php' ? 'active' : '' ?>">
    <i class="fas fa-file-alt"></i> Applications
  </a>
  <a href="bookmarks.php" class="sidebar-link <?= $current === 'bookmarks.php' ? 'active' : '' ?>">
    <i class="fas fa-bookmark"></i> Saved Jobs
  </a>
  <a href="../logout.php" class="sidebar-link">
    <i class="fas fa-sign-out-alt"></i> Logout
  </a>
</aside>
