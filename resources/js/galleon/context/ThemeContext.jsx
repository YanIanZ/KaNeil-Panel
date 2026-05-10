import React, { createContext, useContext, useState, useEffect } from 'react';

const ThemeContext = createContext(null);

const FONT_DISPLAY = { Cinzel: "'Cinzel',serif", IMFell: "'IM Fell English',serif", Pirata: "'Pirata One',cursive", Cormorant: "'Cormorant Garamond',serif", Spectral: "'Spectral',serif" };
const FONT_BODY = { Inter: "'Inter',system-ui,sans-serif", Lato: "'Lato',system-ui,sans-serif", Sora: "'Sora',system-ui,sans-serif", Spectral: "'Spectral',Georgia,serif" };
const FONT_QUERIES = { Cinzel: 'Cinzel:wght@500;600;700', IMFell: 'IM+Fell+English:ital@0;1', Pirata: 'Pirata+One', Cormorant: 'Cormorant+Garamond:wght@500;600;700', Spectral: 'Spectral:wght@500;600;700' };
const BODY_QUERIES = { Inter: 'Inter:wght@400;500;600;700', Lato: 'Lato:wght@400;700', Sora: 'Sora:wght@400;500;600;700', Spectral: 'Spectral:wght@400;500;600' };
const ACCENTS = [['#8a1e1e','#a8782a','#2d5a4e'],['#1f4068','#d6a44b','#7a3a1e'],['#2d5a4e','#c97c5d','#1a3c5e'],['#5a1e3a','#d6a44b','#3d2c5e']];

function injectFonts(displayFont, bodyFont) {
  let link = document.getElementById('gf-galleon');
  if (!link) { link = document.createElement('link'); link.id = 'gf-galleon'; link.rel = 'stylesheet'; document.head.appendChild(link); }
  const fams = [FONT_QUERIES[displayFont] || FONT_QUERIES.Cinzel, BODY_QUERIES[bodyFont] || BODY_QUERIES.Inter, 'JetBrains+Mono:wght@400;500;700'].map(q => 'family=' + q).join('&');
  link.href = 'https://fonts.googleapis.com/css2?' + fams + '&display=swap';
}

export function ThemeProvider({ children }) {
  const load = () => { try { return JSON.parse(localStorage.getItem('galleon-tweaks') || 'null') || { dark: false, pirate: 1, density: 'regular', displayFont: 'Cinzel', bodyFont: 'Inter', accent: ['#8a1e1e','#a8782a','#2d5a4e'] }; } catch { return { dark: false, pirate: 1, density: 'regular', displayFont: 'Cinzel', bodyFont: 'Inter', accent: ['#8a1e1e','#a8782a','#2d5a4e'] }; } };
  const [t, setT] = useState(load);
  const setTweak = (k, v) => setT(prev => { const n = { ...prev, [k]: v }; localStorage.setItem('galleon-tweaks', JSON.stringify(n)); return n; });

  useEffect(() => {
    const r = document.documentElement;
    r.dataset.theme = t.dark ? 'dark' : 'light';
    r.style.setProperty('--pirate', String(t.pirate));
    r.style.setProperty('--density', String(({ compact: 0.85, regular: 1, comfy: 1.15 })[t.density] || 1));
    r.style.setProperty('--font-display', FONT_DISPLAY[t.displayFont] || FONT_DISPLAY.Cinzel);
    r.style.setProperty('--font-body', FONT_BODY[t.bodyFont] || FONT_BODY.Inter);
    if (Array.isArray(t.accent)) { r.style.setProperty('--accent', t.accent[0]); r.style.setProperty('--brass', t.accent[1]); r.style.setProperty('--sea', t.accent[2]); }
    injectFonts(t.displayFont, t.bodyFont);
  }, [t]);

  return <ThemeContext.Provider value={{ t, setTweak }}>{children}</ThemeContext.Provider>;
}

export function useTheme() {
  const ctx = useContext(ThemeContext);
  if (!ctx) throw new Error('useTheme must be used within ThemeProvider');
  return ctx;
}

export { ACCENTS };