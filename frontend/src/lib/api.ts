export function apiUrl(path: string): string {
  const base = process.env.NEXT_PUBLIC_API_BASE || 'http://localhost:8000';
  return `${base}/api/${path.replace(/^\//, '')}`;
}

export function uploadUrl(folder: string, file?: string | null): string | null {
  if (!file) return null;
  // Uploads still served by classic PHP/Apache or shared volume
  const uploadBase = process.env.NEXT_PUBLIC_UPLOAD_BASE || 'http://localhost:8080';
  return `${uploadBase}/uploads/${folder}/${file}`;
}

export type User = {
  id: number;
  email: string;
  role: 'candidate' | 'employer' | 'admin';
  status: string;
  display_name: string;
  candidate_id?: number;
  employer_id?: number;
  profile?: Record<string, unknown>;
};

function getToken(): string | null {
  if (typeof window === 'undefined') return null;
  return localStorage.getItem('jc_token');
}

export function setAuth(token: string, user: User) {
  localStorage.setItem('jc_token', token);
  localStorage.setItem('jc_user', JSON.stringify(user));
}

export function clearAuth() {
  localStorage.removeItem('jc_token');
  localStorage.removeItem('jc_user');
}

export function getStoredUser(): User | null {
  if (typeof window === 'undefined') return null;
  const raw = localStorage.getItem('jc_user');
  if (!raw) return null;
  try {
    return JSON.parse(raw) as User;
  } catch {
    return null;
  }
}

type ApiOptions = Omit<RequestInit, 'body'> & {
  body?: BodyInit | Record<string, unknown> | null;
  formData?: FormData;
};

export async function apiFetch<T = unknown>(path: string, options: ApiOptions = {}): Promise<T> {
  const headers = new Headers(options.headers || {});
  const token = getToken();
  if (token) headers.set('Authorization', `Bearer ${token}`);

  let body: BodyInit | undefined;
  if (options.formData) {
    body = options.formData;
  } else if (options.body && typeof options.body === 'object' && !(options.body instanceof FormData) && !(options.body instanceof Blob)) {
    headers.set('Content-Type', 'application/json');
    body = JSON.stringify(options.body);
  } else if (options.body != null) {
    body = options.body as BodyInit;
  }

  const res = await fetch(apiUrl(path), {
    method: options.method || 'GET',
    headers,
    body,
  });

  const data = await res.json().catch(() => ({}));
  if (!res.ok || data.ok === false) {
    throw new Error(data.error || `Request failed (${res.status})`);
  }
  return data as T;
}

export function formatSalary(min?: number | string | null, max?: number | string | null): string {
  const a = min != null && min !== '' ? Number(min) : null;
  const b = max != null && max !== '' ? Number(max) : null;
  // Numeric ranges only — currency depends on job location (LKR monthly / GBP annual in seed data)
  if (a == null && b == null) return 'Not disclosed';
  if (a != null && b != null) return `${a.toLocaleString()} – ${b.toLocaleString()}`;
  if (a != null) return `From ${a.toLocaleString()}`;
  return `Up to ${Number(b).toLocaleString()}`;
}

export function formatDate(value?: string | null): string {
  if (!value) return '—';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return value;
  return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}
