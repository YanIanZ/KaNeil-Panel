import React from 'react';

export default function Card({ children, framed, style, className = '' }) {
  return (
    <div className={'card' + (framed ? ' framed' : '') + (className ? ' ' + className : '')} style={style}>
      {children}
    </div>
  );
}

export function Stat({ label, value, sub }) {
  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 'var(--space-1)' }}>
      <div style={{ fontSize: 11, color: 'var(--ink-muted)', textTransform: 'uppercase', letterSpacing: '0.08em' }}>{label}</div>
      <div style={{ fontSize: 22, fontWeight: 700, fontFamily: 'var(--font-display)', color: 'var(--brass)', lineHeight: 1.2 }}>{value}</div>
      {sub && <div style={{ fontSize: 11, color: 'var(--ink-muted)' }}>{sub}</div>}
    </div>
  );
}

export function StatBar({ pct, warn = 75, danger = 90 }) {
  const color = pct >= danger ? 'var(--danger)' : pct >= warn ? 'var(--warn)' : 'var(--success)';
  return (
    <div className="stat-bar">
      <div className="stat-bar-fill" style={{ width: pct + '%', background: color }} />
    </div>
  );
}