import React, { useState } from 'react';
import { Link } from '@inertiajs/react';
import { Layout, Card } from '../../components/index.js';

export default function Schedules({ server }) {
  const [showCreate, setShowCreate] = useState(false);
  const schedules = server.schedules || [];

  return (
    <Layout server={server} crumbs={[{ label: server.name, href: '/server/' + server.uuid }, { label: 'Schedules' }]}
      actions={<button className="btn primary sm" onClick={() => setShowCreate(true)}>New Schedule</button>}>
      <div className="page-hd"><h1>Schedules</h1></div>

      {showCreate && (
        <Card style={{ marginBottom: 'var(--space-4)' }}>
          <div style={{ fontWeight: 600, marginBottom: 'var(--space-3)' }}>Create Schedule</div>
          <div style={{ color: 'var(--ink-muted)', fontSize: 13 }}>Schedule creation requires Wings API integration (v2.0-EX stub).</div>
          <button className="btn ghost sm" onClick={() => setShowCreate(false)} style={{ marginTop: 'var(--space-2)' }}>Cancel</button>
        </Card>
      )}

      <Card>
        <table className="table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Cron</th>
              <th>Active</th>
              <th style={{ width: 80 }}>Actions</th>
            </tr>
          </thead>
          <tbody>
            {schedules.length === 0 ? (
              <tr><td colSpan={4} style={{ textAlign: 'center', color: 'var(--ink-muted)', padding: 'var(--space-5)' }}>No schedules.</td></tr>
            ) : schedules.map(s => (
              <tr key={s.id}>
                <td style={{ fontWeight: 500 }}>{s.name}</td>
                <td style={{ fontFamily: "'JetBrains Mono',monospace", fontSize: 12 }}>
                  {s.cron_minute} {s.cron_hour} * * {s.cron_day_of_week}
                </td>
                <td>{s.is_active ? 'Active' : 'Paused'}</td>
                <td><button className="btn ghost sm">Edit</button></td>
              </tr>
            ))}
          </tbody>
        </table>
      </Card>
    </Layout>
  );
}