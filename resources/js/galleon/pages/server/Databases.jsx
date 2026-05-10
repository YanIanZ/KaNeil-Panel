import React, { useState } from 'react';
import { Link } from '@inertiajs/react';
import { Layout, Card } from '../../components/index.js';

export default function Databases({ server }) {
  const [showCreate, setShowCreate] = useState(false);
  const dbs = server.databases || [];

  return (
    <Layout server={server} crumbs={[{ label: server.name, href: '/server/' + server.uuid }, { label: 'Databases' }]}
      actions={<button className="btn primary sm" onClick={() => setShowCreate(true)}>New Database</button>}>
      <div className="page-hd"><h1>Databases</h1></div>

      {showCreate && (
        <Card style={{ marginBottom: 'var(--space-4)' }}>
          <div style={{ fontWeight: 600, marginBottom: 'var(--space-3)' }}>Create Database</div>
          <div style={{ color: 'var(--ink-muted)', fontSize: 13 }}>Database creation requires Wings API integration (v2.0-EX stub).</div>
          <button className="btn ghost sm" onClick={() => setShowCreate(false)} style={{ marginTop: 'var(--space-2)' }}>Cancel</button>
        </Card>
      )}

      <Card>
        <table className="table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Username</th>
              <th>Host</th>
              <th>Port</th>
              <th style={{ width: 80 }}>Actions</th>
            </tr>
          </thead>
          <tbody>
            {dbs.length === 0 ? (
              <tr><td colSpan={5} style={{ textAlign: 'center', color: 'var(--ink-muted)', padding: 'var(--space-5)' }}>No databases.</td></tr>
            ) : dbs.map(db => (
              <tr key={db.id}>
                <td style={{ fontWeight: 500 }}>{db.database}</td>
                <td style={{ fontFamily: "'JetBrains Mono',monospace", fontSize: 12 }}>{db.username}</td>
                <td style={{ fontFamily: "'JetBrains Mono',monospace", fontSize: 12 }}>{db.host?.host || '—'}</td>
                <td>{db.host?.port || '—'}</td>
                <td><button className="btn ghost sm">Rotate</button></td>
              </tr>
            ))}
          </tbody>
        </table>
      </Card>
    </Layout>
  );
}