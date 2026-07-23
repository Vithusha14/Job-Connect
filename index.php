<?php
/**
 * Landing page — Online Job Recruitment System
 */
$pageDepth = 0;
require_once __DIR__ . '/includes/init.php';

$pdo = getDB();

// Stats
$totalJobs = (int) $pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'approved' AND deadline >= CURDATE()")->fetchColumn();
$totalCompanies = (int) $pdo->query('SELECT COUNT(*) FROM employers')->fetchColumn();
$totalCandidates = (int) $pdo->query('SELECT COUNT(*) FROM candidates')->fetchColumn();

// Featured jobs
$featured = $pdo->query(
    "SELECT j.*, c.name AS category_name, e.company_name, e.logo
     FROM jobs j
     JOIN categories c ON c.id = j.category_id
     JOIN employers e ON e.id = j.employer_id
     WHERE j.status = 'approved' AND j.deadline >= CURDATE()
     ORDER BY j.created_at DESC
     LIMIT 6"
)->fetchAll();

// Categories with job counts
$categories = $pdo->query(
    "SELECT cat.*, COUNT(j.id) AS job_count
     FROM categories cat
     LEFT JOIN jobs j ON j.category_id = cat.id AND j.status = 'approved' AND j.deadline >= CURDATE()
     GROUP BY cat.id
     ORDER BY cat.name ASC"
)->fetchAll();

$pageTitle = 'Find Your Dream Job';
require_once __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div class="container">
    <div class="hero-content">
      <div class="hero-brand"><?= e(APP_NAME) ?></div>
      <h1>Find work that fits your future</h1>
      <p>Search thousands of openings from trusted employers. Build your profile, apply in one click, and track every application.</p>

      <form class="hero-search" action="jobs.php" method="GET" role="search">
        <input type="text" name="q" placeholder="Job title, skill, or keyword" aria-label="Search keywords">
        <select name="location" aria-label="Location">
          <option value="">All Locations</option>
          <?php
          $locations = $pdo->query("SELECT DISTINCT location FROM jobs WHERE status = 'approved' ORDER BY location")->fetchAll(PDO::FETCH_COLUMN);
          foreach ($locations as $loc):
          ?>
            <option value="<?= e($loc) ?>"><?= e($loc) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search Jobs</button>
      </form>

      <div class="hero-stats">
        <div class="hero-stat">
          <strong><?= $totalJobs ?>+</strong>
          <span>Open Positions</span>
        </div>
        <div class="hero-stat">
          <strong><?= $totalCompanies ?>+</strong>
          <span>Companies</span>
        </div>
        <div class="hero-stat">
          <strong><?= $totalCandidates ?>+</strong>
          <span>Candidates</span>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-header">
      <div>
        <h2>Featured Jobs</h2>
        <p>Latest opportunities approved and ready for applicants</p>
      </div>
      <a href="jobs.php" class="btn btn-outline">View All Jobs <i class="fas fa-arrow-right"></i></a>
    </div>

    <?php if (empty($featured)): ?>
      <div class="empty-state">
        <i class="fas fa-briefcase"></i>
        <h3>No jobs available yet</h3>
        <p>Check back soon for new openings.</p>
      </div>
    <?php else: ?>
      <div class="jobs-grid">
        <?php foreach ($featured as $job): ?>
          <article class="job-card">
            <div class="job-card-top">
              <div class="company-logo" title="<?= e($job['company_name']) ?>">
                <?php if ($job['logo']): ?>
                  <img src="<?= e(url('uploads/logos/' . $job['logo'])) ?>" alt="<?= e($job['company_name']) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:10px;">
                <?php else: ?>
                  <?= e(strtoupper(substr($job['company_name'], 0, 1))) ?>
                <?php endif; ?>
              </div>
              <div>
                <h3><a href="job-details.php?id=<?= (int) $job['id'] ?>"><?= e($job['title']) ?></a></h3>
                <div class="text-muted" style="font-size:0.85rem;"><?= e($job['company_name']) ?></div>
              </div>
            </div>
            <div class="job-meta">
              <span><i class="fas fa-map-marker-alt"></i> <?= e($job['location']) ?></span>
              <span><i class="fas fa-clock"></i> <?= e($job['job_type']) ?></span>
              <span><i class="fas fa-tag"></i> <?= e($job['category_name']) ?></span>
            </div>
            <div class="job-card-footer">
              <span class="salary"><?= e(formatSalary($job['salary_min'], $job['salary_max'])) ?></span>
              <a href="job-details.php?id=<?= (int) $job['id'] ?>" class="btn btn-ghost btn-sm">View Details</a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="section-header">
      <div>
        <h2>Browse by Category</h2>
        <p>Explore openings across popular industries</p>
      </div>
    </div>
    <div class="categories-grid">
      <?php foreach ($categories as $cat): ?>
        <a class="category-card" href="jobs.php?category=<?= (int) $cat['id'] ?>">
          <i class="fas <?= e($cat['icon'] ?: 'fa-briefcase') ?>"></i>
          <h3><?= e($cat['name']) ?></h3>
          <span><?= (int) $cat['job_count'] ?> open job<?= $cat['job_count'] == 1 ? '' : 's' ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-header" style="justify-content:center;text-align:center;">
      <div>
        <h2>How It Works</h2>
        <p>Three simple steps to your next opportunity</p>
      </div>
    </div>
    <div class="steps-grid">
      <div class="step-card">
        <div class="step-num">1</div>
        <h3>Create Your Profile</h3>
        <p>Register as a candidate, add skills, education, and upload your resume.</p>
      </div>
      <div class="step-card">
        <div class="step-num">2</div>
        <h3>Search &amp; Apply</h3>
        <p>Filter jobs by category, location, salary, and type — then apply in one click.</p>
      </div>
      <div class="step-card">
        <div class="step-num">3</div>
        <h3>Track Progress</h3>
        <p>Monitor application status from Pending to Shortlisted, Selected, or Rejected.</p>
      </div>
    </div>
  </div>
</section>

<section class="cta-band">
  <div class="container">
    <h2>Ready to hire or get hired?</h2>
    <p>Join JobConnect today — free for candidates and employers.</p>
    <div class="cta-actions">
      <a href="register.php?role=candidate" class="btn btn-outline-light btn-lg"><i class="fas fa-user"></i> I'm a Job Seeker</a>
      <a href="register.php?role=employer" class="btn btn-primary btn-lg" style="background:#fff;color:var(--primary-900);box-shadow:none;"><i class="fas fa-building"></i> I'm an Employer</a>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
