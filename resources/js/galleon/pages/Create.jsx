import React, { useState, useEffect } from 'react';
import { useForm, Link, usePage } from '@inertiajs/react';
import { Layout, Card } from '../components/index.js';

export default function Create({ maps = [], nodes = [] }) {
  const { data, setData, post, processing, errors } = useForm({
    name: '',
    map_id: '',
    node_id: '',
    allocation_id: '',
    memory: 1024,
    swap: 0,
    disk: 5000,
    io: 500,
    cpu: 0,
    startup: '',
    image: '',
  });

  const [allocations, setAllocations] = useState([]);

  useEffect(() => {
    if (!data.node_id) { setAllocations([]); return; }
    const node = nodes.find(n => String(n.id) === String(data.node_id));
    if (!node) { setAllocations([]); return; }
    fetch('/api/nodes/' + node.id + '/allocations?filter[assigned]=false', {
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
    })
      .then(r => r.json())
      .then(json => setAllocations(json.data || []))
      .catch(() => setAllocations([]));
  }, [data.node_id]);

  const selectedMap = maps.find(m => String(m.id) === String(data.map_id));
  const effectiveStartup = data.startup || selectedMap?.startup || '';
  const effectiveImage = data.image || (typeof selectedMap?.docker_images === 'string' ? selectedMap.docker_images : Array.isArray(selectedMap?.docker_images) ? selectedMap.docker_images[0] : '') || '';

  return (
    <Layout crumbs={[{ label: 'Fleet', href: '/' }, { label: 'Deploy Server' }]}>
      <div className="page-hd"><h1>Deploy Server</h1></div>

      <Card>
        <form onSubmit={e => { e.preventDefault(); post('/server'); }} className="col" style={{ gap: 'var(--space-4)' }}>
          <div className="page-hd"><h2 style={{ fontSize: 16 }}>Identity</h2></div>

          <div className="grid grid-2">
            <div className="field">
              <label>Server Name</label>
              <input className="input" value={data.name} onChange={e => setData('name', e.target.value)} placeholder="my-server" />
              {errors.name && <span style={{ color: 'var(--danger)', fontSize: 12 }}>{errors.name}</span>}
            </div>
            <div className="field">
              <label>Map</label>
              <select className="input" value={data.map_id} onChange={e => setData('map_id', e.target.value)}>
                <option value="">Select a map…</option>
                {maps.map(m => <option key={m.id} value={m.id}>{m.name}</option>)}
              </select>
              {errors.map_id && <span style={{ color: 'var(--danger)', fontSize: 12 }}>{errors.map_id}</span>}
            </div>
          </div>

          <div className="page-hd"><h2 style={{ fontSize: 16 }}>Placement</h2></div>

          <div className="grid grid-2">
            <div className="field">
              <label>Node</label>
              <select className="input" value={data.node_id} onChange={e => { setData('node_id', e.target.value); setData('allocation_id', ''); }}>
                <option value="">Select a node…</option>
                {nodes.map(n => <option key={n.id} value={n.id}>{n.name}</option>)}
              </select>
              {errors.node_id && <span style={{ color: 'var(--danger)', fontSize: 12 }}>{errors.node_id}</span>}
            </div>
            <div className="field">
              <label>Allocation</label>
              <select className="input" value={data.allocation_id} onChange={e => setData('allocation_id', e.target.value)} disabled={!data.node_id}>
                <option value="">Select an allocation…</option>
                {allocations.map(a => <option key={a.id} value={a.id}>{a.ip}:{a.port}</option>)}
              </select>
              {errors.allocation_id && <span style={{ color: 'var(--danger)', fontSize: 12 }}>{errors.allocation_id}</span>}
            </div>
          </div>

          <div className="page-hd"><h2 style={{ fontSize: 16 }}>Resources</h2></div>

          <div className="grid grid-3">
            <div className="field">
              <label>Memory (MB)</label>
              <input className="input" type="number" value={data.memory} onChange={e => setData('memory', Number(e.target.value))} />
              {errors.memory && <span style={{ color: 'var(--danger)', fontSize: 12 }}>{errors.memory}</span>}
            </div>
            <div className="field">
              <label>Swap (MB)</label>
              <input className="input" type="number" value={data.swap} onChange={e => setData('swap', Number(e.target.value))} />
              {errors.swap && <span style={{ color: 'var(--danger)', fontSize: 12 }}>{errors.swap}</span>}
            </div>
            <div className="field">
              <label>Disk (MB)</label>
              <input className="input" type="number" value={data.disk} onChange={e => setData('disk', Number(e.target.value))} />
              {errors.disk && <span style={{ color: 'var(--danger)', fontSize: 12 }}>{errors.disk}</span>}
            </div>
          </div>

          <div className="grid grid-3">
            <div className="field">
              <label>Block I/O</label>
              <input className="input" type="number" value={data.io} onChange={e => setData('io', Number(e.target.value))} />
              {errors.io && <span style={{ color: 'var(--danger)', fontSize: 12 }}>{errors.io}</span>}
            </div>
            <div className="field">
              <label>CPU Limit (%)</label>
              <input className="input" type="number" value={data.cpu} onChange={e => setData('cpu', Number(e.target.value))} />
              {errors.cpu && <span style={{ color: 'var(--danger)', fontSize: 12 }}>{errors.cpu}</span>}
            </div>
          </div>

          <div className="page-hd"><h2 style={{ fontSize: 16 }}>Startup</h2></div>

          <div className="field">
            <label>Startup Command</label>
            <input className="input" value={data.startup} onChange={e => setData('startup', e.target.value)} placeholder={selectedMap?.startup || './start'} />
            {errors.startup && <span style={{ color: 'var(--danger)', fontSize: 12 }}>{errors.startup}</span>}
          </div>

          <div className="field">
            <label>Docker Image</label>
            <input className="input" value={data.image} onChange={e => setData('image', e.target.value)} placeholder={effectiveImage || 'ghcr.io/...'} />
            {errors.image && <span style={{ color: 'var(--danger)', fontSize: 12 }}>{errors.image}</span>}
          </div>

          <div style={{ display: 'flex', gap: 'var(--space-3)', marginTop: 'var(--space-3)' }}>
            <button className="btn primary" type="submit" disabled={processing}>{processing ? 'Deploying...' : 'Deploy Server'}</button>
            <Link href="/" className="btn ghost">Cancel</Link>
          </div>
        </form>
      </Card>
    </Layout>
  );
}