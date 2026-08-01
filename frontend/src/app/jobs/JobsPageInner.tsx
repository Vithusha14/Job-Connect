'use client';

import Link from 'next/link';
import { FormEvent, useEffect, useState } from 'react';
import { useSearchParams } from 'next/navigation';
import { apiFetch, formatSalary } from '@/lib/api';

type JobsResponse = {
  jobs: Array<{
    id: number;
    title: string;
    description: string;
    company_name: string;
    location: string;
    job_type: string;
    salary_min: number;
    salary_max: number;
    deadline: string;
  }>;
  filters: {
    categories: Array<{ id: number; name: string }>;
    locations: string[];
    job_types: string[];
  };
};

export default function JobsPageInner() {
  const params = useSearchParams();
  const [data, setData] = useState<JobsResponse | null>(null);
  const [error, setError] = useState('');

  const load = (query = '') => {
    apiFetch<JobsResponse>(`jobs/list.php${query}`)
      .then(setData)
      .catch((e) => setError(e.message));
  };

  useEffect(() => {
    const q = params.toString();
    load(q ? `?${q}` : '');
  }, [params]);

  const onFilter = (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    const fd = new FormData(e.currentTarget);
    const qs = new URLSearchParams();
    fd.forEach((v, k) => {
      if (String(v)) qs.set(k, String(v));
    });
    load(`?${qs.toString()}`);
    window.history.replaceState(null, '', `/jobs?${qs.toString()}`);
  };

  return (
    <>
      <div className="page-header">
        <div className="container">
          <h1>Browse Jobs</h1>
          <p>{data?.jobs.length ?? 0} openings found</p>
        </div>
      </div>
      <div className="container">
        <form className="filter-bar" onSubmit={onFilter}>
          <div className="form-group">
            <label>Keywords</label>
            <input name="q" className="form-control" defaultValue={params.get('q') || ''} />
          </div>
          <div className="form-group">
            <label>Location</label>
            <select name="location" className="form-control" defaultValue={params.get('location') || ''}>
              <option value="">All</option>
              {data?.filters.locations.map((l) => <option key={l} value={l}>{l}</option>)}
            </select>
          </div>
          <div className="form-group">
            <label>Category</label>
            <select name="category" className="form-control" defaultValue={params.get('category') || ''}>
              <option value="">All</option>
              {data?.filters.categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
            </select>
          </div>
          <div className="form-group">
            <label>Job Type</label>
            <select name="job_type" className="form-control" defaultValue={params.get('job_type') || ''}>
              <option value="">All</option>
              {data?.filters.job_types.map((t) => <option key={t} value={t}>{t}</option>)}
            </select>
          </div>
          <div className="form-group">
            <label>Min Salary</label>
            <input name="salary_min" type="number" className="form-control" defaultValue={params.get('salary_min') || ''} />
          </div>
          <button className="btn btn-primary">Filter</button>
        </form>

        {error && <div className="alert alert-error">{error}</div>}
        <div className="jobs-grid">
          {data?.jobs.map((job) => (
            <article className="job-card" key={job.id}>
              <h3><Link href={`/jobs/${job.id}`}>{job.title}</Link></h3>
              <div className="text-muted" style={{ fontSize: '0.85rem' }}>{job.company_name}</div>
              <p className="text-muted" style={{ fontSize: '0.9rem', marginTop: '0.5rem' }}>
                {job.description.slice(0, 100)}…
              </p>
              <div className="job-meta">
                <span>{job.location}</span>
                <span>{job.job_type}</span>
              </div>
              <div className="job-card-footer">
                <span className="salary">{formatSalary(job.salary_min, job.salary_max)}</span>
                <Link href={`/jobs/${job.id}`} className="btn btn-primary btn-sm">Apply</Link>
              </div>
            </article>
          ))}
        </div>
        {data && data.jobs.length === 0 && <div className="empty">No jobs match your filters.</div>}
      </div>
    </>
  );
}
