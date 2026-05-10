import React from 'react';
import { usePage, Link } from '@inertiajs/react';
import { Layout, Card } from '../components/index.js';

export default function Maps() {
  const { auth, maps } = usePage().props;
  if (!auth?.user?.root_admin) return <Layout crumbs={[{ label: 'Admiralty' }, { label: 'Maps' }]}><div style={{ padding: 'var(--space-6)', textAlign: 'center', color: 'var(--danger)' }}>Access denied.</div></Layout>;

  return (
    <Layout crumbs={[{ label: 'Admiralty' }, { label: 'Maps' }]}
      actions={<Link href="/admin/maps/import" className="btn primary sm">Import Map</Link>}>
      <div className="page-hd"><h1>Maps</h1></div>
      <Card>
        <table className="table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Docker Images</th>
              <th>Servers</th>
            </tr>
          </thead>
          <tbody>
            {(maps || []).length === 0 ? (
              <tr><td colSpan={3} style={{ textAlign: 'center', color: 'var(--ink-muted)', padding: 'var(--space-5)' }}>No maps.</td></tr>
            ) : (maps || []).map(m => (
              <tr key={m.id}>
                <td style={{ fontWeight: 500 }}>{m.name}</td>
                <td style={{ fontFamily: "'JetBrains Mono',monospace", fontSize: 12, maxWidth: 300, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                  {typeof m.docker_images === 'string' ? m.docker_images : Array.isArray(m.docker_images) ? m.docker_images.join(', ') : '—'}
                </td>
                <td>{m.servers_count ?? 0}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </Card>
    </Layout>
  );
}