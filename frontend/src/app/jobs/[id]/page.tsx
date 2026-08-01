'use client';

import Link from 'next/link';
import { FormEvent, useEffect, useState } from 'react';
import { useParams } from 'next/navigation';
import { apiFetch, formatDate, formatSalary } from '@/lib/api';
import { useAuth } from '@/context/AuthContext';

type Detail = {
  job: Record<string, string | number>;
  has_applied: boolean;
  is_bookmarked: boolean;
};

export default function JobDetailPage() {
  const { id } = useParams<{ id: string }>();
  const { user } = useAuth();
  const [data, setData] = useState<Detail | null>(null);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');

  const load = () => {
    apiFetch<Detail>(`jobs/detail.php?id=${id}`)
      .then(setData)
      .catch((e) => setError(e.message));
  };

  useEffect(() => {
    load();
  }, [id]);

  const apply = async (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setError('');
    setMessage('');
    const fd = new FormData(e.currentTarget);
    try {
      const res = await apiFetch<{ message: string }>('jobs/apply.php', {
        method: 'POST',
        body: {
          job_id: Number(id),
          cover_letter: String(fd.get('cover_letter') || ''),
        } as unknown as BodyInit,
      });
      setMessage(res.message);
      load();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Apply failed');
    }
  };

  const bookmark = async () => {
    try {
      const res = await apiFetch<{ message: string }>('jobs/bookmark.php', {
        method: 'POST',
        body: { job_id: Number(id) } as unknown as BodyInit,
      });
      setMessage(res.message);
      load();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Bookmark failed');
    }
  };

  if (error && !data) {
    return <div className="container" style={{ padding: '2rem 0' }}><div className="alert alert-error">{error}</div></div>;
  }
  if (!data) return <div className="container" style={{ padding: '2rem 0' }}>Loading…</div>;

  const job = data.job;

  return (
    <>
      <div className="page-header">
        <div className="container">
          <h1>{String(job.title)}</h1>
          <p>{String(job.company_name)} · {String(job.location)}</p>
        </div>
      </div>
      <div className="container job-detail">
        <div className="content-panel">
          {message && <div className="alert alert-success">{message}</div>}
          {error && <div className="alert alert-error">{error}</div>}
          <div className="job-meta">
            <span>{String(job.location)}</span>
            <span>{String(job.job_type)}</span>
            <span>{String(job.category_name)}</span>
          </div>
          <div className="detail-section">
            <h3>Description</h3>
            <p style={{ whiteSpace: 'pre-wrap' }}>{String(job.description)}</p>
          </div>
          <div className="detail-section">
            <h3>Requirements</h3>
            <p style={{ whiteSpace: 'pre-wrap' }}>{String(job.requirements)}</p>
          </div>

          {user?.role === 'candidate' && !data.has_applied && (
            <div className="detail-section">
              <h3>Apply</h3>
              <form onSubmit={apply}>
                <div className="form-group">
                  <label>Cover letter (optional)</label>
                  <textarea name="cover_letter" className="form-control" rows={4} />
                </div>
                <button className="btn btn-primary">Apply Now</button>
              </form>
            </div>
          )}
          {data.has_applied && <div className="alert alert-success mt-2">You already applied. <Link href="/candidate/applications">Track status</Link></div>}
          {!user && <div className="alert alert-info mt-2"><Link href="/login">Login</Link> as a candidate to apply.</div>}
        </div>

        <aside className="content-panel">
          <h3 style={{ marginBottom: '0.75rem', color: 'var(--primary-900)' }}>Overview</h3>
          <div className="widget-row"><span>Salary</span><strong>{formatSalary(job.salary_min as number, job.salary_max as number)}</strong></div>
          <div className="widget-row"><span>Type</span><strong>{String(job.job_type)}</strong></div>
          <div className="widget-row"><span>Deadline</span><strong>{formatDate(String(job.deadline))}</strong></div>
          {user?.role === 'candidate' && (
            <button className="btn btn-outline btn-block mt-2" onClick={bookmark}>
              {data.is_bookmarked ? 'Saved' : 'Save Job'}
            </button>
          )}
          <Link href="/jobs" className="btn btn-ghost btn-block mt-2">Back to Jobs</Link>
        </aside>
      </div>
    </>
  );
}
