import React from 'react';

const DOT = {
  running: '#3f6b4a', exited: '#8a1e1e', offline: '#8a1e1e', dead: '#8a1e1e',
  starting: '#a8782a', created: '#a8782a',
  restarting: '#2d5a4e', installing: '#2d5a4e', restoring_backup: '#2d5a4e',
  stopping: '#b85c2c', removing: '#b85c2c',
  paused: '#9b8a64', missing: '#9b8a64',
  install_failed: '#8a1e1e', reinstall_failed: '#8a1e1e', suspended: '#8a1e1e',
};

export default function StatusTag({ condition, label }) {
  const cls = condition || 'offline';
  return (
    <span className={'status-tag ' + cls}>
      <span style={{ width: 6, height: 6, borderRadius: '50%', background: DOT[cls] || '#888', display: 'inline-block' }} />
      {label || cls}
    </span>
  );
}