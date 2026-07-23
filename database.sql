-- =============================================================================
-- Online Job Recruitment System (JobConnect)
-- Database Schema + Sample Seed Data
-- MySQL 5.7+ / MariaDB — XAMPP / WAMP compatible
-- =============================================================================
-- Demo login password for ALL accounts: password
-- (bcrypt hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi)
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
-- SEED DATA
-- Password for all accounts: password
-- =============================================================================

INSERT INTO users (id, email, password, role, status) VALUES
(1, 'admin@jobconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active'),
(2, 'rahul.sharma@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'candidate', 'active'),
(3, 'priya.patel@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'candidate', 'active'),
(4, 'amit.kumar@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'candidate', 'active'),
(5, 'hr@techvista.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employer', 'active'),
(6, 'careers@greenleaf.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employer', 'active');

INSERT INTO candidates (id, user_id, full_name, phone, skills, education, experience, bio, location) VALUES
(1, 2, 'Rahul Sharma', '9876543210',
 'PHP, MySQL, JavaScript, HTML, CSS, Laravel basics',
 'B.Tech Computer Science — Delhi Technological University (2022)',
 '2 years as Junior Web Developer at SoftSolutions Pvt Ltd. Built internal dashboards and REST APIs.',
 'Passionate full-stack developer focused on clean PHP and modern front-end.',
 'Colombo'),
(2, 3, 'Priya Patel', '9123456780',
 'Python, Data Analysis, SQL, Excel, Power BI, Communication',
 'M.Sc Data Science — University of Mumbai (2023)',
 '1 year as Data Analyst Intern at AnalyticsHub. Created reports and dashboards for sales teams.',
 'Detail-oriented analyst who enjoys turning data into actionable insights.',
 'Mumbai'),
(3, 4, 'Amit Kumar', '9988776655',
 'Java, Spring Boot, REST APIs, Git, Agile, Problem Solving',
 'B.E. Information Technology — Pune University (2021)',
 '3 years as Software Engineer at CodeCraft Technologies. Developed microservices and mentored juniors.',
 'Backend engineer with a strong interest in scalable systems and clean architecture.',
 'Kandy');

INSERT INTO employers (id, user_id, company_name, description, industry, website, phone, address) VALUES
(1, 5, 'TechVista Solutions',
 'TechVista Solutions is a leading IT services company specializing in custom software, cloud migration, and digital transformation for enterprises across South Asia.',
 'Information Technology', 'https://www.techvista.example.com', '011-45678901',
 'Level 12, World Trade Center, Colombo 01, Sri Lanka'),
(2, 6, 'GreenLeaf Foods',
 'GreenLeaf Foods manufactures and distributes organic packaged foods. We are expanding our digital and operations teams to support nationwide growth.',
 'Food & Beverage', 'https://www.greenleaf.example.com', '022-33445566',
 '45 Industrial Estate, Andheri East, Mumbai');

INSERT INTO categories (id, name, description, icon) VALUES
(1, 'Information Technology', 'Software, web, and IT infrastructure roles', 'fa-laptop-code'),
(2, 'Marketing', 'Digital marketing, branding, and growth roles', 'fa-bullhorn'),
(3, 'Finance', 'Accounting, banking, and financial analysis', 'fa-chart-line'),
(4, 'Human Resources', 'Recruitment, people ops, and talent management', 'fa-users'),
(5, 'Sales', 'Business development and account management', 'fa-handshake'),
(6, 'Healthcare', 'Clinical, pharma, and health admin roles', 'fa-heartbeat'),
(7, 'Education', 'Teaching, training, and academic roles', 'fa-graduation-cap'),
(8, 'Design', 'UI/UX, graphic, and product design', 'fa-palette');

INSERT INTO jobs (id, employer_id, category_id, title, description, requirements, salary_min, salary_max, location, job_type, deadline, status, vacancies) VALUES
(1, 1, 1, 'PHP Web Developer',
 'We are looking for a skilled PHP Web Developer to build and maintain web applications for our enterprise clients. You will work with a collaborative team using PHP, MySQL, and modern JavaScript.',
 '• 1–3 years experience with PHP and MySQL\n• Strong HTML, CSS, and JavaScript\n• Knowledge of PDO / prepared statements\n• Familiarity with Git and Agile workflows\n• Good communication skills',
 350000, 600000, 'Colombo', 'Full-time', DATE_ADD(CURDATE(), INTERVAL 45 DAY), 'approved', 2),

(2, 1, 1, 'Frontend Developer (JavaScript)',
 'Join TechVista to craft responsive, accessible user interfaces. You will convert designs into polished HTML/CSS/JS experiences and collaborate with backend engineers.',
 '• Solid JavaScript (ES6+)\n• Responsive CSS (Flexbox/Grid)\n• Experience with REST APIs\n• Attention to UI detail and accessibility\n• Portfolio of previous work preferred',
 400000, 700000, 'Remote', 'Remote', DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'approved', 1),

(3, 1, 2, 'Digital Marketing Executive',
 'Drive online campaigns for TechVista and client brands. Own SEO, content calendars, and performance reporting.',
 '• 1+ year in digital marketing\n• Hands-on with Google Analytics & Ads\n• Strong writing and social media skills\n• Basic HTML knowledge is a plus',
 300000, 450000, 'Kandy', 'Full-time', DATE_ADD(CURDATE(), INTERVAL 20 DAY), 'approved', 1),

(4, 2, 5, 'Sales Executive - FMCG',
 'GreenLeaf Foods needs energetic sales executives to expand retail distribution across Western India.',
 '• Graduate in any discipline\n• 0–2 years sales experience\n• Willingness to travel\n• Excellent interpersonal skills',
 250000, 400000, 'Mumbai', 'Full-time', DATE_ADD(CURDATE(), INTERVAL 25 DAY), 'approved', 3),

(5, 2, 4, 'HR Coordinator',
 'Support end-to-end recruitment and employee engagement for GreenLeaf Foods. Coordinate interviews, onboarding, and HR documentation.',
 '• Degree in HR / Business Administration\n• Strong MS Office skills\n• Organized and people-oriented\n• Prior HR internship preferred',
 280000, 420000, 'Galle', 'Full-time', DATE_ADD(CURDATE(), INTERVAL 40 DAY), 'approved', 1),

(6, 1, 1, 'Java Backend Intern',
 'Paid internship for students/freshers to learn Spring Boot microservices under senior mentors at TechVista.',
 '• Pursuing or completed B.Tech/B.E. in CS/IT\n• Basic Java knowledge\n• Eager to learn and contribute\n• Available for 3–6 months',
 120000, 180000, 'Pune', 'Internship', DATE_ADD(CURDATE(), INTERVAL 15 DAY), 'approved', 2),

(7, 2, 3, 'Accounts Assistant',
 'Maintain day-to-day books, vendor payments, and GST filings for GreenLeaf Foods.',
 '• B.Com / M.Com\n• Knowledge of Tally or similar\n• Attention to detail\n• 1 year experience preferred',
 220000, 320000, 'Mumbai', 'Part-time', DATE_ADD(CURDATE(), INTERVAL 35 DAY), 'pending_approval', 1);

INSERT INTO applications (job_id, candidate_id, cover_letter, status) VALUES
(1, 1, 'I have strong PHP/MySQL experience and would love to contribute to TechVista''s web projects.', 'shortlisted'),
(2, 1, 'My frontend skills in HTML/CSS/JS align well with this remote role.', 'pending'),
(6, 3, 'I am a Java developer looking to deepen Spring Boot skills through this internship.', 'pending'),
(4, 2, 'I am relocating to Mumbai and eager to start a sales career in FMCG.', 'pending'),
(5, 2, 'My analytical background and people skills make me a strong fit for HR coordination.', 'rejected');

INSERT INTO bookmarks (candidate_id, job_id) VALUES
(1, 3),
(1, 6),
(2, 1),
(3, 1),
(3, 2);

-- =============================================================================
-- Quick reference — Demo Accounts (password: password)
-- =============================================================================
-- Admin:     admin@jobconnect.com
-- Candidate: rahul.sharma@email.com | priya.patel@email.com | amit.kumar@email.com
-- Employer:  hr@techvista.com | careers@greenleaf.com
-- =============================================================================
