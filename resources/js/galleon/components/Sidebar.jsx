import React from 'react';
import { Link, usePage } from '@inertiajs/react';

const NAV_ADMIN = [
  { label: 'Nodes', route: '/admin/nodes' },
  { label: 'Users', route: '/admin/users' },
  { label: 'Maps', route: '/admin/maps' },
];

const SERVER_TABS = [
  { label: 'Console', sub: 'console' },
  { label: 'Files', sub: 'files' },
  { label: 'Databases', sub: 'databases' },
  { label: 'Backups', sub: 'backups' },
  { label: 'Schedules', sub: 'schedules' },
  { label: 'Allocations', sub: 'network' },
  { label: 'Crew', sub: 'subusers' },
  { label: 'Startup', sub: 'startup' },
];

export default function Sidebar({ server, onClose }) {
  const { url, props } = usePage();
  const isAdmin = props?.auth?.user?.root_admin === true;
  const pathname = url.split('?')[0];

  return (
    <aside className="sidebar">
      <Link href="/" className="brand" onClick={onClose}>
        <div>
          <div className="brand-name">Galleon</div>
          <div className="brand-sub">v2.0-EX · Ship</div>
        </div>
      </Link>

      <nav className="nav">
        <div className="nav-section">Helm</div>
        <Link href="/" className={'nav-item' + (pathname === '/' ? ' active' : '')} onClick={onClose}>
          Fleet
        </Link>

        {server && (
          <>
            <div className="nav-section">{server.name}</div>
            {SERVER_TABS.map(t => {
              const href = '/server/' + server.uuid + '/' + t.sub;
              return (
                <Link key={t.sub} href={href} className={'nav-item' + (pathname.startsWith(href) ? ' active' : '')} onClick={onClose}>
                  {t.label}
                </Link>
              );
            })}
          </>
        )}

        {isAdmin && (
          <>
            <div className="nav-section">Admiralty</div>
            {NAV_ADMIN.map(it => (
              <Link key={it.route} href={it.route} className={'nav-item' + (pathname.startsWith(it.route) ? ' active' : '')} onClick={onClose}>
                {it.label}
              </Link>
            ))}
          </>
        )}
      </nav>

      <div style={{ padding: 'var(--space-3) var(--space-4)', borderTop: '1px solid var(--border)', marginTop: 'auto' }}>
        <Link href={route('galleon.auth.logout')} method="post" as="button" className="btn ghost" style={{ width: '100%', justifyContent: 'center' }}>
          Abandon Ship
        </Link>
      </div>
    </aside>
  );
}