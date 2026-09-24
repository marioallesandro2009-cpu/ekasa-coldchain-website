/**
 * Hero screenshots at the three reference sizes.
 *
 *   node scripts/hero-shots.mjs <folder> [baseUrl]
 *
 * Also saves a screenshot per slide label at 375/768/1280/1440 (headline line
 * count check) and prints the hero measurements as JSON.
 */
import { mkdirSync } from 'node:fs';
import { chromium } from 'playwright-core';

const OUT = process.argv[2] ?? 'docs/screenshots/hero';
const BASE = process.argv[3] ?? 'http://127.0.0.1:8000';
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

mkdirSync(OUT, { recursive: true });

const browser = await chromium.launch({ channel: 'chrome', headless: true });
const results = {};

async function open(width, height) {
    const context = await browser.newContext({ viewport: { width, height }, deviceScaleFactor: 1 });
    const page = await context.newPage();
    const errors = [];
    page.on('pageerror', (err) => errors.push(err.message));
    page.on('console', (msg) => msg.type() === 'error' && errors.push(msg.text()));
    await page.goto(BASE + '/', { waitUntil: 'load' });
    await sleep(3200);
    await page.mouse.move(width - 5, 5);
    return { context, page, errors };
}

for (const [width, height] of [[1440, 900], [1366, 650], [390, 844]]) {
    const { context, page, errors } = await open(width, height);
    await page.screenshot({ path: `${OUT}/hero-${width}x${height}.png` });
    results[`${width}x${height}`] = { errors };
    await context.close();
}

// Headline stability: line count and indicator position for every label.
for (const width of [375, 768, 1280, 1440]) {
    const { context, page } = await open(width, 900);
    const count = await page.$$eval('[data-progress]', (bars) => bars.length);
    const rows = [];

    for (let i = 0; i < count; i++) {
        await page.evaluate((i) => {
            const root = document.querySelector('.hero');
            window.Alpine.$data(root).goTo(i);
        }, i);
        await sleep(1300);
        rows.push(
            await page.evaluate(() => {
                const h1 = document.querySelector('#hero-title');
                const label = document.querySelector('h1 .rt-set');
                const cta = document.querySelector('.hero [href="#contact"]');
                const lineHeight = parseFloat(getComputedStyle(h1).lineHeight);
                return {
                    label: label?.textContent.replace(/\s+/g, ' ').trim(),
                    h1Height: Math.round(h1.getBoundingClientRect().height),
                    lines: Math.round(h1.getBoundingClientRect().height / lineHeight),
                    ctaTop: Math.round(cta.getBoundingClientRect().top),
                };
            }),
        );
        await page.screenshot({ path: `${OUT}/label-${width}-${i + 1}.png` });
    }

    const stable = new Set(rows.map((r) => `${r.h1Height}/${r.ctaTop}`)).size === 1;
    results[`labels-${width}`] = { stable, rows };
    await context.close();
}

await browser.close();
console.log(JSON.stringify(results, null, 2));
