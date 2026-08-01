'use client';

import Link from 'next/link';
import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { apiFetch, formatDate } from '@/lib/api';
import { useAuth } from '@/context/AuthContext';

export default function EmployerDashboard() {
  const { user, loading } = useAuth();
  const router = useRouter();
  const [data, setData] = useState<any>(null);

  useEffect(() => {
    if (loading) return;
    if (!user || user.role !== 'employer') router.replace('/login');
  }, [user, loading, router]);

  useEffect(() => {
    if (user?.role !== 'employer') return;
    apiFetch('employer/dashboard.php').then(setData);
  }, [user]);

  if (!data) return <div className="container" style={{ padding: '2rem 0' }}>Loading…</div>;

  return (
    <>
      <div className="page-header">
        <div className="container">
          <h1>{user?.display_name}</h1>
          <p>Manage job postings and applicants.</p>
        </div>
      </div>
      <div className="container dashboard-layout">
        <aside className="sidebar">
          <Link href="/employer" className="active">Dashboard</Link>
          <Link href="/employer/post-job">Post a Job</Link>
          <Link href="/employer/jobs">Manage Jobs</Link>
        </aside>
        <div>
          <div className="stats-grid mb-2">
            <div className="stat-card"><strong>{data.stats.totalJobs}</strong><span>Jobs Posted</span></div>
            <div className="stat-card"><strong>{data.stats.activeJobs}</strong><span>Active</span></div>
            <div className="stat-card"><strong>{data.stats.pendingJobs}</strong><span>Pending</span></div>
            <div className="stat-card"><strong>{data.stats.totalApplicants}</strong><span>Applicants</span></div>
          </div>
          <div className="content-panel">
            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '1rem' }}>
              <h2 style={{ color: 'var(--primary-900)' }}>Recent Jobs</h2>
              <Link href="/employer/post-job" className="btn btn-primary btn-sm">Post Job</Link>
            </div>
            <table className="data-table">
              <thead><tr><th>Title</th><th>Applicants</th><th>Deadline</th><th>Status</th><th></th></tr></thead>
              <tbody>
                {data.jobs.map((j: any) => (
                  <tr key={j.id}>
                    <td>{j.title}</td>
                    <td>{j.applicant_count}</td>
                    <td>{formatDate(j.deadline)}</td>
                    <td><span className="badge badge-info">{j.status}</span></td>
                    <td><Link href={`/employer/applicants/${j.id}`} className="btn btn-ghost btn-sm">Applicants</Link></td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </>
  );
}
