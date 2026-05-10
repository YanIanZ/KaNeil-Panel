import React, { useState } from 'react';
import { useTheme } from '../context/ThemeContext.jsx';
import Sidebar from './Sidebar.jsx';
import Topbar from './Topbar.jsx';
import TweaksPanel from './TweaksPanel.jsx';

export default function Layout({ server, crumbs, actions, children }) {
  const { t, setTweak } = useTheme();
  const [navOpen, setNavOpen] = useState(false);

  return (
    <div className={'app' + (navOpen ? ' nav-open' : '')}>
      <Sidebar server={server} onClose={() => setNavOpen(false)} />
      <div className="nav-backdrop" onClick={() => setNavOpen(false)} />
      <main className="main">
        <Topbar crumbs={crumbs} onMenu={() => setNavOpen(true)} actions={actions} />
        <div className="main-body">{children}</div>
      </main>
      <TweaksPanel t={t} setTweak={setTweak} />
    </div>
  );
}