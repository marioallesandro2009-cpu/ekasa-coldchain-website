import { chromium } from 'playwright-core';
const browser = await chromium.launch({ channel: 'chrome' });
const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
await page.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle' });
await page.locator('#services').scrollIntoViewIfNeeded();
await page.waitForTimeout(1200);
await page.locator('#services').screenshot({ path: process.argv[2] || 'docs/screenshots/services-index/_home-before.png' });
await browser.close();
