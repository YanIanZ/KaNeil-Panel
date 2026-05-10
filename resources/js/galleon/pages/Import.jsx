import React from 'react';
import { useForm, Link } from '@inertiajs/react';
import { Layout, Card } from '../components/index.js';

export default function Import() {
  const { data, setData, post, processing, errors } = useForm({ json_content: '' });

  return (
    <Layout crumbs={[{ label: 'Admiralty', href: '/admin/maps' }, { label: 'Import Map' }]}>
      <div className="page-hd"><h1>Import Map</h1></div>

      <Card>
        <form onSubmit={e => { e.preventDefault(); post('/admin/maps/import'); }} className="col" style={{ gap: 'var(--space-4)' }}>
          <div className="field">
            <label>Map JSON</label>
            <textarea
              className="input"
              value={data.json_content}
              onChange={e => setData('json_content', e.target.value)}
              placeholder='{"name":"My Map","description":"...","docker_images":"ghcr.io/...","startup":"./start",...}'
              rows={14}
              style={{ fontFamily: "'JetBrains Mono',monospace", fontSize: 12, resize: 'vertical' }}
            />
            {errors.json_content && <span style={{ color: 'var(--danger)', fontSize: 12 }}>{errors.json_content}</span>}
          </div>

          <div style={{ display: 'flex', gap: 'var(--space-3)' }}>
            <button className="btn primary" type="submit" disabled={processing}>{processing ? 'Importing...' : 'Import Map'}</button>
            <Link href="/admin/maps" className="btn ghost">Cancel</Link>
          </div>
        </form>
      </Card>
    </Layout>
  );
}