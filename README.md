# JobConnect - Online Job Recruitment System

A full-stack academic web application that connects **Job Seekers (Candidates)**, **Employers/Recruiters**, and an **Administrator**.

---

## 1. Project Overview

JobConnect is a modern, responsive online job portal similar in idea to LinkedIn / Naukri / Indeed, but simpler and fully custom-built for college / Agile project presentations.

| Item | Detail |
|------|--------|
| Project name | JobConnect (Online Job Recruitment System) |
| Architecture | Next.js frontend + Laravel JSON API (+ classic PHP pages) |
| Users | Candidate, Employer, Admin |
| Branching | `main` (default) · `develop` (active development) |

---

## 2. Languages & Tech Stack

### Frontend (Next.js — primary UI)
| Technology | Purpose |
|------------|---------|
| **Next.js 14** (React 18) | App Router pages, client components, routing |
| **TypeScript** | Typed frontend code |
| **CSS3** | Blue theme & responsive layout (`frontend/src/app/globals.css`) |

Folder: `frontend/` → run with `npm run dev` on port **3000**.

### Classic Frontend (still available)
Original PHP-rendered pages (HTML/CSS/vanilla JS) still work on port **8080** if needed.

### Backend (Laravel — primary API)
| Technology | Purpose |
|------------|---------|
| **Laravel 13** (PHP 8.4) | JSON REST API under `backend/` → port **8000** |
| **Eloquent + PDO** | Models mapped to existing MySQL tables |
| **HMAC Bearer tokens** | API auth compatible with the Next.js client |

Classic PHP pages + legacy `api/` scripts still run on port **8080** if needed.

### Database
| Technology | Purpose |
|------------|---------|
| **MySQL 5.7+ / MariaDB** | Relational data storage |
| **`database.sql`** | Full schema + sample seed data (import once) |

### Optional (local Docker run)
| Technology | Purpose |
|------------|---------|
| **Docker Compose** | PHP Apache + MySQL containers (`docker-compose.yml`) |

---

## 3. Features by Role

### Candidate (Job Seeker)
- Register / login (password hashing)
- Profile: photo, skills, education, experience, PDF resume
- Browse / search / filter jobs (category, location, salary, type)
- Apply to jobs and track status (Pending / Shortlisted / Rejected / Selected)
- Save / bookmark jobs
- Personal dashboard

### Employer / Recruiter
- Register / login with company profile (name, logo, industry, description)
- Post new jobs (title, description, requirements, salary, location, deadline, category)
- Edit / close / delete job postings
- View applicants, open resumes, update application status
- Dashboard stats (jobs posted, applicants)

### Admin
- Secure admin login (same auth system, `role = admin`)
- Manage users (view / block / delete candidates & employers)
- Approve / reject / close / remove job postings
- Manage job categories
- Statistics dashboard with simple bar charts

---

## 4. Folder Structure

```
Job-Connect/
├── backend/               # Laravel 13 JSON API (port 8000)
│   ├── app/Http/Controllers/Api/
│   ├── app/Models/
│   ├── routes/api.php
│   └── Dockerfile
├── frontend/              # Next.js 14 UI (port 3000)
├── api/                   # Legacy plain-PHP JSON API (optional)
├── admin/                 # Classic PHP admin panel
├── candidate/             # Classic PHP candidate pages
├── employer/              # Classic PHP employer pages
├── assets/
├── includes/
├── uploads/
├── database.sql
├── docker-compose.yml     # web (8080) + laravel (8000) + mysql (3307)
└── README.md
```

---

## 5. Database (`database.sql`) — Full Explanation

Import this single file in phpMyAdmin (or MySQL CLI). It creates everything needed for a working demo.

### 5.1 Database name
- **`job_recruitment`** — charset `utf8mb4`

### 5.2 Tables

| Table | Purpose | Key columns |
|-------|---------|-------------|
| **users** | Shared login for all roles | `email`, `password` (bcrypt), `role` (`candidate` / `employer` / `admin`), `status` (`active` / `blocked`) |
| **candidates** | Job seeker profiles | `full_name`, `phone`, `photo`, `skills`, `education`, `experience`, `resume`, `bio`, `location` |
| **employers** | Company profiles | `company_name`, `logo`, `description`, `industry`, `website`, `phone`, `address` |
| **categories** | Job categories | `name`, `description`, `icon` (Font Awesome class) |
| **jobs** | Job postings | `title`, `description`, `requirements`, `salary_min` / `salary_max`, `location`, `job_type`, `deadline`, `status` (`pending_approval` / `approved` / `rejected` / `closed`), `vacancies` |
| **applications** | Candidate applications to jobs | `job_id`, `candidate_id`, `cover_letter`, `status` (`pending` / `shortlisted` / `rejected` / `selected`) |
| **bookmarks** | Saved jobs | `candidate_id`, `job_id` |

