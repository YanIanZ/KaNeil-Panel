import React, { useState } from 'react';
import { Link } from '@inertiajs/react';
import { Layout, Card } from '../../components/index.js';

export default function Files({ server }) {
  const [path, setPath] = useState('/');

  return (
    <Layout server={server} crumbs={[{ label: server.name, href: '/server/' + server.uuid }, { label: 'Files' }]}>
      <div className="page-hd"><h1>Files</h1></div>

      <Card style={{ marginBottom: 'var(--space-4)' }}>
        <div style={{ display: 'flex', gap: 'var(--space-2)', alignItems: 'center', marginBottom: 'var(--space-3)' }}>
          <span style={{ fontSize: 12, color: 'var(--ink-muted)' }}>Path:</span>
          <input className="input" value={path} onChange={e => setPath(e.target.value)} style={{ flex: 1 }} />
        </div>
        <div style={{ display: 'flex', gap: 'var(--space-2)', marginBottom: 'var(--space-3)' }}>
          <button className="btn primary sm">Upload</button>
          <button className="btn ghost sm">New File</button>
          <button className="btn ghost sm">New Folder</button>
        </div>

        <table className="table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Size</th>
              <th>Modified</th>
              <th style={{ width: 100 }}>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td colSpan={4} style={{ textAlign: 'center', color: 'var(--ink-muted)', padding: 'var(--space-6)' }}>
                File browser requires Wings API integration (v2.0-EX stub).
              </td>
            </tr>
          </tbody>
        </table>
      </Card>
    </Layout>
  );
}