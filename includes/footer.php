<?php
/**
 * Site footer
 */
$prefix = pathPrefix();
?>
</main>

<footer class="site-footer">
  <div class="container footer-grid">
    <div class="footer-brand">
      <a href="<?= e($prefix) ?>index.php" class="logo logo-light">
        <span class="logo-mark"><i class="fas fa-briefcase"></i></span>
        <span class="logo-text"><?= e(APP_NAME) ?></span>
      </a>
      <p>Connecting talented professionals with great companies. A modern online job recruitment platform for candidates, employers, and administrators.</p>
    </div>

    <div class="footer-col">
      <h4>Quick Links</h4>
      <ul>
        <li><a href="<?= e($prefix) ?>index.php">Home</a></li>
        <li><a href="<?= e($prefix) ?>jobs.php">Browse Jobs</a></li>
        <li><a href="<?= e($prefix) ?>login.php">Login</a></li>
        <li><a href="<?= e($prefix) ?>register.php">Register</a></li>
      </ul>
    </div>

    <div class="footer-col">
      <h4>For Employers</h4>
      <ul>
        <li><a href="<?= e($prefix) ?>register.php?role=employer">Post a Job</a></li>
        <li><a href="<?= e($prefix) ?>login.php">Employer Login</a></li>
        <li><a href="<?= e($prefix) ?>jobs.php">View Listings</a></li>
      </ul>
    </div>

    <div class="footer-col">
      <h4>Contact</h4>
      <ul class="footer-contact">
        <li><i class="fas fa-envelope"></i> support@jobconnect.com</li>
        <li><i class="fas fa-phone"></i> +91 11 4567 8900</li>
        <li><i class="fas fa-map-marker-alt"></i> Colombo, Sri Lanka</li>
      </ul>
      <div class="footer-social">
        <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
      </div>
    </div>
  </div>

  <div class="footer-bottom">
    <div class="container">
      <p>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. All rights reserved. | Online Job Recruitment System — Academic Project</p>
    </div>
  </div>
</footer>

<script src="<?= e($prefix) ?>assets/js/main.js"></script>
</body>
</html>
