import { chromium } from 'playwright-core';
const browser = await chromium.launch({ channel: 'chrome' });
const page = await browser.newPage({ viewport: { width: 1440, height: 1400 } });
await page.goto('http://127.0.0.1:8000/services', { waitUntil: 'networkidle' });
await page.waitForTimeout(800);

// zoom on the elbow join
await page.screenshot({ path: 'docs/screenshots/bagian-1a/connector-elbow.png', clip: { x: 650, y: 980, width: 300, height: 130 } });
// zoom on where it meets the ribbon icon
await page.screenshot({ path: 'docs/screenshots/bagian-1a/connector-ribbon-end.png', clip: { x: 90, y: 1030, width: 300, height: 100 } });

await browser.close();
