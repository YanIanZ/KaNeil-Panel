import React, { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { TweaksPanel } from '../components/index.js';

export default function Login() {
  const [showPw, setShowPw] = useState(false);
  const { data, setData, post, processing, errors } = useForm({ email: '', password: '', remember: false });

  const submit = (e) => { e.preventDefault(); post('/auth/login'); };

  return (
    <div className="app" style={{ placeItems: 'center', display: 'grid', minHeight: '100vh', background: 'var(--bg)' }}>
      <div className="map-bg" />
      <div style={{ display: 'grid', gridTemplateColumns: '1.1fr 1fr', gap: 'var(--space-7)', maxWidth: 960, width: '100%', padding: 'var(--space-6)', position: 'relative' }}>
        <div>
          <div style={{ display: 'flex', alignItems: 'center', gap: 14, color: 'var(--brass)', marginBottom: 18 }}>
            <div>
              <div style={{ fontFamily: 'var(--font-display)', fontSize: 34, color: 'var(--ink)', fontWeight: 700 }}>Galleon</div>
              <div style={{ fontSize: 11, color: 'var(--ink-mid)' }}>Game-server hosting · since the age of sail</div>
            </div>
          </div>
          <h2 style={{ maxWidth: 480, lineHeight: 1.2, fontSize: 30, marginBottom: 14 }}>Steady the helm. Set sail with confidence.</h2>
          <p style={{ maxWidth: 480, color: 'var(--ink-mid)', fontSize: 14, lineHeight: 1.6 }}>One panel for every server in your fleet. Real-time consoles, automated backups, and granular crew permissions.</p>
        </div>
        <div className="card framed" style={{ padding: 'var(--space-6)' }}>
          <form onSubmit={submit} className="col" style={{ gap: 'var(--space-4)' }}>
            <div style={{ fontFamily: 'var(--font-display)', fontSize: 16, fontWeight: 600, color: 'var(--brass)', marginBottom: 8 }}>Sign in</div>
            <div className="field">
              <label>Email</label>
              <input className="input" type="email" value={data.email} onChange={e => setData('email', e.target.value)} placeholder="captain@galleon.gg" />
              {errors.email && <span style={{ color: 'var(--danger)', fontSize: 12 }}>{errors.email}</span>}
            </div>
            <div className="field">
              <label>Password</label>
              <input className="input" type={showPw ? 'text' : 'password'} value={data.password} onChange={e => setData('password', e.target.value)} />
              {errors.password && <span style={{ color: 'var(--danger)', fontSize: 12 }}>{errors.password}</span>}
            </div>
            <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: 12.5 }}>
              <label style={{ display: 'flex', gap: 8, alignItems: 'center', color: 'var(--ink-mid)' }}>
                <input type="checkbox" checked={data.remember} onChange={e => setData('remember', e.target.checked)} />
                Remember this vessel
              </label>
              <span style={{ color: 'var(--ink-muted)', pointerEvents: 'none' }} title="Not yet wired in v2.0-EX">Forgot password?</span>
            </div>
            <button className="btn primary" type="submit" disabled={processing} style={{ justifyContent: 'center', height: 44 }}>
              {processing ? 'Hoisting...' : 'Hoist the colors'}
            </button>
          </form>
        </div>
      </div>
      <TweaksPanel />
    </div>
  );
}