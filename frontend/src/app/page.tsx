'use client';

import Link from 'next/link';
import { FormEvent, useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { apiFetch, formatSalary } from '@/lib/api';

type HomeData = {
  stats: { jobs: number; companies: number; candidates: number };
  featured: Array<{
    id: number;
    title: string;
    company_name: string;
    location: string;
    job_type: string;
    category_name: string;
    salary_min: number;
    salary_max: number;
  }>;
  categories: Array<{ id: number; name: string; icon: string; job_count: number }>;
  locations: string[];
};

export default function HomePage() {
  const router = useRouter();
  const [data, setData] = useState<HomeData | null>(null);
  const [error, setError] = useState('');

  useEffect(() => {
    apiFetch<HomeData>('home.php')
      .then(setData)
      .catch((e) => setError(e.message));
  }, []);

  const onSearch = (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    const fd = new FormData(e.currentTarget);
    const q = String(fd.get('q') || '');
    const location = String(fd.get('location') || '');
    const params = new URLSearchParams();
    if (q) params.set('q', q);
    if (location) params.set('location', location);
    router.push(`/jobs?${params.toString()}`);
  };

  return (
    <>
      <section className="hero">
        <div className="container">
          <div className="hero-brand">JobConnect</div>
          <h1>Find work that fits your future</h1>
          <p>Search openings from trusted employers. Build your profile, apply in one click, and track every application.</p>
          <form className="hero-search" onSubmit={onSearch}>
            <input name="q" placeholder="Job title, skill, or keyword" />
            <select name="location" defaultValue="">
              <option value="">All Locations</option>
              {data?.locations.map((loc) => (
                <option key={loc} value={loc}>{loc}</option>
              ))}
            </select>
            <button className="btn btn-primary" type="submit">Search Jobs</button>
          </form>
          <div className="hero-stats">
            <div className="hero-stat"><strong>{data?.stats.jobs ?? '—'}+</strong><span>Open Positions</span></div>
            <div className="hero-stat"><strong>{data?.stats.companies ?? '—'}+</strong><span>Companies</span></div>
            <div className="hero-stat"><strong>{data?.stats.candidates ?? '—'}+</strong><span>Candidates</span></div>
          </div>
        </div>
      </section>

      <section className="section">
        <div className="container">
          <div className="section-header">
            <div>
              <h2>Featured Jobs</h2>
              <p className="text-muted">Latest approved openings</p>
            </div>
            <Link href="/jobs" className="btn btn-outline">View All</Link>
          </div>
          {error && <div className="alert alert-error">{error}</div>}
          <div className="jobs-grid">
            {data?.featured.map((job) => (
              <article className="job-card" key={job.id}>
                <h3><Link href={`/jobs/${job.id}`}>{job.title}</Link></h3>
                <div className="text-muted" style={{ fontSize: '0.85rem' }}>{job.company_name}</div>
                <div className="job-meta">
                  <span>{job.location}</span>
                  <span>{job.job_type}</span>
                  <span>{job.category_name}</span>
                </div>
                <div className="job-card-footer">
                  <span className="salary">{formatSalary(job.salary_min, job.salary_max)}</span>
                  <Link href={`/jobs/${job.id}`} className="btn btn-ghost btn-sm">View</Link>
                </div>
              </article>
            ))}
          </div>
        </div>
      </section>

      <section className="section section-alt">
        <div className="container">
          <div className="section-header">
            <div>
              <h2>Browse by Category</h2>
              <p className="text-muted">Explore openings by industry</p>
            </div>
          </div>
          <div className="categories-grid">
            {data?.categories.map((cat) => (
              <Link key={cat.id} href={`/jobs?category=${cat.id}`} className="category-card">
                <h3>{cat.name}</h3>
                <span>{cat.job_count} open</span>
              </Link>
            ))}
          </div>
        </div>
      </section>
    </>
  );
}
