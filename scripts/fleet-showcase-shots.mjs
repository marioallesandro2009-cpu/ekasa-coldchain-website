import { chromium } from 'playwright-core';

const OUT = 'docs/screenshots/tahap-3-blok';
const URL = 'http://127.0.0.1:8000/services/linehaul-transport';

const browser = await chromium.launch({ channel: 'chrome' });
const errors = [];

async function shot(width, height, label) {
  const page = await browser.newPage({ viewport: { width, height } });
  page.on('console', (msg) => { if (msg.type() === 'error') errors.push(`[${label}] console: ${msg.text()}`); });
  page.on('pageerror', (err) => errors.push(`[${label}] pageerror: ${err.message}`));
  await page.goto(URL, { waitUntil: 'networkidle' });
  await page.waitForTimeout(400);

  const showcase = page.locator('#katalog-armada');
  await showcase.scrollIntoViewIfNeeded();
  await page.waitForTimeout(600); // let entrance animation settle

  // Truck 1 (initial state)
  await showcase.screenshot({ path: `${OUT}/${label}-truk1.png` });

  // Click "next" arrow to go to truck 2
  const nextBtn = page.getByRole('button', { name: /next truck/i });
  await nextBtn.click();
  await page.waitForTimeout(900); // 600ms slide transition + margin

  await showcase.screenshot({ path: `${OUT}/${label}-truk2.png` });

  // Also grab a close-up of the nav arrow on hover to verify the semi-transparent -> solid affordance
  await nextBtn.hover();
  await page.waitForTimeout(200);
  await showcase.screenshot({ path: `${OUT}/${label}-hover-panah.png` });

  await page.close();
}

await shot(1440, 900, 'showcase-1440');
await shot(390, 844, 'showcase-390');

await browser.close();

if (errors.length) {
  console.log('ERRORS:');
  console.log(errors.join('\n'));
  process.exit(1);
} else {
  console.log('OK, no console/page errors');
}
