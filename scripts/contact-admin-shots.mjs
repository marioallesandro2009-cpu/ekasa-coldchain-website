/**
 * Screenshots of the admin side of the contact form: "Pesan Masuk" list + detail
 * and the Kontak tab in Pengaturan Situs (with the empty-recipients warning).
 *
 *   ADMIN_EMAIL=... ADMIN_PASSWORD=... node scripts/contact-admin-shots.mjs <folder> [baseUrl]
 *
 * Local development only (logs in to the admin with the given account).
 */
import { mkdirSync } from 'node:fs';
import { chromium } from 'playwright-core';

const OUT = process.argv[2] ?? 'docs/screenshots/contact';
const BASE = process.argv[3] ?? 'http://localhost:8000';
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

mkdirSync(OUT, { recursive: true });
const browser = await chromium.launch({ channel: 'chrome', headless: true });
const page = await (await browser.newContext({ viewport: { width: 1440, height: 900 } })).newPage();
const problems = [];
page.on('pageerror', (err) => problems.push(`pageerror: ${err.message}`));
page.on('response', (res) => res.status() >= 400 && problems.push(`HTTP ${res.status()} ${res.url()}`));

await page.goto(`${BASE}/admin/login`, { waitUntil: 'load' });
await page.fill('input[type="email"]', process.env.ADMIN_EMAIL);
await page.fill('input[type="password"]', process.env.ADMIN_PASSWORD);
await page.click('button[type="submit"]');
await page.waitForURL(/\/admin(?!\/login)/);

await page.goto(`${BASE}/admin/contact-messages`, { waitUntil: 'networkidle' });
await sleep(800);
await page.screenshot({ path: `${OUT}/admin-leads-table.png` });

await page.getByRole('link', { name: 'Buka' }).first().click();
await page.waitForURL(/contact-messages\/\d+/);
await sleep(800);
await page.screenshot({ path: `${OUT}/admin-lead-detail.png`, fullPage: true });

await page.goto(`${BASE}/admin/settings?tab=-kontak-tab`, { waitUntil: 'networkidle' });
await page.getByRole('tab', { name: 'Kontak' }).click();
await sleep(800);
await page.screenshot({ path: `${OUT}/admin-settings-kontak.png`, fullPage: true });

console.log('PROBLEMS:', problems.length ? '\n' + problems.join('\n') : 'none');
await browser.close();
