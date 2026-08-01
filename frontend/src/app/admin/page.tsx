'use client';

import Link from 'next/link';
import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { apiFetch } from '@/lib/api';
import { useAuth } from '@/context/AuthContext';

export default function AdminDashboard() {
  const { user, loading } = useAuth();
  const router = useRouter();
  const [data, setData] = useState<any>(null);

  useEffect(() => {
    if (loading) return;
    if (!user || user.role !== 'admin') router.replace('/login');
    else apiFetch('admin/dashboard.php').then(setData);
  }, [user, loading, router]);

  if (!data) return <div className="container" style={{ padding: '2rem 0' }}>Loading…</div>;
  const s = data.stats;

  return (
    <>
      <div className="page-header">
        <div className="container">
          <h1>Admin Dashboard</h1>
          <p>Platform overview and moderation tools.</p>
        </div>
      </div>
      <div className="container dashboard-layout">
        <aside className="sidebar">
          <Link href="/admin" className="active">Dashboard</Link>
          <Link href="/admin/users">Manage Users</Link>
          <Link href="/admin/jobs">Manage Jobs</Link>
        </aside>
        <div>
          <div className="stats-grid">
            <div className="stat-card"><strong>{s.totalUsers}</strong><span>Total Users</span></div>
            <div className="stat-card"><strong>{s.totalCandidates}</strong><span>Candidates</span></div>
            <div className="stat-card"><strong>{s.totalEmployers}</strong><span>Employers</span></div>
            <div className="stat-card"><strong>{s.totalJobs}</strong><span>Jobs</span></div>
            <div className="stat-card"><strong>{s.approvedJobs}</strong><span>Approved</span></div>
            <div className="stat-card"><strong>{s.pendingJobs}</strong><span>Pending Approval</span></div>
            <div className="stat-card"><strong>{s.totalApps}</strong><span>Applications</span></div>
            <div className="stat-card"><strong>{s.pendingApps}</strong><span>Pending Reviews</span></div>
          </div>
        </div>
      </div>
    </>
  );
}
