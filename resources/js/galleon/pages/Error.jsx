import React from 'react';
import { usePage, Link } from '@inertiajs/react';

const PIRATE = {
  403: { title: 'Forbidden waters', msg: "Ye don't have clearance for this route, sailor." },
  404: { title: 'Uncharted territory', msg: "This port doesn't exist on any known map." },
  500: { title: 'Mutiny in the rigging', msg: "Something broke aboard. The ship's surgeon has been notified." },
  503: { title: 'Ship taking on water', msg: "The galleon is overloaded. Try again when the tide turns." },
};

export default function Error() {
  const { status } = usePage().props;
  const code = Number(status) || 500;
  const { title, msg } = PIRATE[code] || { title: 'Lost at sea', msg: "Something went wrong. Navigate back to familiar waters." };

  return (
    <div className="app" style={{ placeItems: 'center', display: 'grid', minHeight: '100vh', background: 'var(--bg)' }}>
      <div style={{ textAlign: 'center', maxWidth: 460, padding: 'var(--space-6)' }}>
        <div style={{ fontFamily: 'var(--font-display)', fontSize: 72, fontWeight: 700, color: 'var(--brass)', lineHeight: 1 }}>{code}</div>
        <h1 style={{ fontFamily: 'var(--font-display)', fontSize: 24, marginTop: 'var(--space-3)', marginBottom: 'var(--space-2)' }}>{title}</h1>
        <p style={{ color: 'var(--ink-mid)', fontSize: 14, lineHeight: 1.6, marginBottom: 'var(--space-5)' }}>{msg}</p>
        <Link href="/" className="btn primary">Return to Fleet</Link>
      </div>
    </div>
  );
}