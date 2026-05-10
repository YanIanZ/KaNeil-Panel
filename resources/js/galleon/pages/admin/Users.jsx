import React from 'react';
import { usePage, Link } from '@inertiajs/react';
import { Layout, Card } from '../../components/index.js';

export default function Users() {
  const { auth, users } = usePage().props;
  if (!auth?.user?.root_admin) return <Layout crumbs={[{ label: 'Admiralty' }, { label: 'Users' }]}><div style={{ padding: 'var(--space-6)', textAlign: 'center', color: 'var(--danger)' }}>Access denied.</div></Layout>;

  return (
    <Layout crumbs={[{ label: 'Admiralty' }, { label: 'Users' }]}
      actions={<button className="btn primary sm">Add User</button>}>
      <div className="page-hd"><h1>Users</h1></div>
      <Card>
        <table className="table">
          <thead>
            <tr>
              <th>Username</th>
              <th>Email</th>
              <th>Servers</th>
              <th>Admin</th>
            </tr>
          </thead>
          <tbody>
            {(users || []).length === 0 ? (
              <tr><td colSpan={4} style={{ textAlign: 'center', color: 'var(--ink-muted)', padding: 'var(--space-5)' }}>No users.</td></tr>
            ) : (users || []).map(u => (
              <tr key={u.id}>
                <td style={{ fontWeight: 500 }}>{u.username}</td>
                <td style={{ fontSize: 13 }}>{u.email}</td>
                <td>{u.servers_count ?? 0}</td>
                <td>{u.root_admin ? 'Yes' : 'No'}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </Card>
    </Layout>
  );
}