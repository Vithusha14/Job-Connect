# JobConnect Frontend (Next.js)

This folder is the **Next.js frontend**. The **PHP backend API** lives in `/api` and still uses **MySQL** (`database.sql`).

## Architecture

```
Browser (Next.js on :3000)
    → JSON calls → PHP API (Apache on :8080/api/...)
    → MySQL (job_recruitment)
```

## Run (two terminals)

### 1) Backend (PHP + MySQL) — Docker
```bash
# from project root
docker compose up -d
```
API base: http://localhost:8080/api/

### 2) Frontend (Next.js)
```bash
cd frontend
copy .env.local.example .env.local
npm install
npm run dev
```
Open: http://localhost:3000

## Environment
`frontend/.env.local`:
```
NEXT_PUBLIC_API_BASE=http://localhost:8080
```

## Notes
- Old PHP pages (`index.php`, `candidate/*.php`, etc.) still work at :8080 if you want the classic UI.
- The Next.js UI talks only to `/api/*` endpoints.
- Auth uses Bearer tokens stored in `localStorage`.
