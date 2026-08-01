'use client';

import Link from 'next/link';
import { usePathname, useRouter } from 'next/navigation';
import { useAuth } from '@/context/AuthContext';

export default function Navbar() {
  const { user, logout } = useAuth();
  const pathname = usePathname();
  const router = useRouter();

  const isActive = (href: string) => (pathname === href ? 'active' : '');

  return (
    <header className="site-header">
      <div className="container header-inner">
        <Link href="/" className="logo">
          <span className="logo-mark">JC</span>
          <span>JobConnect</span>
        </Link>

        <ul className="nav-links">
          <li><Link className={isActive('/')} href="/">Home</Link></li>
          <li><Link className={isActive('/jobs')} href="/jobs">Browse Jobs</Link></li>
          {user?.role === 'candidate' && (
            <>
              <li><Link href="/candidate">Dashboard</Link></li>
              <li><Link href="/candidate/applications">Applications</Link></li>
            </>
          )}
          {user?.role === 'employer' && (
            <>
              <li><Link href="/employer">Dashboard</Link></li>
              <li><Link href="/employer/post-job">Post Job</Link></li>
            </>
          )}
          {user?.role === 'admin' && (
            <li><Link href="/admin">Admin Panel</Link></li>
          )}
        </ul>

        <div className="nav-actions">
          {user ? (
            <>
              <span className="text-muted" style={{ fontSize: '0.85rem' }}>{user.display_name}</span>
              <button
                className="btn btn-outline btn-sm"
                onClick={() => {
                  logout();
                  router.push('/login');
                }}
              >
                Logout
              </button>
            </>
          ) : (
            <>
              <Link href="/login" className="btn btn-outline btn-sm">Login</Link>
              <Link href="/register" className="btn btn-primary btn-sm">Register</Link>
            </>
          )}
        </div>
      </div>
    </header>
  );
}
