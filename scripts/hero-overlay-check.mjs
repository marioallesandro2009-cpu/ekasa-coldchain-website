/**
 * Hero overlay check, for any photo the admin may upload.
 *
 *   node scripts/hero-overlay-check.mjs [baseUrl]
 *
 * 1. Worst-case WCAG contrast of the subtext (white 85%) over the darkest overlay
 *    result, for every slide photo, a plain white image and a very bright image.
 * 2. Edge check: on the plain white image, the largest brightness step between
 *    two columns 4px apart across the width (a hard vertical edge shows up as a
 *    large step; a smooth scrim stays small).
 */
import { chromium } from 'playwright-core';

const BASE = process.argv[2] ?? 'http://127.0.0.1:8000';
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
const SUBTEXT = '#home p[class*="max-w-[560px]"]';

const browser = await chromium.launch({ channel: 'chrome', headless: true });
const results = [];

async function worstContrast(page, clip) {
    const png = await page.screenshot({ clip });
    return page.evaluate(async (base64) => {
        const bitmap = await createImageBitmap(await (await fetch(`data:image/png;base64,${base64}`)).blob());
        const canvas = new OffscreenCanvas(bitmap.width, bitmap.height);
        const ctx = canvas.getContext('2d');
        ctx.drawImage(bitmap, 0, 0);
        const data = ctx.getImageData(0, 0, bitmap.width, bitmap.height).data;
        const lin = (c) => ((c /= 255) <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4);
        const lum = (r, g, b) => 0.2126 * lin(r) + 0.7152 * lin(g) + 0.0722 * lin(b);
        let worst = Infinity;
        for (let i = 0; i < data.length; i += 4) {
            const [r, g, b] = [data[i], data[i + 1], data[i + 2]];
            const text = lum(r + (255 - r) * 0.85, g + (255 - g) * 0.85, b + (255 - b) * 0.85);
            worst = Math.min(worst, (text + 0.05) / (lum(r, g, b) + 0.05));
        }
        return Math.round(worst * 100) / 100;
    }, png.toString('base64'));
}

async function useGeneratedImage(page, kind) {
    await page.evaluate((kind) => {
        const canvas = document.createElement('canvas');
        canvas.width = 1600;
        canvas.height = 900;
        const ctx = canvas.getContext('2d');
        if (kind === 'white') {
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, 1600, 900);
        } else {
            const g = ctx.createLinearGradient(0, 0, 1600, 900);
            g.addColorStop(0, '#ffffff');
            g.addColorStop(1, '#e3ebf2');
            ctx.fillStyle = g;
            ctx.fillRect(0, 0, 1600, 900);
            for (let i = 0; i < 4000; i++) {
                ctx.fillStyle = `rgba(${200 + Math.random() * 55},${210 + Math.random() * 45},${220 + Math.random() * 35},0.8)`;
                ctx.fillRect(Math.random() * 1600, Math.random() * 900, 6, 6);
            }
        }
        const url = canvas.toDataURL('image/png');
        document.querySelectorAll('.hero-photo').forEach((img) => {
            img.removeAttribute('srcset');
            img.src = url;
            img.classList.remove('is-zooming');
        });
    }, kind);
    await sleep(400);
}

/** Largest luminance step between columns 4px apart, along a row at 45% of the hero height. */
async function largestStep(page, width) {
    const y = await page.evaluate(() => Math.round(document.getElementById('home').getBoundingClientRect().height * 0.45));
    const png = await page.screenshot({ clip: { x: 0, y, width, height: 1 } });
    return page.evaluate(async (base64) => {
        const bitmap = await createImageBitmap(await (await fetch(`data:image/png;base64,${base64}`)).blob());
        const canvas = new OffscreenCanvas(bitmap.width, 1);
        const ctx = canvas.getContext('2d');
        ctx.drawImage(bitmap, 0, 0);
        const d = ctx.getImageData(0, 0, bitmap.width, 1).data;
        const l = (i) => 0.2126 * d[i] + 0.7152 * d[i + 1] + 0.0722 * d[i + 2];
        let max = 0;
        for (let x = 4; x < bitmap.width; x++) max = Math.max(max, Math.abs(l(x * 4) - l((x - 4) * 4)));
        return Math.round(max * 10) / 10;
    }, png.toString('base64'));
}

for (const [width, height] of [[1920, 1080], [1440, 900], [1366, 650], [1280, 800], [1024, 768], [768, 1024], [390, 844]]) {
    const page = await browser.newPage({ viewport: { width, height }, reducedMotion: 'reduce' });
    await page.goto(BASE + '/', { waitUntil: 'load' });
    await sleep(1500);

    const row = { viewport: `${width}x${height}`, photos: [] };
    const count = await page.$$eval('[data-progress]', (bars) => bars.length);

    for (let i = 0; i < Math.max(count, 1); i++) {
        if (i > 0) await page.evaluate((i) => window.Alpine.$data(document.querySelector('.hero')).goTo(i), i);
        await sleep(1300);
        const clip = await page.evaluate((sel) => {
            const p = document.querySelector(sel);
            p.style.color = 'transparent';
            const r = p.getBoundingClientRect();
            return { x: r.x, y: r.y, width: r.width, height: r.height };
        }, SUBTEXT);
        row.photos.push(await worstContrast(page, clip));
        await page.evaluate((sel) => (document.querySelector(sel).style.color = ''), SUBTEXT);
    }

    for (const kind of ['bright', 'white']) {
        await useGeneratedImage(page, kind);
        const clip = await page.evaluate((sel) => {
            const p = document.querySelector(sel);
            p.style.color = 'transparent';
            const r = p.getBoundingClientRect();
            return { x: r.x, y: r.y, width: r.width, height: r.height };
        }, SUBTEXT);
        row[kind] = await worstContrast(page, clip);
        await page.evaluate((sel) => (document.querySelector(sel).style.color = ''), SUBTEXT);
    }

    // Hide the text before measuring the edge, so only the overlay is sampled.
    await page.addStyleTag({ content: '#home .container-site, #home dl { visibility: hidden !important; }' });
    row.largestStepOnWhite = await largestStep(page, width);
    row.passesAA = Math.min(...row.photos, row.white, row.bright) >= 4.5;

    results.push(row);
    await page.close();
}

await browser.close();
console.log(JSON.stringify(results, null, 2));
if (results.some((row) => !row.passesAA)) process.exitCode = 1;
