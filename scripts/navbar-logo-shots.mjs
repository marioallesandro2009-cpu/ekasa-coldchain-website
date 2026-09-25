/**
 * Navbar logo screenshots (before/after a logo size change) plus measurements.
 *
 *   node scripts/navbar-logo-shots.mjs <prefix> [baseUrl]
 *
 * Saves <prefix>-{transparan,solid}-1440.png, <prefix>-mobile-390.png and a
 * 3x close-up of the logo (<prefix>-zoom-{transparan,solid}.png); prints the
 * navbar and logo heights.
 */
import { mkdirSync } from 'node:fs';
import { dirname } from 'node:path';
import { chromium } from 'playwright-core';

const PREFIX = process.argv[2] ?? 'docs/screenshots/logo-navbar/sesudah';
const BASE = process.argv[3] ?? 'http://127.0.0.1:8000';
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

mkdirSync(dirname(PREFIX), { recursive: true });
const browser = await chromium.launch({ channel: 'chrome', headless: true });

const measure = (page) => page.evaluate(() => {
    const bar = document.querySelector('.nav-bar').getBoundingClientRect();
    const logo = document.querySelector('.site-header a[href="#home"] img').getBoundingClientRect();
    return { navbar: Math.round(bar.height), logoH: Math.round(logo.height), logoW: Math.round(logo.width), top: Math.round(logo.top - bar.top) };
});

async function shoot(width, height, scale, label, scrolled) {
    const page = await browser.newPage({ viewport: { width, height }, deviceScaleFactor: scale });
    await page.goto(BASE + '/', { waitUntil: 'load' });
    await sleep(3000);
    if (scrolled) {
        await page.evaluate(() => window.scrollTo(0, 400));
        await sleep(800);
    }
    const m = await measure(page);
    const clipHeight = m.navbar + 16;
    await page.screenshot({ path: `${PREFIX}-${label}.png`, clip: { x: 0, y: 0, width: Math.min(width, 720), height: clipHeight } });
    console.log(label, JSON.stringify(m));
    await page.close();
}

await shoot(1440, 900, 2, 'transparan-1440', false);
await shoot(1440, 900, 2, 'solid-1440', true);
await shoot(390, 844, 2, 'mobile-390', false);

// 3x close-up of the logo alone.
for (const [label, scrolled] of [['zoom-transparan', false], ['zoom-solid', true]]) {
    const page = await browser.newPage({ viewport: { width: 1440, height: 900 }, deviceScaleFactor: 3 });
    await page.goto(BASE + '/', { waitUntil: 'load' });
    await sleep(3000);
    if (scrolled) {
        await page.evaluate(() => window.scrollTo(0, 400));
        await sleep(800);
    }
    const box = await page.evaluate(() => {
        const r = document.querySelector('.site-header a[href="#home"]').getBoundingClientRect();
        return { x: r.x - 8, y: r.y - 8, width: r.width + 16, height: r.height + 16 };
    });
    await page.screenshot({ path: `${PREFIX}-${label}.png`, clip: box });
    await page.close();
}

await browser.close();
