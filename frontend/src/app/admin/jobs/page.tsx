'use client';

import Link from 'next/link';
import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { apiFetch, formatDate } from '@/lib/api';
import { useAuth } from '@/context/AuthContext';

export default function AdminJobsPage() {
  const { user, loading } = useAuth();
  const router = useRouter();
  const [jobs, setJobs] = useState<any[]>([]);

  const load = () => apiFetch<{ jobs: any[] }>('admin/jobs.php').then((d) => setJobs(d.jobs));

  useEffect(() => {
    if (loading) return;
    if (!user || user.role !== 'admin') router.replace('/login');
    else load();
  }, [user, loading, router]);

  const act = async (jobId: number, action: string) => {
    if (!confirm(`${action} this job?`)) return;
    await apiFetch('admin/jobs.php', {
      method: 'POST',
      body: { job_id: jobId, action } as unknown as BodyInit,
    });
    load();
  };

  return (
    <>
      <div className="page-header"><div className="container"><h1>Manage Jobs</h1></div></div>
      <div className="container">
        <div className="content-panel">
          <Link href="/admin" className="btn btn-ghost btn-sm mb-2">Dashboard</Link>
          <table className="data-table">
            <thead><tr><th>Title</th><th>Company</th><th>Status</th><th>Deadline</th><th>Actions</th></tr></thead>
            <tbody>
              {jobs.map((j) => (
                <tr key={j.id}>
                  <td>{j.title}</td>
                  <td>{j.company_name}</td>
                  <td><span className="badge badge-info">{j.status}</span></td>
                  <td>{formatDate(j.deadline)}</td>
                  <td style={{ display: 'flex', gap: 6, flexWrap: 'wrap' }}>
                    {(j.status === 'pending_approval' || j.status === 'rejected') && (
                      <button className="btn btn-primary btn-sm" onClick={() => act(j.id, 'approve')}>Approve</button>
                    )}
                    {(j.status === 'pending_approval' || j.status === 'approved') && (
                      <button className="btn btn-outline btn-sm" onClick={() => act(j.id, 'reject')}>Reject</button>
                    )}
                    {j.status === 'approved' && (
                      <button className="btn btn-outline btn-sm" onClick={() => act(j.id, 'close')}>Close</button>
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
