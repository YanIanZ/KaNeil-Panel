import React from 'react';
import { Link } from '@inertiajs/react';
import { Layout, Card } from '../../components/index.js';

function formatAddr(ip, port) {
  return ip?.includes(':') ? '[' + ip + ']:' + port : ip + ':' + (port || '');
}

export default function Network({ server }) {
  const alloc = server.allocation;

  return (
    <Layout server={server} crumbs={[{ label: server.name, href: '/server/' + server.uuid }, { label: 'Allocations' }]}>
      <div className="page-hd"><h1>Allocations</h1></div>

      <Card style={{ marginBottom: 'var(--space-4)' }}>
        <div style={{ fontWeight: 600, marginBottom: 'var(--space-3)' }}>Primary Allocation</div>
        {alloc ? (
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr 1fr', gap: 'var(--space-3)' }}>
            <div>
              <div style={{ fontSize: 11, color: 'var(--ink-muted)', textTransform: 'uppercase' }}>IP</div>
              <div style={{ fontFamily: "'JetBrains Mono',monospace", fontSize: 13 }}>{alloc.ip}</div>
            </div>
            <div>
              <div style={{ fontSize: 11, color: 'var(--ink-muted)', textTransform: 'uppercase' }}>Port</div>
              <div style={{ fontFamily: "'JetBrains Mono',monospace", fontSize: 13 }}>{alloc.port}</div>
            </div>
            <div>
              <div style={{ fontSize: 11, color: 'var(--ink-muted)', textTransform: 'uppercase' }}>Address</div>
              <div style={{ fontFamily: "'JetBrains Mono',monospace", fontSize: 13 }}>{formatAddr(alloc.ip, alloc.port)}</div>
            </div>
          </div>
        ) : (
          <div style={{ color: 'var(--ink-muted)' }}>No allocation assigned.</div>
        )}
      </Card>

      <Card>
        <div style={{ fontWeight: 600, marginBottom: 'var(--space-3)' }}>Additional Allocations</div>
        <div style={{ color: 'var(--ink-muted)', fontSize: 13 }}>Allocation management requires Wings API integration (v2.0-EX stub).</div>
      </Card>
    </Layout>
  );
}