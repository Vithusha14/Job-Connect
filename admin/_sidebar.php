<?php
/**
 * Admin sidebar navigation
 */
$current = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
  <a href="dashboard.php" class="sidebar-link <?= $current === 'dashboard.php' ? 'active' : '' ?>">
    <i class="fas fa-chart-pie"></i> Dashboard
  </a>
  <a href="manage-users.php" class="sidebar-link <?= $current === 'manage-users.php' ? 'active' : '' ?>">
    <i class="fas fa-users"></i> Manage Users
  </a>
  <a href="manage-jobs.php" class="sidebar-link <?= $current === 'manage-jobs.php' ? 'active' : '' ?>">
    <i class="fas fa-briefcase"></i> Manage Jobs
  </a>
  <a href="manage-categories.php" class="sidebar-link <?= $current === 'manage-categories.php' ? 'active' : '' ?>">
    <i class="fas fa-tags"></i> Categories
  </a>
  <a href="../logout.php" class="sidebar-link">
    <i class="fas fa-sign-out-alt"></i> Logout
  </a>
</aside>
