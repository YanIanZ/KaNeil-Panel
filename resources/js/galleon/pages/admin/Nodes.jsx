import React from 'react';
import { usePage, Link } from '@inertiajs/react';
import { Layout, Card, StatusTag } from '../components/index.js';

export default function Nodes() {
  const { auth, nodes } = usePage().props;
  if (!auth?.user?.root_admin) return <Layout crumbs={[{ label: 'Admiralty' }, { label: 'Nodes' }]}><div style={{ padding: 'var(--space-6)', textAlign: 'center', color: 'var(--danger)' }}>Access denied.</div></Layout>;

  return (
    <Layout crumbs={[{ label: 'Admiralty' }, { label: 'Nodes' }]}
      actions={<button className="btn primary sm">Add Node</button>}>
      <div className="page-hd"><h1>Nodes</h1></div>
      <Card>
        <table className="table">
          <thead>
            <tr>
              <th>Name</th>
              <th>FQDN</th>
              <th>Memory</th>
              <th>Disk</th>
              <th>Servers</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            {(nodes || []).length === 0 ? (
              <tr><td colSpan={6} style={{ textAlign: 'center', color: 'var(--ink-muted)', padding: 'var(--space-5)' }}>No nodes.</td></tr>
            ) : (nodes || []).map(n => (
              <tr key={n.id}>
                <td style={{ fontWeight: 500 }}>{n.name}</td>
                <td style={{ fontFamily: "'JetBrains Mono',monospace", fontSize: 12 }}>{n.fqdn}</td>
                <td>{(n.memory || 0).toLocaleString()} MB</td>
                <td>{(n.disk || 0).toLocaleString()} MB</td>
                <td>{n.servers_count ?? 0}</td>
                <td><StatusTag condition={n.condition || 'offline'} label={n.condition_label || n.condition || 'Offline'} /></td>
              </tr>
            ))}
          </tbody>
        </table>
      </Card>
    </Layout>
  );
}