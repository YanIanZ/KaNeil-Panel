import React, { useState } from 'react';
import { Link } from '@inertiajs/react';
import { Layout, Card } from '../../components/index.js';

export default function Backups({ server }) {
  const [showCreate, setShowCreate] = useState(false);
  const backups = server.backups || [];

  return (
    <Layout server={server} crumbs={[{ label: server.name, href: '/server/' + server.uuid }, { label: 'Backups' }]}
      actions={<button className="btn primary sm" onClick={() => setShowCreate(true)}>Create Backup</button>}>
      <div className="page-hd"><h1>Backups</h1></div>

      {showCreate && (
        <Card style={{ marginBottom: 'var(--space-4)' }}>
          <div style={{ fontWeight: 600, marginBottom: 'var(--space-3)' }}>Create Backup</div>
          <div style={{ color: 'var(--ink-muted)', fontSize: 13 }}>Backup creation requires Wings API integration (v2.0-EX stub).</div>
          <button className="btn ghost sm" onClick={() => setShowCreate(false)} style={{ marginTop: 'var(--space-2)' }}>Cancel</button>
        </Card>
      )}

      <Card>
        <table className="table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Size</th>
              <th>Status</th>
              <th>Created</th>
              <th style={{ width: 120 }}>Actions</th>
            </tr>
          </thead>
          <tbody>
            {backups.length === 0 ? (
              <tr><td colSpan={5} style={{ textAlign: 'center', color: 'var(--ink-muted)', padding: 'var(--space-5)' }}>No backups.</td></tr>
            ) : backups.map(b => (
              <tr key={b.uuid}>
                <td style={{ fontWeight: 500 }}>{b.name}</td>
                <td>{b.bytes ? (b.bytes / 1048576).toFixed(1) + ' MB' : '—'}</td>
                <td>{b.is_successful ? '✓ Successful' : '✗ Failed'}</td>
                <td style={{ fontSize: 12, color: 'var(--ink-muted)' }}>{b.created_at}</td>
                <td>
                  <div style={{ display: 'flex', gap: 'var(--space-1)' }}>
                    <button className="btn ghost sm">Restore</button>
                    <button className="btn ghost sm danger">Delete</button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </Card>
    </Layout>
  );
}