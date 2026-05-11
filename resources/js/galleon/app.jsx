import './styles.css';
import '@xterm/xterm/css/xterm.css';
import axios from 'axios';
import React, { Suspense } from 'react';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { ThemeProvider } from './context/ThemeContext.jsx';
import { GalleonErrorBoundary } from './components/ErrorBoundary.jsx';

axios.defaults.headers.common['X-CSRF-TOKEN'] =
  document.querySelector('meta[name="csrf-token"]')?.content ?? '';

function PageSkeleton() {
  return (
    <div style={{ display: 'flex', height: '100vh', background: 'var(--bg)' }}>
      <div style={{ width: 220, background: 'var(--surface)', borderRight: '1px solid var(--border)' }} />
      <div style={{ flex: 1, padding: 32 }}>
        <div style={{ height: 24, width: 200, background: 'var(--surface-2)', borderRadius: 4, marginBottom: 24 }} />
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3,1fr)', gap: 16 }}>
          {[1,2,3,4,5,6].map(i => (
            <div key={i} style={{ height: 120, background: 'var(--surface)', borderRadius: 8, border: '1px solid var(--border)' }} />
          ))}
        </div>
      </div>
    </div>
  );
}

createInertiaApp({
  resolve: (name) => {
    const pages = import.meta.glob('./pages/**/*.jsx');
    const loader = pages['./pages/' + name + '.jsx'];
    if (!loader) {
      console.error('[galleon] page not found: ' + name);
      return import('./pages/Error.jsx').then(function(m) { return { default: function() { return React.createElement(m.default, { status: '404' }); } }; });
    }
    return loader();
  },
  setup({ el, App, props }) {
    createRoot(el).render(
      <GalleonErrorBoundary>
        <ThemeProvider>
          <Suspense fallback={<PageSkeleton />}>
            <App {...props} />
          </Suspense>
        </ThemeProvider>
      </GalleonErrorBoundary>
    );
  },
});