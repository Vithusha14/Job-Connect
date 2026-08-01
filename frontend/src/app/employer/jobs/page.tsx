'use client';

import Link from 'next/link';
import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { apiFetch, formatDate } from '@/lib/api';
import { useAuth } from '@/context/AuthContext';

export default function EmployerJobsPage() {
  const { user, loading } = useAuth();
  const router = useRouter();
  const [jobs, setJobs] = useState<any[]>([]);

  const load = () => apiFetch<{ jobs: any[] }>('employer/jobs.php').then((d) => setJobs(d.jobs));

  useEffect(() => {
    if (loading) return;
    if (!user || user.role !== 'employer') router.replace('/login');
    else load();
  }, [user, loading, router]);

  const act = async (jobId: number, action: string) => {
    if (!confirm(`${action} this job?`)) return;
    await apiFetch('employer/jobs.php', {
      method: 'POST',
      body: { action, job_id: jobId } as unknown as BodyInit,
    });
    load();
  };

  return (
    <>
      <div className="page-header"><div className="container"><h1>Manage Jobs</h1></div></div>
      <div className="container">
        <div className="content-panel">
          <div style={{ marginBottom: '1rem' }}>
            <Link href="/employer/post-job" className="btn btn-primary btn-sm">New Job</Link>
          </div>
          <table className="data-table">
            <thead><tr><th>Title</th><th>Applicants</th><th>Deadline</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
              {jobs.map((j) => (
                <tr key={j.id}>
                  <td>{j.title}<div className="text-muted" style={{ fontSize: '0.8rem' }}>{j.location}</div></td>
                  <td>{j.applicant_count}</td>
                  <td>{formatDate(j.deadline)}</td>
                  <td><span className="badge badge-info">{j.status}</span></td>
                  <td style={{ display: 'flex', gap: 6, flexWrap: 'wrap' }}>
                    <Link href={`/employer/applicants/${j.id}`} className="btn btn-ghost btn-sm">Applicants</Link>
                    {j.status !== 'closed' ? (
                      <button className="btn btn-outline btn-sm" onClick={() => act(j.id, 'close')}>Close</button>
                    ) : (
                      <button className="btn btn-ghost btn-sm" onClick={() => act(j.id, 'reopen')}>Reopen</button>
                    )}
                    <button className="btn btn-danger btn-sm" onClick={() => act(j.id, 'delete')}>Delete</button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </>
  );
}
