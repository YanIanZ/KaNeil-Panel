import React from 'react';
import { Link } from '@inertiajs/react';

export default function Topbar({ crumbs = [], onMenu, actions }) {
  return (
    <div className="topbar">
      <button className="btn icon ghost sm hamburger-btn" onClick={onMenu} aria-label="Open navigation">☰</button>
      <nav className="breadcrumb">
        {crumbs.map((c, i) => (
          <React.Fragment key={i}>
            {i > 0 && <span className="breadcrumb-sep">›</span>}
            {c.href
              ? <Link href={c.href}>{c.label}</Link>
              : <span className="breadcrumb-current">{c.label}</span>
            }
          </React.Fragment>
        ))}
      </nav>
      {actions && <div style={{ display: 'flex', gap: 'var(--space-2)' }}>{actions}</div>}
    </div>
  );
}