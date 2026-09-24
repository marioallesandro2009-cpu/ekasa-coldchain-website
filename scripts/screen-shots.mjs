/**
 * Screenshots of the first screen (hero, mega menu, navbar).
 *
 *   node scripts/screen-shots.mjs <folder> [baseUrl]
 *
 * Saves: hero 1440x900 (every slide), hero 1366x650, hero 390x844,
 * mega menu open 1440x900, navbar after scrolling 1440x900.
 */
import { mkdirSync } from 'node:fs';
import { chromium } from 'playwright-core';

const OUT = process.argv[2] ?? 'docs/screenshots/layar-pertama';
const BASE = process.argv[3] ?? 'http://127.0.0.1:8000';
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

mkdirSync(OUT, { recursive: true });
const browser = await chromium.launch({ channel: 'chrome', headless: true });
const errors = [];

async function open(width, height) {
    const page = await browser.newPage({ viewport: { width, height }, deviceScaleFactor: 1 });
    page.on('pageerror', (err) => errors.push(`${width}x${height}: ${err.message}`));
    page.on('console', (msg) => msg.type() === 'error' && errors.push(`${width}x${height}: ${msg.text()}`));
    await page.goto(BASE + '/', { waitUntil: 'load' });
    await page.mouse.move(width - 2, height - 2);
    await sleep(3500); // entrance animations, count-up and deferred photos
    return page;
}

// Hero 1440x900, every slide (switched through Alpine, waits for the crossfade).
{
    const page = await open(1440, 900);
    const count = await page.$$eval('[data-progress]', (bars) => bars.length);

    for (let i = 0; i < Math.max(count, 1); i++) {
        if (i > 0) {
            await page.evaluate((i) => window.Alpine.$data(document.querySelector('.hero')).goTo(i), i);
            await sleep(1600);
        }
        await page.screenshot({ path: `${OUT}/hero-1440x900-slide-${i + 1}.png` });
    }

    await page.evaluate(() => document.querySelector('[aria-controls="mega-menu-services"]').click());
    await sleep(500);
    await page.screenshot({ path: `${OUT}/mega-menu-open-1440x900.png` });

    await page.evaluate(() => window.Alpine.$data(document.querySelector('.site-header').parentElement).closeMega());
    await page.evaluate(() => window.scrollTo(0, 400));
    await sleep(800);
    await page.screenshot({ path: `${OUT}/navbar-scrolled-1440x900.png` });
    await page.close();
}

for (const [width, height] of [[1366, 650], [390, 844]]) {
    const page = await open(width, height);
    await page.screenshot({ path: `${OUT}/hero-${width}x${height}.png` });
    await page.close();
}

await browser.close();
console.log(JSON.stringify({ folder: OUT, errors }, null, 2));
