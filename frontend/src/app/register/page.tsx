'use client';

import Link from 'next/link';
import { FormEvent, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/context/AuthContext';

export default function RegisterPage() {
  const { register } = useAuth();
  const router = useRouter();
  const [role, setRole] = useState<'candidate' | 'employer'>('candidate');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const onSubmit = async (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setError('');
    setLoading(true);
    const fd = new FormData(e.currentTarget);
    const payload: Record<string, string> = {
      role,
      email: String(fd.get('email') || ''),
      password: String(fd.get('password') || ''),
      confirm_password: String(fd.get('confirm_password') || ''),
      phone: String(fd.get('phone') || ''),
      full_name: String(fd.get('full_name') || ''),
      company_name: String(fd.get('company_name') || ''),
      industry: String(fd.get('industry') || ''),
    };
    try {
      const u = await register(payload);
      router.push(u.role === 'employer' ? '/employer' : '/candidate');
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Registration failed');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="auth-page">
      <div className="auth-card wide">
        <h1>Create an account</h1>
        <p className="subtitle">Join as a job seeker or employer</p>
        {error && <div className="alert alert-error">{error}</div>}
        <div className="role-tabs">
          <button type="button" className={`role-tab ${role === 'candidate' ? 'active' : ''}`} onClick={() => setRole('candidate')}>
            Job Seeker
          </button>
          <button type="button" className={`role-tab ${role === 'employer' ? 'active' : ''}`} onClick={() => setRole('employer')}>
            Employer
          </button>
        </div>
        <form onSubmit={onSubmit}>
          <div className="form-group">
            <label>Email</label>
            <input name="email" type="email" className="form-control" required />
          </div>
          <div className="form-row">
            <div className="form-group">
              <label>Password</label>
              <input name="password" type="password" className="form-control" minLength={6} required />
            </div>
            <div className="form-group">
              <label>Confirm Password</label>
              <input name="confirm_password" type="password" className="form-control" required />
            </div>
          </div>
          {role === 'candidate' ? (
            <div className="form-row">
              <div className="form-group">
                <label>Full Name</label>
                <input name="full_name" className="form-control" required />
              </div>
              <div className="form-group">
                <label>Phone</label>
                <input name="phone" className="form-control" />
              </div>
            </div>
          ) : (
            <div className="form-row">
              <div className="form-group">
                <label>Company Name</label>
                <input name="company_name" className="form-control" required />
              </div>
              <div className="form-group">
                <label>Industry</label>
                <input name="industry" className="form-control" />
              </div>
            </div>
          )}
          <button className="btn btn-primary btn-block" disabled={loading}>
            {loading ? 'Creating…' : 'Create Account'}
          </button>
        </form>
        <p className="text-center mt-2 text-muted">
          Already registered? <Link href="/login">Login</Link>
        </p>
      </div>
    </div>
  );
}
