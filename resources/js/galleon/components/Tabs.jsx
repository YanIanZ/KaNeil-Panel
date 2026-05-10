import React from 'react';
import { Link } from '@inertiajs/react';

export default function Tabs({ items, value, baseHref }) {
  return (
    <div className="tabs">
      {items.map(it => (
        <Link
          key={it.id}
          href={baseHref + '/' + it.id}
          className={'tab' + (value === it.id ? ' active' : '')}
        >
          {it.label}
        </Link>
      ))}
    </div>
  );
}