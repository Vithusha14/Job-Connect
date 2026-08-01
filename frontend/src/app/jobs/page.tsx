'use client';

import { Suspense } from 'react';
import JobsPageInner from './JobsPageInner';

export default function JobsPage() {
  return (
    <Suspense fallback={<div className="container" style={{ padding: '2rem 0' }}>Loading jobs…</div>}>
      <JobsPageInner />
    </Suspense>
  );
}
