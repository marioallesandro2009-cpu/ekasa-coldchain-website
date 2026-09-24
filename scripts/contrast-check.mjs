/**
 * Worst-case WCAG contrast of the hero subtext on every slide photo.
 *
 *   node scripts/contrast-check.mjs [baseUrl]
 *
 * Hides the text, captures the background behind it and compares the text
 * colour (white 85%) against the brightest background pixel.
 */
import { chromium } from 'playwright-core';

const BASE = process.argv[2] ?? 'http://127.0.0.1:8000';
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

const browser = await chromium.launch({ channel: 'chrome', headless: true });
const results = [];

for (const viewport of [{ width: 1440, height: 900 }, { width: 1024, height: 768 }, { width: 768, height: 1024 }, { width: 390, height: 844 }]) {
    const page = await browser.newPage({ viewport, reducedMotion: 'reduce' });
    await page.goto(BASE + '/', { waitUntil: 'load' });
    await sleep(1500);

    const labels = await page.$$eval('[aria-label^="Show "]', (buttons) => buttons.map((b) => b.getAttribute('aria-label')));

    for (const label of labels) {
        await page.evaluate((name) => document.querySelector(`[aria-label="${name}"]`).click(), label);
        await sleep(1200);

        const box = await page.evaluate(() => {
            const p = document.querySelector('#home p.max-w-\\[560px\\]');
            p.style.color = 'transparent';
            const r = p.getBoundingClientRect();
            return { x: r.x, y: r.y, width: r.width, height: r.height };
        });

        const png = await page.screenshot({ clip: box });
        const worst = await page.evaluate(async (base64) => {
            const blob = await (await fetch(`data:image/png;base64,${base64}`)).blob();
            const bitmap = await createImageBitmap(blob);
            const canvas = new OffscreenCanvas(bitmap.width, bitmap.height);
            const ctx = canvas.getContext('2d');
            ctx.drawImage(bitmap, 0, 0);
            const data = ctx.getImageData(0, 0, bitmap.width, bitmap.height).data;

            const lin = (c) => ((c /= 255) <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4);
            const lum = (r, g, b) => 0.2126 * lin(r) + 0.7152 * lin(g) + 0.0722 * lin(b);

            let worstRatio = Infinity;
            for (let i = 0; i < data.length; i += 4) {
                const [r, g, b] = [data[i], data[i + 1], data[i + 2]];
                // Text colour = white at 85% over this pixel.
                const text = lum(r + (255 - r) * 0.85, g + (255 - g) * 0.85, b + (255 - b) * 0.85);
                const ratio = (text + 0.05) / (lum(r, g, b) + 0.05);
                worstRatio = Math.min(worstRatio, ratio);
            }

            return Math.round(worstRatio * 100) / 100;
        }, png.toString('base64'));

        await page.evaluate(() => (document.querySelector('#home p.max-w-\\[560px\\]').style.color = ''));
        results.push({ viewport: `${viewport.width}x${viewport.height}`, slide: label.replace('Show ', ''), worstContrast: worst });
    }

    await page.close();
}

await browser.close();
console.log(JSON.stringify(results, null, 2));
