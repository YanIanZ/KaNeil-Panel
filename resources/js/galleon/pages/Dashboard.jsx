import React from 'react';
import { Link } from '@inertiajs/react';
import { Layout, Card, Stat, StatBar, StatusTag } from '../components/index.js';

function formatAddr(ip, port) {
  return ip?.includes(':') ? '[' + ip + ']:' + port : ip + ':' + (port || '');
}

export default function Dashboard({ servers = [], serverStats = null }) {
  const merged = servers.map(s => serverStats?.[s.uuid] ? { ...s, ...serverStats[s.uuid] } : s);
  const onlineCount = merged.filter(s => s.condition === 'running').length;
  const uniqueNodes = new Set(merged.map(s => s.node_id)).size;

  return (
    <Layout crumbs={[{ label: 'Fleet' }]} actions={<Link href="/server/create" className="btn primary">Deploy server</Link>}>
      <div className="page-hd"><h1>Fleet</h1></div>
      <div className="grid grid-4" style={{ marginBottom: 'var(--space-5)' }}>
        <Card><Stat label="Servers" value={merged.length} /></Card>
        <Card><Stat label="Online" value={onlineCount} /></Card>
        <Card><Stat label="Nodes" value={uniqueNodes} /></Card>
        <Card><Stat label="Memory" value={merged.reduce((a, s) => a + (s.memory || 0), 0).toLocaleString()} sub="MB allocated" /></Card>
      </div>
      <div className="grid grid-3">
        {merged.map(s => <ServerCard key={s.uuid} server={s} />)}
        {merged.length === 0 && (
          <div style={{ gridColumn: '1/-1', textAlign: 'center', padding: 'var(--space-7)', color: 'var(--ink-muted)' }}>
            No servers yet. <Link href="/server/create" style={{ color: 'var(--accent)' }}>Deploy one</Link>.
          </div>
        )}
      </div>
    </Layout>
  );
}

function ServerCard({ server }) {
  const cpuPct = Math.min(100, server.cpu_pct ?? 0);
  const memPct = Math.min(100, server.memory_pct ?? 0);
  const alloc = server.allocation;
  return (
    <Card style={{ display: 'flex', flexDirection: 'column', gap: 'var(--space-3)' }}>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
        <div>
          <div style={{ fontWeight: 600, fontSize: 14, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', maxWidth: 160 }}>{server.name}</div>
          <div style={{ fontSize: 11, color: 'var(--ink-muted)', marginTop: 2 }}>{alloc ? formatAddr(alloc.ip, alloc.port) : '—'}</div>
        </div>
        <StatusTag condition={server.condition} label={server.condition_label} />
      </div>
      <div className="col" style={{ gap: 'var(--space-2)' }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: 11, color: 'var(--ink-muted)' }}><span>CPU</span><span>{cpuPct.toFixed(1)}%</span></div>
        <StatBar pct={cpuPct} />
        <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: 11, color: 'var(--ink-muted)' }}><span>Memory</span><span>{memPct.toFixed(1)}%</span></div>
        <StatBar pct={memPct} />
      </div>
      <div style={{ marginTop: 'auto' }}>
        <Link href={'/server/' + server.uuid + '/console'} className="btn ghost sm" style={{ width: '100%', justifyContent: 'center' }}>Open Console</Link>
      </div>
    </Card>
  );
}