### 5.3 Relationships
- `candidates.user_id` → `users.id`
- `employers.user_id` → `users.id`
- `jobs.employer_id` → `employers.id`
- `jobs.category_id` → `categories.id`
- `applications` → `jobs` + `candidates` (unique pair)
- `bookmarks` → `candidates` + `jobs` (unique pair)

### 5.4 Seed / sample data included
- **1 admin**, **3 candidates**, **2 employers**
- **8 categories** (IT, Marketing, Finance, HR, Sales, Healthcare, Education, Design)
- **Multiple approved jobs** + sample applications and bookmarks
- Seed password for all demo users: **`password`** (bcrypt hashed in SQL)

> Note: Demo credentials are for local/academic testing only. Do not expose them on a public production site.

---

## 6. Frontend Details

- **Responsive** layouts for mobile, tablet, and desktop
- **Blue theme:** `#0D47A1`, `#1565C0`, `#1976D2`, accent `#E3F2FD`
- Landing page: hero search, featured jobs, categories, how-it-works, CTA
- Shared header / footer includes
- Custom confirm modal (replaces browser `confirm()`)
- Flash success / error / warning alerts

---

## 7. Backend Details

- Session-based authentication
- Role-based access control (Candidate / Employer / Admin)
- `password_hash()` / `password_verify()`
- PDO prepared statements (SQL injection protection)
- CSRF tokens on POST forms
- File upload validation:
  - Resumes → PDF only (max 2 MB)
  - Photos / logos → image types (max 1 MB)
- Output escaping via `e()` helper (`htmlspecialchars`)

---

## 8. Setup Guide (XAMPP)

### Step 1 — Clone the repository
```bash
git clone <your-repo-url>
cd Job-Connect
```

Or copy the project into:
```
C:\xampp\htdocs\Job-Connect
```

### Step 2 — Start Apache + MySQL
Open **XAMPP Control Panel** → Start **Apache** and **MySQL**.

### Step 3 — Import `database.sql`
1. Open [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. **Import** → choose `database.sql`
3. Click **Go**

### Step 4 — Configure DB (if needed)
Edit `includes/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'job_recruitment');
define('DB_USER', 'root');
define('DB_PASS', '');   // empty by default on XAMPP
```

### Step 5 — Run
```
http://localhost/Job-Connect/
```

---

## 9. Setup with Docker + Next.js (recommended)

### Terminal 1 — Laravel API + MySQL (+ classic PHP)
```bash
docker compose up -d
```
- **Laravel API:** http://localhost:8000/api/  
- Classic PHP site / uploads: http://localhost:8080/  
- MySQL host port: `3307` (user `root` / password `root`)

Quick check:
```bash
curl http://localhost:8000/api/home
```

### Terminal 2 — Next.js frontend
```bash
cd frontend
copy .env.local.example .env.local
npm install
npm run dev
```
Open: **http://localhost:3000**

`.env.local` should point at Laravel:
```
NEXT_PUBLIC_API_BASE=http://localhost:8000
NEXT_PUBLIC_UPLOAD_BASE=http://localhost:8080
```

> If `npm install` fails (network/SSL), fix npm registry access on your PC, then retry.
> After changing Laravel PHP code under `backend/app`, rebuild is not needed (source is mounted). After `composer install` / vendor changes, run `docker compose build laravel && docker compose up -d laravel`.
---

## 10. Git Workflow

```bash
# Work on develop
git checkout develop

# After changes
git add .
git commit -m "Your message"
git push -u origin develop
```

Suggested branches:
- **`main`** — stable / release
- **`develop`** — ongoing feature work

---

## 11. Academic / Agile Mapping

| Artifact | Project mapping |
|----------|-----------------|
| SRS modules | `candidate/`, `employer/`, `admin/` |
| Use cases | Register, Login, Post Job, Apply, Approve Job, etc. |
| ER diagram | Tables in `database.sql` |
| Test data | Seed rows in `database.sql` |
| Modular design | Shared `includes/` + role folders |

---

## 12. Troubleshooting

| Problem | Fix |
|---------|-----|
| Database connection failed | Start MySQL and import `database.sql` |
| Blank / PHP error page | Check `includes/config.php` credentials |
| Upload failed | Ensure `uploads/*` folders are writable |
| CSS/JS not updating | Hard refresh (`Ctrl + F5`) |

---

## License

Academic / educational project — free to use and modify for college presentations.
