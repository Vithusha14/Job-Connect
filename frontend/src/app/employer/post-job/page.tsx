'use client';

import Link from 'next/link';
import { FormEvent, useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { apiFetch } from '@/lib/api';
import { useAuth } from '@/context/AuthContext';

export default function PostJobPage() {
  const { user, loading } = useAuth();
  const router = useRouter();
  const [categories, setCategories] = useState<any[]>([]);
  const [error, setError] = useState('');
  const [message, setMessage] = useState('');

  useEffect(() => {
    if (loading) return;
    if (!user || user.role !== 'employer') router.replace('/login');
    else apiFetch<{ categories: any[] }>('employer/jobs.php').then((d) => setCategories(d.categories));
  }, [user, loading, router]);

  const onSubmit = async (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setError('');
    const fd = new FormData(e.currentTarget);
    const payload: Record<string, string | number> = { action: 'create' };
    fd.forEach((v, k) => { payload[k] = String(v); });
    try {
      const res = await apiFetch<{ message: string }>('employer/jobs.php', {
        method: 'POST',
        body: payload as unknown as BodyInit,
      });
      setMessage(res.message);
      setTimeout(() => router.push('/employer/jobs'), 800);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed');
    }
  };

  return (
    <>
      <div className="page-header"><div className="container"><h1>Post a New Job</h1></div></div>
      <div className="container">
        <div className="content-panel" style={{ maxWidth: 800, margin: '0 auto' }}>
          {message && <div className="alert alert-success">{message}</div>}
          {error && <div className="alert alert-error">{error}</div>}
          <form onSubmit={onSubmit}>
            <div className="form-group"><label>Title</label><input name="title" className="form-control" required /></div>
            <div className="form-row">
              <div className="form-group">
                <label>Category</label>
                <select name="category_id" className="form-control" required>
                  <option value="">Select</option>
                  {categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                </select>
              </div>
              <div className="form-group">
                <label>Job Type</label>
                <select name="job_type" className="form-control">
                  {['Full-time','Part-time','Contract','Internship','Remote'].map((t) => <option key={t}>{t}</option>)}
                </select>
              </div>
            </div>
            <div className="form-row">
              <div className="form-group"><label>Location</label><input name="location" className="form-control" required /></div>
              <div className="form-group"><label>Vacancies</label><input name="vacancies" type="number" min={1} defaultValue={1} className="form-control" /></div>
            </div>
            <div className="form-row">
              <div className="form-group"><label>Salary Min</label><input name="salary_min" type="number" className="form-control" /></div>
              <div className="form-group"><label>Salary Max</label><input name="salary_max" type="number" className="form-control" /></div>
            </div>
            <div className="form-group"><label>Deadline</label><input name="deadline" type="date" className="form-control" required /></div>
            <div className="form-group"><label>Description</label><textarea name="description" className="form-control" required /></div>
            <div className="form-group"><label>Requirements</label><textarea name="requirements" className="form-control" required /></div>
            <button className="btn btn-primary">Submit for Approval</button>
            <Link href="/employer" className="btn btn-outline" style={{ marginLeft: 8 }}>Cancel</Link>
          </form>
        </div>
      </div>
    </>
  );
}
