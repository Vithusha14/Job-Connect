'use client';

import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import { User, apiFetch, clearAuth, getStoredUser, setAuth } from '@/lib/api';

type AuthContextValue = {
  user: User | null;
  loading: boolean;
  login: (email: string, password: string) => Promise<User>;
  register: (payload: Record<string, string>) => Promise<User>;
  logout: () => void;
  refresh: () => Promise<void>;
};

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  const refresh = useCallback(async () => {
    const stored = getStoredUser();
    if (!stored) {
      setUser(null);
      setLoading(false);
      return;
    }
    try {
      const data = await apiFetch<{ user: User }>('auth/me.php');
      setUser(data.user);
      localStorage.setItem('jc_user', JSON.stringify(data.user));
    } catch {
      clearAuth();
      setUser(null);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    refresh();
  }, [refresh]);

  const login = useCallback(async (email: string, password: string) => {
    const data = await apiFetch<{ token: string; user: User }>('auth/login.php', {
      method: 'POST',
      body: { email, password } as unknown as BodyInit,
    });
    setAuth(data.token, data.user);
    setUser(data.user);
    return data.user;
  }, []);

  const register = useCallback(async (payload: Record<string, string>) => {
    const data = await apiFetch<{ token: string; user: User }>('auth/register.php', {
      method: 'POST',
      body: payload as unknown as BodyInit,
    });
    setAuth(data.token, data.user);
    setUser(data.user);
    return data.user;
  }, []);

  const logout = useCallback(() => {
    clearAuth();
    setUser(null);
  }, []);

  const value = useMemo(
    () => ({ user, loading, login, register, logout, refresh }),
    [user, loading, login, register, logout, refresh]
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used within AuthProvider');
  return ctx;
}
