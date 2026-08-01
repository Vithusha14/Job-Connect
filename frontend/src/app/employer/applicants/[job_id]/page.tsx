'use client';

import Link from 'next/link';
import { useEffect, useState } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { apiFetch, formatDate, uploadUrl } from '@/lib/api';
import { useAuth } from '@/context/AuthContext';

export default function ApplicantsPage() {
  const { job_id } = useParams<{ job_id: string }>();
  const { user, loading } = useAuth();
  const router = useRouter();
  const [data, setData] = useState<any>(null);

  const load = () => apiFetch(`employer/applicants.php?job_id=${job_id}`).then(setData);

  useEffect(() => {
    if (loading) return;
    if (!user || user.role !== 'employer') router.replace('/login');
    else load();
  }, [user, loading, router, job_id]);

  const updateStatus = async (applicationId: number, status: string) => {
    await apiFetch(`employer/applicants.php?job_id=${job_id}`, {
      method: 'POST',
      body: { application_id: applicationId, status } as unknown as BodyInit,
    });
    load();
  };

  if (!data) return <div className="container" style={{ padding: '2rem 0' }}>Loading…</div>;

  return (
    <>
      <div className="page-header">
        <div className="container">
          <h1>Applicants — {data.job.title}</h1>
          <p>{data.applicants.length} applications</p>
        </div>
      </div>
      <div className="container">
        <div className="content-panel">
          <Link href="/employer/jobs" className="btn btn-outline btn-sm mb-2">Back</Link>
          <table className="data-table">
            <thead><tr><th>Candidate</th><th>Email</th><th>Applied</th><th>Resume</th><th>Status</th></tr></thead>
            <tbody>
              {data.applicants.map((a: any) => (
                <tr key={a.id}>
                  <td>{a.full_name}</td>
                  <td>{a.email}</td>
                  <td>{formatDate(a.applied_at)}</td>
                  <td>
                    {a.resume ? (
                      <a href={uploadUrl('resumes', a.resume) || '#'} target="_blank" className="btn btn-outline btn-sm">PDF</a>
                    ) : '—'}
                  </td>
                  <td>
                    <select className="form-control" style={{ width: 'auto' }} value={a.status} onChange={(e) => updateStatus(a.id, e.target.value)}>
                      {['pending','shortlisted','rejected','selected'].map((s) => <option key={s} value={s}>{s}</option>)}
                    </select>
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
