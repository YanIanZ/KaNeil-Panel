import React from 'react';
import { Link } from '@inertiajs/react';
import { Layout, Card } from '../../components/index.js';

export default function Startup({ server }) {
  return (
    <Layout server={server} crumbs={[{ label: server.name, href: '/server/' + server.uuid }, { label: 'Startup' }]}>
      <div className="page-hd"><h1>Startup</h1></div>

      <Card style={{ marginBottom: 'var(--space-4)' }}>
        <div style={{ fontWeight: 600, marginBottom: 'var(--space-3)' }}>Startup Command</div>
        <code style={{ display: 'block', padding: 'var(--space-3)', background: 'var(--surface)', borderRadius: 6, fontSize: 13, fontFamily: "'JetBrains Mono',monospace", whiteSpace: 'pre-wrap', wordBreak: 'break-all' }}>
          {server.startup || '—'}
        </code>
      </Card>

      <div className="grid grid-2">
        <Card>
          <div style={{ fontWeight: 600, marginBottom: 'var(--space-3)' }}>Resource Limits</div>
          <table className="table">
            <tbody>
              <tr><td style={{ color: 'var(--ink-muted)' }}>Memory</td><td style={{ fontWeight: 500 }}>{(server.memory || 0).toLocaleString()} MB</td></tr>
              <tr><td style={{ color: 'var(--ink-muted)' }}>Swap</td><td style={{ fontWeight: 500 }}>{(server.swap || 0).toLocaleString()} MB</td></tr>
              <tr><td style={{ color: 'var(--ink-muted)' }}>Disk</td><td style={{ fontWeight: 500 }}>{(server.disk || 0).toLocaleString()} MB</td></tr>
              <tr><td style={{ color: 'var(--ink-muted)' }}>CPU</td><td style={{ fontWeight: 500 }}>{server.cpu || 0}%</td></tr>
              <tr><td style={{ color: 'var(--ink-muted)' }}>I/O</td><td style={{ fontWeight: 500 }}>{server.io || 0}</td></tr>
            </tbody>
          </table>
        </Card>

        <Card>
          <div style={{ fontWeight: 600, marginBottom: 'var(--space-3)' }}>Limits</div>
          <table className="table">
            <tbody>
              <tr><td style={{ color: 'var(--ink-muted)' }}>Databases</td><td style={{ fontWeight: 500 }}>{server.database_limit ?? '∞'}</td></tr>
              <tr><td style={{ color: 'var(--ink-muted)' }}>Allocations</td><td style={{ fontWeight: 500 }}>{server.allocation_limit ?? '∞'}</td></tr>
              <tr><td style={{ color: 'var(--ink-muted)' }}>Backups</td><td style={{ fontWeight: 500 }}>{server.backup_limit ?? '∞'}</td></tr>
              <tr><td style={{ color: 'var(--ink-muted)' }}>OOM Killer</td><td style={{ fontWeight: 500 }}>{server.oom_killer ? 'Enabled' : 'Disabled'}</td></tr>
            </tbody>
          </table>
        </Card>
      </div>
    </Layout>
  );
}