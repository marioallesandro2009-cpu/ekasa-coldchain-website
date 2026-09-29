import { chromium } from 'playwright-core';
const browser = await chromium.launch({ channel: 'chrome' });
const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
await page.goto('http://127.0.0.1:8000/services', { waitUntil: 'networkidle' });
await page.waitForTimeout(800);

const cards = await page.locator('main article').all();
console.log('card count', cards.length);

for (let i = 0; i < Math.min(cards.length, 2); i++) {
  const card = cards[i];
  const numberSpan = card.locator('span[aria-hidden="true"]').first();
  const box = await numberSpan.boundingBox();
  const styles = await numberSpan.evaluate((el) => {
    const cs = getComputedStyle(el);
    return { color: cs.color, opacity: cs.opacity, mixBlendMode: cs.mixBlendMode, fontSize: cs.fontSize, className: el.className };
  });
  console.log(`card ${i}`, JSON.stringify(styles), 'box', box);
}

await page.screenshot({ path: 'docs/screenshots/bagian-1a/full-page.png', fullPage: false });
await browser.close();
