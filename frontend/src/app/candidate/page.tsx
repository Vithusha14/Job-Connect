'use client';

import Link from 'next/link';
import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { apiFetch, formatDate } from '@/lib/api';
import { useAuth } from '@/context/AuthContext';

function StatusBadge({ status }: { status: string }) {
  const map: Record<string, string> = {
    pending: 'badge-warning',
    shortlisted: 'badge-info',
    rejected: 'badge-danger',
    selected: 'badge-success',
    approved: 'badge-success',
    pending_approval: 'badge-warning',
    closed: 'badge-secondary',
  };
  return <span className={`badge ${map[status] || 'badge-secondary'}`}>{status.replace('_', ' ')}</span>;
}

export default function CandidateDashboard() {
  const { user, loading } = useAuth();
  const router = useRouter();
  const [data, setData] = useState<any>(null);
  const [error, setError] = useState('');

  useEffect(() => {
    if (loading) return;
    if (!user) router.replace('/login');
    else if (user.role !== 'candidate') router.replace('/');
  }, [user, loading, router]);

  useEffect(() => {
    if (user?.role !== 'candidate') return;
    apiFetch('candidate/dashboard.php')
      .then(setData)
      .catch((e) => setError(e.message));
  }, [user]);

  if (loading || !data) {
    return <div className="container" style={{ padding: '2rem 0' }}>{error || 'Loading…'}</div>;
  }

  const s = data.stats;

  return (
    <>
      <div className="page-header">
        <div className="container">
          <h1>Welcome, {user?.display_name}</h1>
          <p>Track applications and discover new roles.</p>
        </div>
      </div>
      <div className="container dashboard-layout">
        <aside className="sidebar">
          <Link href="/candidate" className="active">Dashboard</Link>
          <Link href="/candidate/profile">My Profile</Link>
          <Link href="/jobs">Browse Jobs</Link>
          <Link href="/candidate/applications">Applications</Link>
          <Link href="/candidate/bookmarks">Saved Jobs</Link>
        </aside>
        <div>
          <div className="stats-grid mb-2">
            <div className="stat-card"><strong>{s.totalApps}</strong><span>Applications</span></div>
            <div className="stat-card"><strong>{s.pending}</strong><span>Pending</span></div>
            <div className="stat-card"><strong>{s.shortlisted}</strong><span>Shortlisted</span></div>
            <div className="stat-card"><strong>{s.selected}</strong><span>Selected</span></div>
          </div>
          <div className="content-panel">
            <h2 style={{ marginBottom: '1rem', color: 'var(--primary-900)' }}>Recent Applications</h2>
            <div className="table-wrap">
              <table className="data-table">
                <thead>
                  <tr><th>Job</th><th>Company</th><th>Applied</th><th>Status</th></tr>
                </thead>
                <tbody>
                  {data.recent.map((app: any) => (
                    <tr key={app.id}>
                      <td><Link href={`/jobs/${app.job_id}`}>{app.title}</Link></td>
                      <td>{app.company_name}</td>
                      <td>{formatDate(app.applied_at)}</td>
                      <td><StatusBadge status={app.status} /></td>
                    </tr>
                  ))}
                </tbody>
              </table>
              {data.recent.length === 0 && <div className="empty">No applications yet. <Link href="/jobs">Browse jobs</Link></div>}
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
