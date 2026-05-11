import React from 'react';

export class GalleonErrorBoundary extends React.Component {
  state = { hasError: false };
  static getDerivedStateFromError() { return { hasError: true }; }
  render() {
    if (this.state.hasError) return (
      <div style={{ minHeight: '100vh', display: 'grid', placeItems: 'center', background: 'var(--bg)' }}>
        <div style={{ textAlign: 'center', padding: 32 }}>
          <div style={{ fontSize: 72, marginBottom: 16 }}>🦑</div>
          <h2 style={{ fontFamily: 'var(--font-display)', fontSize: 24 }}>The Kraken Strikes</h2>
          <p style={{ color: 'var(--ink-mid)', margin: '12px 0' }}>Something went terribly wrong below decks.</p>
          <button className="btn primary" onClick={() => window.location.reload()}>Reload</button>
        </div>
      </div>
    );
    return this.props.children;
  }
}

export class XtermBoundary extends React.Component {
  state = { hasError: false };
  static getDerivedStateFromError() { return { hasError: true }; }
  render() {
    if (this.state.hasError) return (
      <div className="terminal-wrap" style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#8a1e1e' }}>
        Terminal failed to load. <button className="btn ghost sm" style={{ marginLeft: 12 }} onClick={() => { this.setState({ hasError: false }); this.props.onRetry?.(); }}>Retry</button>
      </div>
    );
    return this.props.children;
  }
}