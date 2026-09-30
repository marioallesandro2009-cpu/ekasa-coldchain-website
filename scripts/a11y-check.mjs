/**
 * Contrast (WCAG AA) and keyboard check for the #contact form and the admin "Pesan Masuk".
 *
 *   ADMIN_EMAIL=... ADMIN_PASSWORD=... node scripts/a11y-check.mjs [baseUrl]
 *
 * Contrast: for every visible text element, the computed text colour and every
 * translucent background between it and the page are composited (via a 1x1 canvas, so
 * oklch / color-mix values work) and the ratio is compared with 4.5:1 (3:1 for large
 * text). Input borders are checked against 3:1 (non-text contrast, WCAG 1.4.11).
 * Keyboard: Tab through the area, list the stops and whether each shows a focus change.
 * Local development only.
 */
import { chromium } from 'playwright-core';

const BASE = process.argv[2] ?? 'http://localhost:8000';
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

/** Runs in the page: returns the contrast failures inside `rootSelector`. */
const contrastScan = (rootSelector) => {
    const canvas = document.createElement('canvas');
    canvas.width = canvas.height = 1;
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    const rgba = (css) => {
        ctx.clearRect(0, 0, 1, 1);
        ctx.fillStyle = '#000';
        ctx.fillStyle = css;
        ctx.fillRect(0, 0, 1, 1);
        const [r, g, b, a] = ctx.getImageData(0, 0, 1, 1).data;

        return { r, g, b, a: a / 255 };
    };
    const over = (top, bottom) => {
        const a = top.a + bottom.a * (1 - top.a);

        return a === 0 ? { r: 0, g: 0, b: 0, a: 0 } : {
            r: (top.r * top.a + bottom.r * bottom.a * (1 - top.a)) / a,
            g: (top.g * top.a + bottom.g * bottom.a * (1 - top.a)) / a,
            b: (top.b * top.a + bottom.b * bottom.a * (1 - top.a)) / a,
            a,
        };
    };
    const lum = ({ r, g, b }) => {
        const f = (v) => { v /= 255; return v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4; };

        return 0.2126 * f(r) + 0.7152 * f(g) + 0.0722 * f(b);
    };
    const ratio = (a, b) => { const [hi, lo] = [lum(a), lum(b)].sort((x, y) => y - x); return (hi + 0.05) / (lo + 0.05); };
    const backdrop = (el) => {
        const chain = [];
        for (let node = el; node; node = node.parentElement) chain.unshift(getComputedStyle(node).backgroundColor);

        return chain.reduce((acc, css) => over(rgba(css), acc), { r: 255, g: 255, b: 255, a: 1 });
    };
    const label = (el) => `${el.tagName.toLowerCase()}${el.id ? '#' + el.id : ''}${el.name ? `[name=${el.name}]` : ''} "${(el.value || el.textContent || el.placeholder || '').trim().slice(0, 32)}"`;
    const visible = (el) => { const r = el.getBoundingClientRect(); const s = getComputedStyle(el); return r.width > 0 && r.height > 0 && s.visibility !== 'hidden' && s.display !== 'none' && +s.opacity > 0.05 && r.left > -1000; };

    const failures = [];
    const seen = [];
    const root = document.querySelector(rootSelector);

    root.querySelectorAll('*').forEach((el) => {
        if (!visible(el)) return;
        const style = getComputedStyle(el);
        const ownText = [...el.childNodes].some((n) => n.nodeType === 3 && n.textContent.trim());
        const isField = el.matches('input:not([type=hidden]):not([type=checkbox]), textarea, select');

        if (ownText || (isField && (el.value || el.placeholder))) {
            const bg = backdrop(el);
            const fg = over(rgba(style.color), bg);
            const size = parseFloat(style.fontSize);
            const large = size >= 24 || (size >= 18.66 && +style.fontWeight >= 700);
            const need = large ? 3 : 4.5;
            const value = ratio(fg, bg);
            seen.push(value);
            if (value < need) failures.push({ kind: 'text', el: label(el), ratio: +value.toFixed(2), need });
        }

        if (isField && el.placeholder && !el.value) {
            const bg = backdrop(el);
            const fg = over(rgba(getComputedStyle(el, '::placeholder').color), bg);
            const value = ratio(fg, bg);
            if (value < 4.5) failures.push({ kind: 'placeholder', el: label(el), ratio: +value.toFixed(2), need: 4.5 });
        }

        if (isField && parseFloat(style.borderTopWidth) > 0) {
            const parentBg = backdrop(el.parentElement);
            const border = over(rgba(style.borderTopColor), parentBg);
            const value = ratio(border, parentBg);
            if (value < 3) failures.push({ kind: 'input-border', el: label(el), ratio: +value.toFixed(2), need: 3 });
        }
    });

    return { checked: seen.length, min: Math.min(...seen).toFixed(2), failures };
};

