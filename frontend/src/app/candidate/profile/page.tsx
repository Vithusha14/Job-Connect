'use client';

import Link from 'next/link';
import { FormEvent, useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { apiFetch, uploadUrl } from '@/lib/api';
import { useAuth } from '@/context/AuthContext';

export default function CandidateProfile() {
  const { user, loading, refresh } = useAuth();
  const router = useRouter();
  const [profile, setProfile] = useState<any>(null);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');

  useEffect(() => {
    if (loading) return;
    if (!user || user.role !== 'candidate') router.replace('/login');
  }, [user, loading, router]);

  useEffect(() => {
    if (user?.role !== 'candidate') return;
    apiFetch<{ profile: any }>('candidate/profile.php').then((d) => setProfile(d.profile));
  }, [user]);

  const onSubmit = async (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setError('');
    setMessage('');
    const fd = new FormData(e.currentTarget);
    try {
      const res = await apiFetch<{ message: string; profile: any }>('candidate/profile.php', {
        method: 'POST',
        formData: fd,
      });
      setProfile(res.profile);
      setMessage(res.message);
      await refresh();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Update failed');
    }
  };

  if (!profile) return <div className="container" style={{ padding: '2rem 0' }}>Loading…</div>;

  return (
    <>
      <div className="page-header"><div className="container"><h1>My Profile</h1></div></div>
      <div className="container">
        <div className="content-panel" style={{ maxWidth: 800, margin: '0 auto' }}>
          {message && <div className="alert alert-success">{message}</div>}
          {error && <div className="alert alert-error">{error}</div>}
          {profile.resume && (
            <p className="mb-2"><a href={uploadUrl('resumes', profile.resume) || '#'} target="_blank">View current resume</a></p>
          )}
          <form onSubmit={onSubmit}>
            <div className="form-row">
              <div className="form-group"><label>Full Name</label><input name="full_name" className="form-control" defaultValue={profile.full_name || ''} required /></div>
              <div className="form-group"><label>Phone</label><input name="phone" className="form-control" defaultValue={profile.phone || ''} /></div>
            </div>
            <div className="form-group"><label>Location</label><input name="location" className="form-control" defaultValue={profile.location || ''} /></div>
            <div className="form-group"><label>Skills</label><input name="skills" className="form-control" defaultValue={profile.skills || ''} /></div>
            <div className="form-group"><label>Education</label><textarea name="education" className="form-control" defaultValue={profile.education || ''} /></div>
            <div className="form-group"><label>Experience</label><textarea name="experience" className="form-control" defaultValue={profile.experience || ''} /></div>
            <div className="form-group"><label>Bio</label><textarea name="bio" className="form-control" defaultValue={profile.bio || ''} /></div>
            <div className="form-row">
              <div className="form-group"><label>Photo</label><input name="photo" type="file" accept="image/*" className="form-control" /></div>
              <div className="form-group"><label>Resume (PDF)</label><input name="resume" type="file" accept="application/pdf" className="form-control" /></div>
            </div>
            <button className="btn btn-primary">Save Profile</button>
            <Link href="/candidate" className="btn btn-outline" style={{ marginLeft: 8 }}>Back</Link>
          </form>
        </div>
      </div>
    </>
  );
}
