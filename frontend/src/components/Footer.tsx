import Link from 'next/link';

export default function Footer() {
  return (
    <footer className="site-footer">
      <div className="container footer-grid">
        <div>
          <div className="logo" style={{ color: '#fff', marginBottom: '0.75rem' }}>
            <span className="logo-mark">JC</span>
            <span>JobConnect</span>
          </div>
          <p>Connecting talented professionals with great companies.</p>
        </div>
        <div>
          <h4>Quick Links</h4>
          <ul>
            <li><Link href="/">Home</Link></li>
            <li><Link href="/jobs">Browse Jobs</Link></li>
            <li><Link href="/login">Login</Link></li>
            <li><Link href="/register">Register</Link></li>
          </ul>
        </div>
        <div>
          <h4>Contact</h4>
          <ul>
            <li>support@jobconnect.com</li>
            <li>Colombo, Sri Lanka</li>
          </ul>
        </div>
      </div>
      <div className="footer-bottom">
        <div className="container">
          © {new Date().getFullYear()} JobConnect · Online Job Recruitment System
        </div>
      </div>
    </footer>
  );
}
