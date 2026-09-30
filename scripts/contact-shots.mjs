/**
 * Screenshots + behaviour check of the #contact form.
 *
 *   node scripts/contact-shots.mjs <folder> [baseUrl]
 *
 * Saves desktop / mobile, validation errors, success (JS and no-JS), EN + ID, and
 * prints console errors, failed requests (404) and the result of each submit.
 * NOTE: it really submits the form (5 per 15 min per IP is the limit), so run
 * `php artisan cache:clear` first if you re-run it.
 */
import { mkdirSync } from 'node:fs';
import { chromium } from 'playwright-core';

const OUT = process.argv[2] ?? 'docs/screenshots/contact';
const BASE = process.argv[3] ?? 'http://localhost:8000';
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

mkdirSync(OUT, { recursive: true });
const browser = await chromium.launch({ channel: 'chrome', headless: true });
const problems = [];

async function page(width, height, { js = true, reduce = false } = {}) {
    const context = await browser.newContext({
        viewport: { width, height },
        javaScriptEnabled: js,
        reducedMotion: reduce ? 'reduce' : 'no-preference',
    });
    const p = await context.newPage();
    p.on('pageerror', (err) => problems.push(`pageerror ${width}: ${err.message}`));
    p.on('console', (msg) => msg.type() === 'error' && problems.push(`console ${width}: ${msg.text()}`));
    p.on('response', (res) => res.status() >= 400 && problems.push(`HTTP ${res.status()} ${res.url()}`));

    return p;
}

async function toContact(p, path) {
    await p.goto(`${BASE}${path}`, { waitUntil: 'load' });
    await p.evaluate(() => {
        document.documentElement.style.scrollBehavior = 'auto';
        const el = document.getElementById('contact');
        window.scrollTo(0, el.getBoundingClientRect().top + scrollY - 72);
    });
    await sleep(2200);
}

const fill = async (p, data) => {
    for (const [name, value] of Object.entries(data)) {
        await p.fill(`#contact [name="${name}"]`, value);
    }
};

// 1. Desktop ID + EN (empty form)
for (const [locale, path] of [['id', '/id'], ['en', '/']]) {
    const p = await page(1440, 900);
    await toContact(p, path);
    await p.screenshot({ path: `${OUT}/desktop-${locale}.png` });
    await p.context().close();
}

// 2. Mobile 375 + tablet 768
for (const w of [375, 768]) {
    const p = await page(w, 812);
    await toContact(p, '/id');
    await p.screenshot({ path: `${OUT}/mobile-${w}.png`, fullPage: false });
    await p.evaluate(() => document.querySelector('#contact form').scrollIntoView({ block: 'start' }));
    await sleep(600);
    await p.screenshot({ path: `${OUT}/mobile-${w}-form.png` });
    await p.context().close();
}

// 3. Validation errors (server, via fetch): bad email + short message
{
    const p = await page(1440, 900);
    await toContact(p, '/id');
    await fill(p, { name: 'Budi', email: 'budi@klien.test', message: 'pendek' });
    // bypass the browser's own checks so the SERVER answers
    await p.evaluate(() => document.querySelectorAll('#contact form [required], #contact form [minlength]').forEach((el) => { el.removeAttribute('required'); el.removeAttribute('minlength'); }));
    await p.evaluate(() => { document.querySelector('#contact [name="email"]').type = 'text'; });
    await p.fill('#contact [name="email"]', 'bukan-email');
    await p.click('#contact button[type="submit"]', { force: true });
    await sleep(1500);
    await p.evaluate(() => document.querySelector('#contact form').scrollIntoView({ block: 'center' }));
    await sleep(400);
    await p.screenshot({ path: `${OUT}/validation-error-id.png` });
    await p.context().close();
}

// 4. Success with JS (fetch), ID
{
    const p = await page(1440, 900);
    await toContact(p, '/id');
    await fill(p, { name: 'Budi Santoso', email: 'budi@klien.test', phone: '+62 812 3456 7890', company: 'PT Klien', message: 'Kami butuh cold storage untuk 20 pallet frozen.' });
    await p.selectOption('#contact [name="service_id"]', { index: 1 });
    await p.click('#contact button[type="submit"]', { force: true });
    await sleep(2000);
    await p.evaluate(() => document.querySelector('#contact form').scrollIntoView({ block: 'center' }));
    await sleep(400);
    await p.screenshot({ path: `${OUT}/success-js-id.png` });
    console.log('success-js text:', (await p.locator('#contact [role="status"]').allInnerTexts()).join(' | '));
    await p.context().close();
}

// 5. Success WITHOUT JavaScript (plain POST + redirect), EN
{
    const p = await page(1440, 900, { js: false });
    await p.goto(`${BASE}/`, { waitUntil: 'load' });
    await fill(p, { name: 'Jane Doe', email: 'jane@client.test', message: 'We need reefer trucks for weekly Jakarta to Surabaya runs.' });
    await p.click('#contact button[type="submit"]', { force: true });
    await sleep(2500);
    await sleep(800);
    console.log('no-JS redirect ->', p.url());
    await p.evaluate(() => document.getElementById('contact').scrollIntoView());
    await p.screenshot({ path: `${OUT}/success-nojs-en.png` });
    await p.context().close();
}

// 6. No-JS validation error (server-side flash), ID
{
    const p = await page(1440, 900, { js: false });
    await p.goto(`${BASE}/id`, { waitUntil: 'load' });
    await p.evaluate(() => document.querySelectorAll('#contact form [required], #contact form [minlength]').forEach((el) => { el.removeAttribute('required'); el.removeAttribute('minlength'); }));
    await p.evaluate(() => { document.querySelector('#contact [name="email"]').type = 'text'; });
    await fill(p, { name: 'Sari', email: 'salah', message: 'x' });
    await p.click('#contact button[type="submit"]', { force: true });
    await sleep(2500);
    await p.evaluate(() => document.querySelector('#contact form').scrollIntoView({ block: 'center' }));
    await p.screenshot({ path: `${OUT}/validation-error-nojs-id.png` });
    await p.context().close();
}

// 7. Reduced motion: content visible at once
{
    const p = await page(1440, 900, { reduce: true });
    await p.goto(`${BASE}/id`, { waitUntil: 'load' });
    await p.evaluate(() => { document.documentElement.style.scrollBehavior = 'auto'; window.scrollTo(0, document.getElementById('contact').getBoundingClientRect().top + scrollY - 72); });
    await sleep(500);
    const visible = await p.evaluate(() => [...document.querySelectorAll('#contact [data-animate]')].map((el) => getComputedStyle(el).opacity));
    console.log('reduced-motion opacities:', visible.join(', '));
    await p.context().close();
}

console.log('PROBLEMS:', problems.length ? '\n' + problems.join('\n') : 'none (0 console errors, 0 failed requests)');
await browser.close();
