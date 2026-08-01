'use client';

import Link from 'next/link';
import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { apiFetch, formatDate } from '@/lib/api';
import { useAuth } from '@/context/AuthContext';

export default function AdminUsersPage() {
  const { user, loading } = useAuth();
  const router = useRouter();
  const [tab, setTab] = useState<'candidates' | 'employers'>('candidates');
  const [data, setData] = useState<any>({ candidates: [], employers: [] });

  const load = () => apiFetch('admin/users.php').then(setData);

  useEffect(() => {
    if (loading) return;
    if (!user || user.role !== 'admin') router.replace('/login');
    else load();
  }, [user, loading, router]);

  const act = async (userId: number, action: string) => {
    if (!confirm(`${action} this user?`)) return;
    await apiFetch('admin/users.php', {
      method: 'POST',
      body: { user_id: userId, action } as unknown as BodyInit,
    });
    load();
  };

  const rows = tab === 'candidates' ? data.candidates : data.employers;

  return (
    <>
      <div className="page-header"><div className="container"><h1>Manage Users</h1></div></div>
      <div className="container">
        <div className="content-panel">
          <div style={{ display: 'flex', gap: 8, marginBottom: 16 }}>
            <button className={`btn btn-sm ${tab === 'candidates' ? 'btn-primary' : 'btn-outline'}`} onClick={() => setTab('candidates')}>Candidates</button>
            <button className={`btn btn-sm ${tab === 'employers' ? 'btn-primary' : 'btn-outline'}`} onClick={() => setTab('employers')}>Employers</button>
            <Link href="/admin" className="btn btn-ghost btn-sm">Dashboard</Link>
          </div>
          <table className="data-table">
            <thead>
              <tr>
                <th>{tab === 'candidates' ? 'Name' : 'Company'}</th>
                <th>Email</th>
                <th>Status</th>
                <th>Joined</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((r: any) => (
                <tr key={r.user_id}>
                  <td>{tab === 'candidates' ? r.full_name : r.company_name}</td>
                  <td>{r.email}</td>
                  <td><span className="badge badge-info">{r.status}</span></td>
                  <td>{formatDate(r.created_at)}</td>
                  <td style={{ display: 'flex', gap: 6 }}>
                    {r.status === 'active' ? (
                      <button className="btn btn-outline btn-sm" onClick={() => act(r.user_id, 'block')}>Block</button>
                    ) : (
                      <button className="btn btn-ghost btn-sm" onClick={() => act(r.user_id, 'unblock')}>Unblock</button>
                    )}
                    <button className="btn btn-danger btn-sm" onClick={() => act(r.user_id, 'delete')}>Delete</button>
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