/** Tab through `count` stops starting after `startSelector`; report focus visibility. */
async function tabThrough(page, startSelector, count, insideSelector) {
    if (startSelector) await page.evaluate((selector) => document.querySelector(selector).focus(), startSelector);
    const stops = [];

    for (let i = 0; i < count; i++) {
        await page.keyboard.press('Tab');
        const stop = await page.evaluate((inside) => {
            const el = document.activeElement;
            if (!el || el === document.body) return null;
            const s = getComputedStyle(el);
            const name = el.getAttribute('aria-label') || el.labels?.[0]?.textContent || el.textContent || el.placeholder || el.name || '';
            const indicator = (s.outlineStyle !== 'none' && parseFloat(s.outlineWidth) > 0) || (s.boxShadow && s.boxShadow !== 'none');

            return {
                tag: el.tagName.toLowerCase() + (el.name ? `[${el.name}]` : ''),
                name: name.trim().replace(/\s+/g, ' ').slice(0, 40),
                inside: !!el.closest(inside),
                outline: indicator || (s.backgroundColor !== 'rgba(0, 0, 0, 0)' && !/^oklch\(0\.9[89]\d* 0 0\)$/.test(s.backgroundColor) === true),
                border: s.borderTopColor,
                bg: s.backgroundColor,
            };
        }, insideSelector);
        stops.push(stop);
    }

    return stops;
}

const browser = await chromium.launch({ channel: 'chrome', headless: true });
const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
const page = await context.newPage();
const report = [];

// ---------- public form ----------
await page.goto(`${BASE}/id`, { waitUntil: 'load' });
await page.evaluate(() => { document.documentElement.style.scrollBehavior = 'auto'; window.scrollTo(0, document.getElementById('contact').getBoundingClientRect().top + scrollY - 72); });
await sleep(2500);

const publicContrast = await page.evaluate(contrastScan, '#contact');
report.push(['FORM contrast (ID)', publicContrast]);

// error state contrast
await page.evaluate(() => document.querySelectorAll('#contact form [required], #contact form [minlength]').forEach((el) => { el.removeAttribute('required'); el.removeAttribute('minlength'); }));
await page.evaluate(() => { document.querySelector('#contact [name="email"]').type = 'text'; });
await page.fill('#contact [name="name"]', 'Budi');
await page.fill('#contact [name="email"]', 'salah');
await page.fill('#contact [name="message"]', 'x');
await page.click('#contact button[type="submit"]', { force: true });
await sleep(1500);
report.push(['FORM contrast (error state)', await page.evaluate(contrastScan, '#contact')]);

await page.goto(`${BASE}/id`, { waitUntil: 'load' });
await sleep(1500);
const stops = await tabThrough(page, '#contact a', 12, '#contact');
report.push(['FORM keyboard stops', stops.filter((s) => s?.inside)]);

// submit with the keyboard only: focus name, type, Tab through, Enter on the button
await page.focus('#contact-name');
await page.keyboard.type('Keyboard Tester');
await page.keyboard.press('Tab');
await page.keyboard.type('keyboard@tester.test');
await page.keyboard.press('Tab');
await page.keyboard.press('Tab');
await page.keyboard.press('Tab');
await page.keyboard.press('Tab');
await page.keyboard.type('Pesan uji dari tes keyboard, minimal sepuluh karakter.');
await page.keyboard.press('Tab');
await page.keyboard.press('Enter');
await sleep(2500);
report.push(['FORM keyboard submit', await page.evaluate(() => ({ status: document.querySelector('#contact [role="status"]:not([style*="display: none"])')?.textContent?.trim().slice(0, 80) ?? null, focusedIsLiveRegion: document.activeElement?.getAttribute('role') === 'status' }))]);

// ---------- admin ----------
await page.goto(`${BASE}/admin/login`, { waitUntil: 'load' });
await page.fill('input[type="email"]', process.env.ADMIN_EMAIL);
await page.fill('input[type="password"]', process.env.ADMIN_PASSWORD);
await page.click('button[type="submit"]');
await page.waitForURL(/\/admin(?!\/login)/);

for (const [name, path] of [['ADMIN list', '/admin/contact-messages'], ['ADMIN detail', null]]) {
    if (path) {
        await page.goto(`${BASE}${path}`, { waitUntil: 'networkidle' });
    } else {
        await page.locator('table tbody tr').first().click();
        await page.waitForURL(/contact-messages\/\d+/);
    }
    await sleep(800);
    report.push([`${name} contrast`, await page.evaluate(contrastScan, 'main, .fi-main')]);
}

await page.goto(`${BASE}/admin/contact-messages`, { waitUntil: 'networkidle' });
await sleep(800);
await page.locator('input[placeholder="Cari"]').last().focus(); // table search: the sidebar is Filament core
const adminStops = await tabThrough(page, null, 16, 'main, .fi-main');
report.push(['ADMIN list keyboard stops', adminStops.filter(Boolean)]);

for (const [title, data] of report) {
    console.log(`\n== ${title}`);
    console.log(JSON.stringify(data, null, 1).slice(0, 4500));
}

await browser.close();
