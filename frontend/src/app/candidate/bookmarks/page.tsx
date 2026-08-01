'use client';

import Link from 'next/link';
import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { apiFetch, formatSalary } from '@/lib/api';
import { useAuth } from '@/context/AuthContext';

export default function BookmarksPage() {
  const { user, loading } = useAuth();
  const router = useRouter();
  const [items, setItems] = useState<any[]>([]);

  const load = () => apiFetch<{ bookmarks: any[] }>('candidate/bookmarks.php').then((d) => setItems(d.bookmarks));

  useEffect(() => {
    if (loading) return;
    if (!user || user.role !== 'candidate') router.replace('/login');
    else load();
  }, [user, loading, router]);

  const remove = async (jobId: number) => {
    await apiFetch('candidate/bookmarks.php?action=remove', {
      method: 'POST',
      body: { job_id: jobId } as unknown as BodyInit,
    });
    load();
  };

  return (
    <>
      <div className="page-header"><div className="container"><h1>Saved Jobs</h1></div></div>
      <div className="container">
        <div className="jobs-grid">
          {items.map((job) => (
            <article className="job-card" key={job.id}>
              <h3><Link href={`/jobs/${job.id}`}>{job.title}</Link></h3>
              <div className="text-muted">{job.company_name}</div>
              <div className="job-meta"><span>{job.location}</span><span>{job.job_type}</span></div>
              <div className="job-card-footer">
                <span className="salary">{formatSalary(job.salary_min, job.salary_max)}</span>
                <button className="btn btn-outline btn-sm" onClick={() => remove(job.id)}>Remove</button>
              </div>
            </article>
          ))}
        </div>
        {items.length === 0 && <div className="empty">No saved jobs. <Link href="/jobs">Browse jobs</Link></div>}
      </div>
    </>
  );
}
