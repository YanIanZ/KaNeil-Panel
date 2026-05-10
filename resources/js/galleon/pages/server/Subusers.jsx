import React from 'react';
import { Link } from '@inertiajs/react';
import { Layout, Card } from '../../components/index.js';

const PERMISSIONS = [
  'console.read', 'console.send', 'console.queue',
  'backup.read', 'backup.create', 'backup.restore', 'backup.delete',
  'allocation.read', 'allocation.create', 'allocation.delete',
  'database.read', 'database.create', 'database.delete', 'database.rotate',
  'schedule.read', 'schedule.create', 'schedule.update', 'schedule.delete',
  'file.read', 'file.create', 'file.update', 'file.delete', 'file.archive', 'file.sftp',
  'startup.read', 'startup.update',
  'subuser.read', 'subuser.create', 'subuser.update', 'subuser.delete',
  'settings.reinstall', 'settings.rename',
];

export default function Subusers({ server }) {
  const subusers = server.subusers || [];

  return (
    <Layout server={server} crumbs={[{ label: server.name, href: '/server/' + server.uuid }, { label: 'Crew' }]}
      actions={<button className="btn primary sm">Invite Crew</button>}>
      <div className="page-hd"><h1>Crew</h1></div>

      <Card>
        <table className="table">
          <thead>
            <tr>
              <th>User</th>
              <th>Email</th>
              <th style={{ width: 80 }}>Actions</th>
            </tr>
          </thead>
          <tbody>
            {subusers.length === 0 ? (
              <tr><td colSpan={3} style={{ textAlign: 'center', color: 'var(--ink-muted)', padding: 'var(--space-5)' }}>No crew members.</td></tr>
            ) : subusers.map(s => (
              <tr key={s.id}>
                <td style={{ fontWeight: 500 }}>{s.user?.username || '—'}</td>
                <td style={{ fontSize: 13 }}>{s.user?.email || '—'}</td>
                <td>
                  <div style={{ display: 'flex', gap: 'var(--space-1)' }}>
                    <button className="btn ghost sm">Edit</button>
                    <button className="btn ghost sm danger">Remove</button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </Card>

      {subusers.length > 0 && (
        <Card style={{ marginTop: 'var(--space-4)' }}>
          <div style={{ fontWeight: 600, marginBottom: 'var(--space-3)' }}>Permission Groups</div>
          <div style={{ fontSize: 12, color: 'var(--ink-muted)' }}>
            Fine-grained permission editing requires Wings API integration (v2.0-EX stub).
          </div>
        </Card>
      )}
    </Layout>
  );
}