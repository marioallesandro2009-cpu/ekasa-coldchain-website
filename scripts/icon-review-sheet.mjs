/**
 * Review sheet (PNG) for a set of Ekasa icons: 96, 48, 24 and 16 px on a light
 * and a dark tile, plus a row with the approved icons for style comparison.
 *
 *   node scripts/icon-review-sheet.mjs <out.png> "<title>" key[,key...] [--compare=key,key] [--dir=extraDir]
 *
 * Icons are read from resources/icons/ekasa/ (or --dir for review-only variants).
 */
import { existsSync, readFileSync } from 'node:fs';
import { chromium } from 'playwright-core';

const [out, title, keyList, ...flags] = process.argv.slice(2);
const option = (name) => flags.find((flag) => flag.startsWith(`--${name}=`))?.split('=')[1];
const extraDir = option('dir');
const compare = (option('compare') ?? '').split(',').filter(Boolean);
const keys = keyList.split(',');

const read = (key) => {
    const file = [`resources/icons/ekasa/${key}.svg`, extraDir && `${extraDir}/${key}.svg`].find((f) => f && existsSync(f));
    return readFileSync(file, 'utf8');
};

const manifest = JSON.parse(process.env.ICON_LABELS ?? '{}');
const card = (key) => `
<div class="card">
  <div class="tile light">${[96, 48, 24, 16].map((s) => `<span style="width:${s}px;height:${s}px">${read(key)}</span>`).join('')}</div>
  <div class="tile dark">${[48, 24, 16].map((s) => `<span style="width:${s}px;height:${s}px">${read(key)}</span>`).join('')}</div>
  <div class="name"><code>${key}</code>${manifest[key] ? ` · ${manifest[key]}` : ''}</div>
</div>`;

const html = `<!doctype html><meta charset="utf-8"><style>
body{margin:0;padding:24px;font:13px/1.4 system-ui,sans-serif;background:#f5f7fa;color:#2d3748}
h1{margin:0 0 4px;font-size:20px} p{margin:0 0 16px;color:#647084}
h2{margin:22px 0 10px;font-size:13px;text-transform:uppercase;letter-spacing:.08em;color:#647084}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:12px}
.card{border:1px solid #d0d5de;border-radius:10px;overflow:hidden;background:#fff}
.tile{display:flex;align-items:center;gap:18px;padding:12px 16px}
.light{color:#0f6b46}.dark{background:#16231d;color:#fff}
.tile span{display:block}.tile svg{width:100%;height:100%;display:block}
.name{padding:8px 12px;border-top:1px solid #d0d5de}
</style>
<h1>${title}</h1><p>Stroke 1.75, currentColor · 96 / 48 / 24 / 16 px di latar putih, 48 / 24 / 16 px di latar gelap</p>
<div class="grid">${keys.map(card).join('')}</div>
${compare.length ? `<h2>Pembanding: ikon batch 1 yang sudah disetujui</h2><div class="grid">${compare.map(card).join('')}</div>` : ''}`;

const browser = await chromium.launch({ channel: 'chrome' });
const page = await browser.newPage({ viewport: { width: 1000, height: 600 }, deviceScaleFactor: 2 });
await page.setContent(html);
await page.screenshot({ path: out, fullPage: true });
await browser.close();
console.log(out);
