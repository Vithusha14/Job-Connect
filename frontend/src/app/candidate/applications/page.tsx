'use client';

import Link from 'next/link';
import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { apiFetch, formatDate } from '@/lib/api';
import { useAuth } from '@/context/AuthContext';

export default function CandidateApplications() {
  const { user, loading } = useAuth();
  const router = useRouter();
  const [apps, setApps] = useState<any[]>([]);

  useEffect(() => {
    if (loading) return;
    if (!user || user.role !== 'candidate') router.replace('/login');
  }, [user, loading, router]);

  useEffect(() => {
    if (user?.role !== 'candidate') return;
    apiFetch<{ applications: any[] }>('candidate/applications.php')
      .then((d) => setApps(d.applications));
  }, [user]);

  return (
    <>
      <div className="page-header"><div className="container"><h1>My Applications</h1></div></div>
      <div className="container dashboard-layout">
        <aside className="sidebar">
          <Link href="/candidate">Dashboard</Link>
          <Link href="/candidate/applications" className="active">Applications</Link>
          <Link href="/candidate/bookmarks">Saved Jobs</Link>
          <Link href="/candidate/profile">Profile</Link>
        </aside>
        <div className="content-panel">
          <table className="data-table">
            <thead><tr><th>Job</th><th>Company</th><th>Applied</th><th>Status</th></tr></thead>
            <tbody>
              {apps.map((a) => (
                <tr key={a.id}>
                  <td><Link href={`/jobs/${a.job_id}`}>{a.title}</Link></td>
                  <td>{a.company_name}</td>
                  <td>{formatDate(a.applied_at)}</td>
                  <td><span className="badge badge-info">{a.status}</span></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </>
  );
}
