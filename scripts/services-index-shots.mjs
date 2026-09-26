import { chromium } from 'playwright-core';

const browser = await chromium.launch({ channel: 'chrome' });
const OUT = 'docs/screenshots/services-index';
const sizes = [
  { w: 1366, h: 768, label: '1366x768' },
  { w: 1440, h: 900, label: '1440x900' },
  { w: 1536, h: 864, label: '1536x864' },
];

for (const { w, h, label } of sizes) {
  for (const [path, lang] of [['/services', 'en'], ['/id/layanan', 'id']]) {
    const page = await browser.newPage({ viewport: { width: w, height: h } });
    await page.goto('http://127.0.0.1:8000' + path, { waitUntil: 'networkidle' });
    await page.waitForTimeout(1500);
    const scrollHeight = await page.evaluate(() => document.documentElement.scrollHeight);
    const hasScroll = scrollHeight > h;
    console.log(`${label} ${lang}: scrollHeight=${scrollHeight} viewport=${h} overflow=${hasScroll}`);
    await page.screenshot({ path: `${OUT}/${label}-${lang}.png` });
    await page.close();
  }
}

// Mobile: single screenshot showing the vertical connector line.
const mobile = await browser.newPage({ viewport: { width: 390, height: 844 } });
await mobile.goto('http://127.0.0.1:8000/services', { waitUntil: 'networkidle' });
await mobile.waitForTimeout(1500);
await mobile.screenshot({ path: `${OUT}/390x844-en.png`, fullPage: true });
await mobile.close();

await browser.close();
