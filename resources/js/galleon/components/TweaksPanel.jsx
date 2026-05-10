import React, { useState } from 'react';
import { useTheme, ACCENTS } from '../context/ThemeContext.jsx';

export default function TweaksPanel() {
  const { t, setTweak } = useTheme();
  const [open, setOpen] = useState(false);

  return (
    <>
      <button
        onClick={() => setOpen(o => !o)}
        style={{
          position: 'fixed', bottom: 20, right: 20, zIndex: 300,
          width: 44, height: 44, borderRadius: '50%',
          background: 'var(--surface)', border: '1px solid var(--border)',
          cursor: 'pointer', fontSize: 18, display: 'flex', alignItems: 'center', justifyContent: 'center',
          boxShadow: '0 2px 12px rgb(var(--shadow-color)/0.2)',
        }}
        title="Tweaks"
      >⚙</button>

      {open && (
        <div style={{
          position: 'fixed', bottom: 72, right: 20, zIndex: 300,
          width: 260, background: 'var(--surface-raised)',
          border: '1px solid var(--border-strong)', borderRadius: 10,
          padding: 'var(--space-4)', display: 'flex', flexDirection: 'column', gap: 'var(--space-4)',
          boxShadow: '0 8px 32px rgb(var(--shadow-color)/0.25)',
        }}>
          <div style={{ fontFamily: 'var(--font-display)', fontSize: 14, fontWeight: 700, color: 'var(--brass)' }}>Tweaks</div>

          <label style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', fontSize: 13 }}>
            Dark mode
            <input type="checkbox" checked={t.dark} onChange={e => setTweak('dark', e.target.checked)} />
          </label>

          <div>
            <div style={{ fontSize: 12, marginBottom: 4 }}>Pirate intensity: {Math.round(t.pirate * 100)}%</div>
            <input type="range" min={0} max={100} step={5} value={Math.round(t.pirate * 100)}
              onChange={e => setTweak('pirate', Number(e.target.value) / 100)} aria-label="Pirate intensity" style={{ width: '100%' }} />
          </div>

          <div>
            <div style={{ fontSize: 12, marginBottom: 6 }}>Accent</div>
            <div style={{ display: 'flex', gap: 8 }}>
              {ACCENTS.map((ac, i) => (
                <button key={i} onClick={() => setTweak('accent', ac)}
                  style={{
                    width: 28, height: 28, borderRadius: '50%',
                    background: ac[0], border: JSON.stringify(t.accent) === JSON.stringify(ac) ? '3px solid var(--ink)' : '2px solid transparent',
                    cursor: 'pointer',
                  }} />
              ))}
            </div>
          </div>

          <div>
            <div style={{ fontSize: 12, marginBottom: 6 }}>Density</div>
            <div style={{ display: 'flex', gap: 6 }}>
              {['compact','regular','comfy'].map(d => (
                <button key={d} className={'btn sm ' + (t.density === d ? 'primary' : 'ghost')}
                  onClick={() => setTweak('density', d)} style={{ flex: 1, justifyContent: 'center', fontSize: 11 }}>
                  {d}
                </button>
              ))}
            </div>
          </div>
        </div>
      )}
    </>
  );
}