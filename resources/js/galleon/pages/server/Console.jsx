import React, { useEffect, useRef, useState, useCallback } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import { Layout, Card, StatusTag } from '../../components/index.js';

export default function Console({ server }) {
  const [lines, setLines] = useState([]);
  const [input, setInput] = useState('');
  const [status, setStatus] = useState(server.condition || 'offline');
  const wsRef = useRef(null);
  const reconnectRef = useRef({ timer: null, delay: 1000 });
  const tokenRef = useRef(null);
  const termEndRef = useRef(null);

  const fetchToken = useCallback(async () => {
    const ctrl = new AbortController();
    try {
      const res = await fetch('/api/client/servers/' + server.uuid + '/websocket', {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
        signal: ctrl.signal,
      });
      if (!res.ok) throw new Error('token fetch failed');
      const json = await res.json();
      tokenRef.current = json.data?.token || json.data?.split?.pop() || null;
      return tokenRef.current;
    } catch (e) {
      return null;
    }
  }, [server.uuid]);

  const connectWs = useCallback((token) => {
    if (!token) return;
    if (wsRef.current) { wsRef.current.close(); wsRef.current = null; }

    const proto = window.location.protocol === 'https:' ? 'wss' : 'ws';
    const url = proto + '://' + window.location.host + '/api/ws?token=' + encodeURIComponent(token);
    const ws = new WebSocket(url);
    wsRef.current = ws;

    ws.addEventListener('open', () => {
      ws.send(JSON.stringify({ event: 'auth', args: [token] }));
      reconnectRef.current.delay = 1000;
    });

    ws.addEventListener('message', (e) => {
      try {
        const msg = JSON.parse(e.data);
        if (msg.event === 'console output' && Array.isArray(msg.args)) {
          setLines(prev => [...prev, ...msg.args]);
        } else if (msg.event === 'status' && Array.isArray(msg.args) && msg.args[0]) {
          setStatus(msg.args[0]);
        } else if (msg.event === 'token expiring' || msg.event === 'token expired') {
          fetchToken().then(t => { if (t) ws.send(JSON.stringify({ event: 'auth', args: [t] })); });
        }
      } catch {}
    });

    ws.addEventListener('close', () => {
      wsRef.current = null;
      reconnectRef.current.timer = setTimeout(() => {
        reconnectRef.current.delay = Math.min(reconnectRef.current.delay * 2, 30000);
        fetchToken().then(t => { if (t) connectWs(t); });
      }, reconnectRef.current.delay);
    });

    ws.addEventListener('error', () => { ws.close(); });
  }, [fetchToken]);

  useEffect(() => {
    fetchToken().then(t => { if (t) connectWs(t); });
    return () => {
      if (reconnectRef.current.timer) clearTimeout(reconnectRef.current.timer);
      if (wsRef.current) wsRef.current.close();
    };
  }, [server.uuid]);

  useEffect(() => {
    termEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [lines]);

  const sendCommand = (cmd) => {
    if (!cmd.trim() || !wsRef.current) return;
    wsRef.current.send(JSON.stringify({ event: 'send command', args: [cmd.trim()] }));
    setInput('');
  };

  const power = (action) => {
    if (!wsRef.current) return;
    wsRef.current.send(JSON.stringify({ event: 'set state', args: [action] }));
  };

  const isRunning = ['running','starting','restarting'].includes(status);

  return (
    <Layout server={server} crumbs={[{ label: server.name, href: '/server/' + server.uuid }, { label: 'Console' }]}
      actions={
        <div style={{ display: 'flex', gap: 'var(--space-2)' }}>
          {['start','restart','stop','kill'].map(a => (
            <button key={a} className={'btn sm ' + (a === 'kill' ? 'danger' : 'ghost')}
              disabled={a === 'start' ? isRunning : !isRunning && a !== 'kill'}
              onClick={() => power(a)}>{a.charAt(0).toUpperCase() + a.slice(1)}</button>
          ))}
        </div>
      }>
      <div className="page-hd" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
        <h1>Console</h1>
        <StatusTag condition={status} label={server.condition_label} />
      </div>

      <Card style={{ flex: 1, display: 'flex', flexDirection: 'column', padding: 0, overflow: 'hidden', minHeight: 400 }}>
        <div className="terminal" style={{ flex: 1, overflowY: 'auto', padding: 'var(--space-3)', fontFamily: "'JetBrains Mono',monospace", fontSize: 12, whiteSpace: 'pre-wrap', wordBreak: 'break-all' }}>
          {lines.map((l, i) => <div key={i}>{l}</div>)}
          <div ref={termEndRef} />
        </div>
        <div style={{ display: 'flex', borderTop: '1px solid var(--border)' }}>
          <input className="input" value={input} onChange={e => setInput(e.target.value)}
            onKeyDown={e => { if (e.key === 'Enter') sendCommand(input); }}
            placeholder="Send command…" disabled={!isRunning}
            style={{ flex: 1, border: 'none', borderRadius: 0, background: 'transparent' }} />
          <button className="btn primary sm" onClick={() => sendCommand(input)} disabled={!isRunning || !input.trim()}>Send</button>
        </div>
      </Card>

      <div className="grid grid-3" style={{ marginTop: 'var(--space-4)' }}>
        <Card><div style={{ fontSize: 11, color: 'var(--ink-muted)', textTransform: 'uppercase' }}>Server</div><div style={{ fontWeight: 600 }}>{server.name}</div></Card>
        <Card><div style={{ fontSize: 11, color: 'var(--ink-muted)', textTransform: 'uppercase' }}>Node</div><div style={{ fontWeight: 600 }}>{server.node?.name || '—'}</div></Card>
        <Card><div style={{ fontSize: 11, color: 'var(--ink-muted)', textTransform: 'uppercase' }}>Memory</div><div style={{ fontWeight: 600 }}>{(server.memory || 0).toLocaleString()} MB</div></Card>
      </div>
    </Layout>
  );
}