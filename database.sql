-- =============================================================================
-- Online Job Recruitment System (JobConnect)
-- Database Schema + Sample Seed Data (Sri Lanka + UK)
-- MySQL 5.7+ / MariaDB — XAMPP / WAMP / Docker compatible
-- =============================================================================
-- Demo login password for ALL accounts: password
-- (bcrypt hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi)
-- Salaries: Sri Lanka roles use LKR (monthly); UK roles use GBP (annual)
-- =============================================================================

CREATE DATABASE IF NOT EXISTS job_recruitment
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE job_recruitment;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS bookmarks;
DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS jobs;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS candidates;
DROP TABLE IF EXISTS employers;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------------------------------
-- Users (shared authentication)
-- -----------------------------------------------------------------------------
CREATE TABLE users (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email      VARCHAR(150) NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,
  role       ENUM('candidate', 'employer', 'admin') NOT NULL,
  status     ENUM('active', 'blocked') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- Candidates (job seekers)
-- -----------------------------------------------------------------------------
CREATE TABLE candidates (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL UNIQUE,
  full_name  VARCHAR(120) NOT NULL,
  phone      VARCHAR(20) DEFAULT NULL,
  photo      VARCHAR(255) DEFAULT NULL,
  skills     TEXT DEFAULT NULL,
  education  TEXT DEFAULT NULL,
  experience TEXT DEFAULT NULL,
  resume     VARCHAR(255) DEFAULT NULL,
  bio        TEXT DEFAULT NULL,
  location   VARCHAR(120) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_candidate_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- Employers (companies / recruiters)
-- -----------------------------------------------------------------------------
CREATE TABLE employers (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id      INT UNSIGNED NOT NULL UNIQUE,
  company_name VARCHAR(150) NOT NULL,
  logo         VARCHAR(255) DEFAULT NULL,
  description  TEXT DEFAULT NULL,
  industry     VARCHAR(100) DEFAULT NULL,
  website      VARCHAR(200) DEFAULT NULL,
  phone        VARCHAR(20) DEFAULT NULL,
  address      VARCHAR(255) DEFAULT NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_employer_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- Job categories
-- -----------------------------------------------------------------------------
CREATE TABLE categories (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL UNIQUE,
  description VARCHAR(255) DEFAULT NULL,
  icon        VARCHAR(50) DEFAULT 'fa-briefcase',
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- Job postings
-- -----------------------------------------------------------------------------
CREATE TABLE jobs (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  employer_id  INT UNSIGNED NOT NULL,
  category_id  INT UNSIGNED NOT NULL,
  title        VARCHAR(200) NOT NULL,
  description  TEXT NOT NULL,
  requirements TEXT NOT NULL,
  salary_min   DECIMAL(12,2) DEFAULT NULL,
  salary_max   DECIMAL(12,2) DEFAULT NULL,
  location     VARCHAR(120) NOT NULL,
  job_type     ENUM('Full-time', 'Part-time', 'Contract', 'Internship', 'Remote') NOT NULL DEFAULT 'Full-time',
  deadline     DATE NOT NULL,
  status       ENUM('pending_approval', 'approved', 'rejected', 'closed') NOT NULL DEFAULT 'pending_approval',
  vacancies    INT UNSIGNED NOT NULL DEFAULT 1,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_job_employer FOREIGN KEY (employer_id) REFERENCES employers(id) ON DELETE CASCADE,
  CONSTRAINT fk_job_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
  INDEX idx_jobs_status (status),
  INDEX idx_jobs_location (location),
  INDEX idx_jobs_type (job_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- Applications
-- -----------------------------------------------------------------------------
CREATE TABLE applications (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  job_id       INT UNSIGNED NOT NULL,
  candidate_id INT UNSIGNED NOT NULL,
  cover_letter TEXT DEFAULT NULL,
  status       ENUM('pending', 'shortlisted', 'rejected', 'selected') NOT NULL DEFAULT 'pending',
  applied_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_app_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
  CONSTRAINT fk_app_candidate FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE,
  UNIQUE KEY uq_job_candidate (job_id, candidate_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- Bookmarks (saved jobs)
-- -----------------------------------------------------------------------------
CREATE TABLE bookmarks (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  candidate_id INT UNSIGNED NOT NULL,
  job_id       INT UNSIGNED NOT NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_bm_candidate FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE,
  CONSTRAINT fk_bm_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
  UNIQUE KEY uq_bookmark (candidate_id, job_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- SEED DATA — Sri Lanka (Colombo-focused) + United Kingdom
-- Password for all accounts: password
-- =============================================================================

INSERT INTO users (id, email, password, role, status) VALUES
(1,  'admin@jobconnect.com',            '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active'),
(2,  'nimal.perera@email.com',          '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'candidate', 'active'),
(3,  'ishara.fernando@email.com',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'candidate', 'active'),
(4,  'dilani.jayasinghe@email.com',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'candidate', 'active'),
(5,  'james.walker@email.com',          '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'candidate', 'active'),
(6,  'emma.collins@email.com',          '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'candidate', 'active'),
(7,  'hr@lankacode.lk',                 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employer', 'active'),
(8,  'careers@ceylontrade.lk',          '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employer', 'active'),
(9,  'recruitment@thamesdigital.co.uk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employer', 'active'),
(10, 'jobs@northernpeak.co.uk',         '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employer', 'active');

INSERT INTO candidates (id, user_id, full_name, phone, skills, education, experience, bio, location) VALUES
(1, 2, 'Nimal Perera', '+94 77 123 4567',
 'PHP, MySQL, JavaScript, Laravel, Git',
 'B.Sc. Computer Science — University of Colombo (2022)',
 '2 years as Junior Software Engineer at a Colombo product team. Built internal tools and REST APIs.',
 'Full-stack developer focused on reliable PHP backends and clean UI integration.',
 'Colombo'),
(2, 3, 'Ishara Fernando', '+94 71 555 8890',
 'Digital marketing, SEO, Google Analytics, Content writing, Social media',
 'BBA Marketing — University of Kelaniya (2023)',
 '1.5 years as Marketing Executive supporting SME campaigns across Western Province.',
 'Marketing professional who enjoys measurable growth and clear storytelling.',
 'Kandy'),
(3, 4, 'Dilani Jayasinghe', '+94 76 441 2200',
 'HR coordination, recruitment, MS Office, onboarding, employee relations',
 'BA Human Resource Management — University of Sri Jayewardenepura (2021)',
 '3 years in people operations for a mid-size services firm in the Southern Province.',
 'Organised HR practitioner focused on fair hiring and smooth onboarding.',
 'Galle'),
(4, 5, 'James Walker', '+44 7700 900123',
 'Java, Spring Boot, REST APIs, SQL, Docker, Agile',
 'B.Sc. Software Engineering — University of Manchester (2020)',
 '4 years building backend services for fintech and SaaS teams in the North West.',
 'Backend engineer interested in scalable systems and practical delivery.',
 'Manchester'),
(5, 6, 'Emma Collins', '+44 7700 900456',
 'UI design, Figma, HTML/CSS, user research, accessibility',
 'BA Graphic Design — University of the Arts London (2021)',
 '3 years designing product interfaces for digital agencies in Central London.',
 'Product-minded designer who balances clarity, accessibility, and brand.',
 'London');

INSERT INTO employers (id, user_id, company_name, description, industry, website, phone, address) VALUES
(1, 7, 'LankaCode Technologies',
 'Colombo-based software company delivering web platforms, cloud integrations, and digital transformation for banks, logistics, and public-sector clients across Sri Lanka.',
 'Information Technology', 'https://www.lankacode.lk', '+94 11 234 5600',
 'Level 18, World Trade Center, Colombo 01, Sri Lanka'),
(2, 8, 'Ceylon Trade Partners',
 'Import-export and wholesale distribution group headquartered in Colombo, supporting FMCG, apparel accessories, and regional retail networks.',
 'Trading & Distribution', 'https://www.ceylontrade.lk', '+94 11 256 7800',
 'No. 42, D.R. Wijewardene Mawatha, Colombo 10, Sri Lanka'),
(3, 9, 'Thames Digital Ltd',
 'London product studio building customer portals, analytics dashboards, and marketing platforms for UK mid-market clients.',
 'Information Technology', 'https://www.thamesdigital.co.uk', '+44 20 7946 0123',
 '14 Shoreditch High Street, London E1 6PG, United Kingdom'),
(4, 10, 'Northern Peak Consulting',
 'Manchester consultancy helping UK organisations with finance operations, people programmes, and commercial growth projects.',
 'Professional Services', 'https://www.northernpeak.co.uk', '+44 161 496 0100',
 'Spinningfields, 1 Hardman Square, Manchester M3 3EB, United Kingdom');

INSERT INTO categories (id, name, description, icon) VALUES
(1, 'Information Technology', 'Software, web, and IT infrastructure roles', 'fa-laptop-code'),
(2, 'Marketing', 'Digital marketing, branding, and growth roles', 'fa-bullhorn'),
(3, 'Finance', 'Accounting, banking, and financial analysis', 'fa-chart-line'),
(4, 'Human Resources', 'Recruitment, people ops, and talent management', 'fa-users'),
(5, 'Sales', 'Business development and account management', 'fa-handshake'),
(6, 'Healthcare', 'Clinical, pharma, and health admin roles', 'fa-heartbeat'),
(7, 'Education', 'Teaching, training, and academic roles', 'fa-graduation-cap'),
(8, 'Design', 'UI/UX, graphic, and product design', 'fa-palette');

-- Salaries: LK = LKR monthly; UK = GBP annual
INSERT INTO jobs (id, employer_id, category_id, title, description, requirements, salary_min, salary_max, location, job_type, deadline, status, vacancies) VALUES
(1, 1, 1, 'PHP Web Developer',
 'Build and maintain client web applications for LankaCode''s Colombo delivery team using PHP, MySQL, and modern JavaScript.',
 '• 1–3 years PHP and MySQL experience\n• Strong HTML, CSS, and JavaScript\n• Familiar with Git and Agile delivery\n• Based in or willing to work from Colombo',
 180000, 320000, 'Colombo', 'Full-time', DATE_ADD(CURDATE(), INTERVAL 45 DAY), 'approved', 2),

(2, 1, 1, 'Frontend Developer',
 'Create responsive interfaces for enterprise portals. Work closely with designers and backend engineers in our Colombo studio.',
 '• Solid JavaScript (ES6+)\n• Responsive CSS (Flexbox/Grid)\n• Experience consuming REST APIs\n• Portfolio or GitHub samples preferred',
 200000, 350000, 'Colombo', 'Full-time', DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'approved', 1),

(3, 1, 2, 'Digital Marketing Executive',
 'Plan and run digital campaigns for LankaCode and selected clients, including SEO, content, and performance reporting.',
 '• 1+ year digital marketing experience\n• Hands-on with Analytics and Ads tools\n• Strong written English\n• Comfortable collaborating with sales teams',
 120000, 200000, 'Kandy', 'Full-time', DATE_ADD(CURDATE(), INTERVAL 20 DAY), 'approved', 1),

(4, 2, 5, 'Sales Executive — Regional Trade',
 'Grow retail and wholesale accounts for Ceylon Trade Partners across the Southern and Western provinces.',
 '• Degree in any discipline\n• 0–2 years sales or customer-facing experience\n• Willingness to travel within Sri Lanka\n• Strong interpersonal skills in Sinhala/English',
 90000, 160000, 'Galle', 'Full-time', DATE_ADD(CURDATE(), INTERVAL 25 DAY), 'approved', 3),

(5, 2, 4, 'HR Coordinator',
 'Support recruitment, onboarding, and employee engagement for Ceylon Trade Partners'' Colombo head office.',
 '• Degree in HR or Business Administration\n• Strong MS Office skills\n• Organised and people-oriented\n• Prior HR internship or 1 year experience preferred',
 110000, 180000, 'Colombo', 'Full-time', DATE_ADD(CURDATE(), INTERVAL 40 DAY), 'approved', 1),

(6, 3, 1, 'Java Backend Engineer',
 'Design and maintain Spring Boot services powering Thames Digital customer products for UK clients.',
 '• 3+ years Java / Spring Boot\n• Solid SQL and API design\n• Experience with CI/CD or Docker helpful\n• Eligible to work in the UK',
 42000, 58000, 'London', 'Full-time', DATE_ADD(CURDATE(), INTERVAL 35 DAY), 'approved', 2),

(7, 3, 8, 'Product Designer',
 'Own end-to-end UI/UX for SaaS dashboards at Thames Digital — from research to polished Figma specs.',
 '• 2+ years product or UI design experience\n• Strong Figma skills\n• Understanding of accessibility basics\n• Portfolio demonstrating shipped work',
 38000, 52000, 'London', 'Full-time', DATE_ADD(CURDATE(), INTERVAL 28 DAY), 'approved', 1),

(8, 4, 3, 'Finance Analyst',
 'Support budgeting, forecasting, and client reporting for Northern Peak Consulting engagements.',
 '• Degree in Finance, Accounting, or related field\n• Advanced Excel\n• Clear communication with non-finance stakeholders\n• ACA/ACCA progress is a plus',
 32000, 42000, 'Manchester', 'Full-time', DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'approved', 1),

(9, 4, 4, 'People Operations Assistant',
 'Assist consultants with recruitment coordination, interview scheduling, and HR documentation for UK projects.',
 '• Interest in HR / people operations\n• Excellent organisation and written English\n• Confident with spreadsheets and calendars\n• Previous admin experience preferred',
 24000, 30000, 'Manchester', 'Part-time', DATE_ADD(CURDATE(), INTERVAL 22 DAY), 'pending_approval', 1),

(10, 1, 1, 'Software Engineering Intern',
 'Paid internship with LankaCode mentors covering PHP APIs, code reviews, and delivery practices.',
 '• Final-year CS/IT student or recent graduate\n• Basic PHP or JavaScript\n• Eager to learn in a Colombo delivery team\n• Available for 3–6 months',
 40000, 60000, 'Colombo', 'Internship', DATE_ADD(CURDATE(), INTERVAL 15 DAY), 'pending_approval', 2);

INSERT INTO applications (job_id, candidate_id, cover_letter, status) VALUES
(1, 1, 'I have hands-on PHP/MySQL experience from Colombo product teams and would like to join LankaCode.', 'shortlisted'),
(2, 1, 'My frontend work with responsive CSS and REST APIs matches this role well.', 'pending'),
(3, 2, 'I have run digital campaigns for SMEs and can support LankaCode''s growth programmes from Kandy.', 'pending'),
(5, 3, 'My HR coordination experience in Galle and Colombo makes me a strong fit for this role.', 'pending'),
(6, 4, 'I have four years of Java/Spring experience in Manchester and am open to a London-based team.', 'shortlisted'),
(7, 5, 'Please find my London product design portfolio — I focus on clarity and accessibility.', 'pending'),
(4, 2, 'I am open to regional travel and sales work with Ceylon Trade Partners.', 'rejected');

INSERT INTO bookmarks (candidate_id, job_id) VALUES
(1, 3),
(1, 10),
(2, 1),
(3, 5),
(4, 6),
(5, 7);

-- =============================================================================
-- Quick reference — Demo Accounts (password: password)
-- =============================================================================
-- Admin:     admin@jobconnect.com
-- Candidate: nimal.perera@email.com | ishara.fernando@email.com | dilani.jayasinghe@email.com
--            james.walker@email.com | emma.collins@email.com
-- Employer:  hr@lankacode.lk | careers@ceylontrade.lk
--            recruitment@thamesdigital.co.uk | jobs@northernpeak.co.uk
-- =============================================================================
