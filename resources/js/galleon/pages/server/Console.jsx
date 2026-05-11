import React, { useEffect, useRef, useState, useCallback } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { Layout, Card, StatusTag } from '../../components/index.js';
import { XtermBoundary } from '../../components/ErrorBoundary.jsx';

function XtermTerminal({ termRef, mountKey, onRetry }) {
  const containerRef = useRef(null);
  const fitRef = useRef(null);

  useEffect(() => {
    let term = null;
    let fitAddon = null;
    let disposed = false;

    import('@xterm/xterm').then(({ Terminal }) => {
      if (disposed) return;
      import('@xterm/addon-fit').then(({ FitAddon }) => {
        if (disposed || !containerRef.current) return;
        term = new Terminal({
          theme: { background: '#0a0e14', foreground: '#c9d1d9', cursor: '#d6a44b' },
          fontFamily: "'JetBrains Mono','SF Mono',ui-monospace,monospace",
          fontSize: 13,
          cursorBlink: true,
          scrollback: 5000,
        });
        fitAddon = new FitAddon();
        term.loadAddon(fitAddon);
        term.open(containerRef.current);
        requestAnimationFrame(() => { try { fitAddon.fit(); } catch {} });
        fitRef.current = fitAddon;
        termRef.current = term;
      });
    });

    const onResize = () => { try { fitRef.current?.fit(); } catch {} };
    window.addEventListener('resize', onResize);

    return () => {
      disposed = true;
      window.removeEventListener('resize', onResize);
      term?.dispose();
      termRef.current = null;
    };
  }, [termRef, mountKey]);

  return React.createElement(XtermBoundary, { onRetry: onRetry },
    React.createElement('div', { ref: containerRef, className: 'terminal-wrap', style: { flex: 1, width: '100%', minHeight: 0 } })
  );
}

export default function Console({ server }) {
  const [input, setInput] = useState('');
  const [status, setStatus] = useState(server.condition || 'offline');
  const [xtermKey, setXtermKey] = useState(0);
  const wsRef = useRef(null);
  const reconnectRef = useRef({ timer: null, delay: 1000 });
  const tokenRef = useRef(null);
  const socketRef = useRef(null);
  const termRef = useRef(null);
  const abortRef = useRef(null);
  const mountedRef = useRef(true);

  const safeName = (server?.name || 'Unknown').replace(/\x1b\[[0-9;]*m/g, '');

  const fetchWs = useCallback(async () => {
    const ctrl = new AbortController();
    abortRef.current = ctrl;
    try {
      const res = await fetch('/api/client/servers/' + server.uuid + '/websocket', {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
        credentials: 'same-origin',
        signal: ctrl.signal,
      });
      if (!res.ok) throw new Error('websocket token fetch failed');
      const json = await res.json();
      tokenRef.current = json.data?.token || null;
      socketRef.current = json.data?.socket || null;
      return { token: tokenRef.current, socket: socketRef.current };
    } catch {
      return null;
    }
  }, [server.uuid]);

  const connectWs = useCallback(({ token, socket }) => {
    if (!token || !socket) return;
    if (wsRef.current) { wsRef.current.close(); wsRef.current = null; }

    const ws = new WebSocket(socket);
    wsRef.current = ws;

    ws.addEventListener('open', () => {
      ws.send(JSON.stringify({ event: 'auth', args: [token] }));
      const term = termRef.current;
      if (term) {
        term.write('\x1b[33m\u2693 Galleon Console \u2014 ' + safeName + '\x1b[0m\n');
        term.write('\x1b[90mConnecting to Ship daemon...\x1b[0m\n');
      }
    });

    ws.addEventListener('message', (e) => {
      try {
        const msg = JSON.parse(e.data);
        if (msg.event === 'console output' && Array.isArray(msg.args)) {
          const term = termRef.current;
          if (term) {
            for (const line of msg.args) term.write(line + '\n');
          }
        } else if (msg.event === 'status' && Array.isArray(msg.args) && msg.args[0]) {
          setStatus(msg.args[0]);
        } else if (msg.event === 'auth success') {
          reconnectRef.current.delay = 1000;
          const term = termRef.current;
          if (term) term.write('\x1b[32mConnected to Ship daemon.\x1b[0m\n');
        } else if (msg.event === 'token expiring') {
          fetchWs().then(r => { if (r?.token) ws.send(JSON.stringify({ event: 'auth', args: [r.token] })); });
        } else if (msg.event === 'token expired') {
          ws.close();
        }
      } catch {}
    });

    ws.addEventListener('close', () => {
      wsRef.current = null;
      const term = termRef.current;
      if (term) term.write('\x1b[31m[disconnected]\x1b[0m\n');
      if (!mountedRef.current) return;
      reconnectRef.current.timer = setTimeout(() => {
        if (!mountedRef.current) return;
        reconnectRef.current.delay = Math.min(reconnectRef.current.delay * 2, 30000);
        fetchWs().then(r => { if (r) connectWs(r); });
      }, reconnectRef.current.delay);
    });

    ws.addEventListener('error', () => {
      const term = termRef.current;
      if (term) term.write('\x1b[31m[websocket error]\x1b[0m\n');
      ws.close();
    });
  }, [fetchWs, safeName]);

  useEffect(() => {
    mountedRef.current = true;
    fetchWs().then(r => { if (r) connectWs(r); });
    return () => {
      mountedRef.current = false;
      abortRef.current?.abort();
      if (reconnectRef.current.timer) clearTimeout(reconnectRef.current.timer);
      if (wsRef.current) wsRef.current.close();
    };
  }, [server.uuid]);

  const sendCommand = (cmd) => {
    if (!cmd.trim() || !wsRef.current) return;
    wsRef.current.send(JSON.stringify({ event: 'send command', args: [cmd.trim()] }));
    if (termRef.current) termRef.current.write('> ' + cmd.trim() + '\n');
    setInput('');
  };

  const power = (action) => {
    if (!wsRef.current) return;
    wsRef.current.send(JSON.stringify({ event: 'set state', args: [action] }));
  };

  const isRunning = ['running','starting','restarting'].includes(status);

  const handleXtermRetry = () => setXtermKey(k => k + 1);

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
        <XtermTerminal termRef={termRef} mountKey={xtermKey} onRetry={handleXtermRetry} />
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