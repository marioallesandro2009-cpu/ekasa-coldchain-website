/**
 * Browser checks + screenshots for the home page (stage 1-2 audit).
 *
 *   node scripts/browser-check.mjs [baseUrl]
 *
 * Uses the installed Google Chrome through playwright-core (headless).
 * Screenshots go to docs/screenshots/tahap-1-2/, results are printed as JSON.
 * Fails (exit code 1) when a /build/ asset referenced by the HTML does not
 * answer 200, when any request returns >= 400, or when a hero check fails.
 */
import { mkdirSync } from 'node:fs';
import { chromium } from 'playwright-core';

const BASE = process.argv[2] ?? 'http://127.0.0.1:8000';
const OUT = 'docs/screenshots/tahap-1-2';
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

mkdirSync(OUT, { recursive: true });

const browser = await chromium.launch({ channel: 'chrome', headless: true });
const results = {};
// Every check that fails is listed here; the script exits with code 1 if any.
const failures = [];
// Every 4xx/5xx response seen by any page during the checks.
const badResponses = new Set();

// 0. Build assets: every CSS/JS/font file in /build/ referenced by the HTML must answer 200.
{
    const html = await (await fetch(BASE + '/', { headers: { 'Cache-Control': 'no-cache' } })).text();
    const urls = [...new Set([...html.matchAll(/(?:href|src)="([^"]*\/build\/[^"]+)"/g)].map((m) => new URL(m[1], BASE).href))];
    results.buildAssets = [];

    for (const url of urls) {
        const response = await fetch(url);
        await response.arrayBuffer(); // read the body: php -S closes the connection
        results.buildAssets.push({ url: url.replace(BASE, ''), status: response.status });
        if (response.status !== 200) failures.push(`build asset ${url.replace(BASE, '')} -> ${response.status}`);
    }

    if (!urls.some((url) => url.endsWith('.css')) || !urls.some((url) => url.endsWith('.js'))) {
        failures.push('HTML does not reference a CSS and a JS file from /build/');
    }

    // Without its CSS/JS the page cannot be checked further: stop here.
    if (failures.length) {
        await browser.close();
        console.log(JSON.stringify({ buildAssets: results.buildAssets, failures }, null, 2));
        process.exit(1);
    }
}

async function open(viewport, options = {}) {
    const context = await browser.newContext({ viewport, deviceScaleFactor: 1, ...options });
    const page = await context.newPage();
    const errors = [];
    page.on('console', (msg) => msg.type() === 'error' && errors.push(msg.text()));
    page.on('pageerror', (err) => errors.push(err.message));
    page.on('response', (response) => response.status() >= 400 && badResponses.add(`${response.status()} ${response.url().replace(BASE, '')}`));
    await page.goto(BASE + '/', { waitUntil: 'load' });
    await sleep(1500);
    return { context, page, errors };
}

// 1. Screenshots -------------------------------------------------------------
{
    const { context, page, errors } = await open({ width: 1440, height: 900 });
    await page.screenshot({ path: `${OUT}/hero-desktop-1440.png` });

    // Hero: no chevron, left edges aligned, navbar CTA outlined at scroll 0.
    results.hero = await page.evaluate(() => {
        const left = (el) => Math.round(el.getBoundingClientRect().left);
        const cta = getComputedStyle(document.querySelector('.site-header .nav-cta'));
        return {
            // Lucide chevron-down anywhere in the hero section.
            chevron: document.querySelectorAll('#home .animate-chevron, #home path[d="m6 9 6 6 6-6"]').length,
            leftEdges: {
                logo: left(document.querySelector('.site-header a[href="#home"]')),
                headline: left(document.querySelector('#hero-title')),
                firstStatColumn: left(document.querySelector('#home dl > div')),
            },
            ctaAtTop: { scrollY: window.scrollY, background: cta.backgroundColor, color: cta.color, outline: cta.boxShadow },
        };
    });
    const { chevron, leftEdges, ctaAtTop } = results.hero;
    if (chevron !== 0) failures.push('hero chevron is still rendered');
    if (new Set(Object.values(leftEdges)).size !== 1) failures.push(`hero left edges differ: ${JSON.stringify(leftEdges)}`);
    if (ctaAtTop.background !== 'rgba(0, 0, 0, 0)' || !/1 1 1 \/ 0\.6|255, 255, 255, 0\.6/.test(ctaAtTop.outline)) {
        failures.push(`navbar CTA is not a white outline at scroll 0: ${JSON.stringify(ctaAtTop)}`);
    }

    const labels = await page.$$eval('[aria-label^="Show "]', (buttons) => buttons.map((b) => b.getAttribute('aria-label')));
    results.slides = [];

    for (let i = 0; i < labels.length; i++) {
        await page.click(`[aria-label="${labels[i]}"]`);
        await sleep(2200);
        await page.mouse.move(1400, 850);
        results.slides.push(
            await page.evaluate(() => ({
                current: document.querySelector('[aria-current="true"]')?.getAttribute('aria-label'),
                label: document.querySelector('h1 .rt-set')?.textContent.replace(/\s+/g, ' ').trim(),
                photo: document.querySelector('.hero-photo.is-active')?.dataset.index,
            })),
        );
        await page.screenshot({ path: `${OUT}/hero-slide-${i + 1}.png` });
    }

    // Event log for the mega menu (kept in the results only when the check fails).
    await page.evaluate(() => {
        window.__megaLog = [];
        const t0 = performance.now();
        const li = document.querySelector('li.mega');
        const btn = document.querySelector('[aria-controls="mega-menu-services"]');
        const log = (e) => window.__megaLog.push(`${Math.round(performance.now() - t0)}ms ${e.type} ${e.pointerType ?? ''} (${e.clientX},${e.clientY})`);
        ['pointerenter', 'pointerleave', 'pointerover', 'focusin', 'focusout'].forEach((type) => li.addEventListener(type, log));
        new MutationObserver(() =>
            window.__megaLog.push(`${Math.round(performance.now() - t0)}ms aria-expanded=${btn.getAttribute('aria-expanded')}`),
        ).observe(btn, { attributes: true, attributeFilter: ['aria-expanded'] });
    });

    await page.mouse.move(700, 40);
    await page.hover('[aria-controls="mega-menu-services"]');
    await sleep(400);
    results.megaHover = await page.evaluate(() => ({
        expanded: document.querySelector('[aria-controls="mega-menu-services"]').getAttribute('aria-expanded'),
        visible: getComputedStyle(document.getElementById('mega-menu-services')).display,
        items: document.querySelectorAll('#mega-menu-services li').length,
    }));
    await page.screenshot({ path: `${OUT}/mega-menu-open-1440.png` });
    await page.mouse.move(700, 880);
    await sleep(500);
    results.megaHoverClose = await page.evaluate(() =>
        document.querySelector('[aria-controls="mega-menu-services"]').getAttribute('aria-expanded'),
    );
    if (results.megaHoverClose !== 'false') {
        results.megaLog = await page.evaluate(() => window.__megaLog);
    }

    await page.evaluate(() => window.scrollTo(0, 300));
    await sleep(600);
    results.scrolled = await page.evaluate(() => ({
        solid: document.querySelector('.site-header').classList.contains('is-solid'),
        height: Math.round(document.querySelector('.nav-bar').getBoundingClientRect().height),
    }));
    await page.screenshot({ path: `${OUT}/navbar-scrolled-1440.png` });

    await page.click('header a[href="#clients"]');
    await sleep(1500);
    results.scrollspy = await page.evaluate(() => ({
        active: [...document.querySelectorAll('header .nav-link.is-active')].map((a) => a.textContent.trim()),
        underline: getComputedStyle(document.querySelector('header .nav-link.is-active'), '::after').backgroundColor,
        textColor: getComputedStyle(document.querySelector('header .nav-link.is-active')).color,
        clientsTop: Math.round(document.getElementById('clients').getBoundingClientRect().top),
    }));

    results.consoleErrorsDesktop = errors;
    await context.close();
}

{
    const { context, page, errors } = await open({ width: 390, height: 844 }, { hasTouch: true, isMobile: true });
    await page.screenshot({ path: `${OUT}/hero-mobile-390.png` });
    await page.tap('[aria-controls="mobile-menu"]');
    await sleep(1100);
    results.mobileMenu = await page.evaluate(() => ({
        expanded: document.querySelector('[aria-controls="mobile-menu"]').getAttribute('aria-expanded'),
        open: getComputedStyle(document.getElementById('mobile-menu')).display,
        bodyLocked: document.documentElement.classList.contains('overflow-hidden'),
        focus: document.activeElement?.getAttribute('aria-label'),
    }));
    await page.tap('.mm-details summary');
    await sleep(400);
    await page.screenshot({ path: `${OUT}/mobile-menu-open-390.png` });
    await page.keyboard.press('Escape');
    await sleep(500);
    results.mobileMenuEsc = await page.evaluate(() => ({
        open: getComputedStyle(document.getElementById('mobile-menu')).display,
        bodyLocked: document.documentElement.classList.contains('overflow-hidden'),
    }));
    results.consoleErrorsMobile = errors;
    await context.close();
}

{
    const { context, page } = await open({ width: 1440, height: 900 }, { javaScriptEnabled: false });
    await page.screenshot({ path: `${OUT}/hero-no-js-1440.png` });
    results.noJs = await page.evaluate(() => ({
        h1: document.querySelector('h1').innerText.replace(/\s+/g, ' '),
        stats: [...document.querySelectorAll('dd')].map((d) => d.innerText.trim()),
        heroPhotoOpacity: getComputedStyle(document.querySelector('.hero-photo')).opacity,
        headerBg: getComputedStyle(document.querySelector('.site-header')).backgroundColor,
        indicatorsHidden: getComputedStyle(document.querySelector('.js-only')).display === 'none',
    }));
    await page.hover('header .mega > a');
    await sleep(300);
    results.noJs.megaOnHover = await page.evaluate(() => getComputedStyle(document.getElementById('mega-menu-services')).display);
    await context.close();
}

// 2. Layout at the required sizes -------------------------------------------
results.layout = {};
for (const [width, height] of [[375, 812], [390, 844], [768, 1024], [1024, 768], [1280, 800], [1366, 650], [1440, 900], [1920, 1080]]) {
    const { context, page, errors } = await open({ width, height });
    results.layout[`${width}x${height}`] = await page.evaluate(() => {
        const overflow = [...document.querySelectorAll('body *')]
            .filter((el) => {
                const rect = el.getBoundingClientRect();
                return rect.right > window.innerWidth + 1 && !el.closest('.hero-photo, [aria-hidden="true"], .mobile-menu, .mega-panel');
            })
            .slice(0, 3)
            .map((el) => el.tagName + '.' + String(el.className).slice(0, 40));
        const clipped = [...document.querySelectorAll('#home h1, #home p, #home a, #home dt, #home dd')].filter(
            (el) => el.scrollWidth > el.clientWidth + 1 && getComputedStyle(el).overflow !== 'visible',
        ).length;

        return {
            scrollX: document.documentElement.scrollWidth - window.innerWidth,
            heroHeight: document.getElementById('home').offsetHeight,
            viewport: window.innerHeight,
            statsBottom: Math.round(document.querySelector('#home dl')?.getBoundingClientRect().bottom ?? 0),
            overflow,
            clipped,
        };
    });
    results.layout[`${width}x${height}`].errors = errors.length;
    await context.close();
}

// 3. Keyboard -----------------------------------------------------------------
{
    const { context, page } = await open({ width: 1440, height: 900 });
    const stops = [];
    for (let i = 0; i < 20; i++) {
        await page.keyboard.press('Tab');
        stops.push(
            await page.evaluate(() => {
                const el = document.activeElement;
                const style = getComputedStyle(el);
                return `${el.tagName}:${(el.getAttribute('aria-label') || el.textContent).trim().slice(0, 24)}|outline=${style.outlineStyle}`;
            }),
        );
    }
    results.keyboardTabOrder = stops;

    await page.focus('[aria-controls="mega-menu-services"]');
    await page.keyboard.press('ArrowDown');
    await sleep(300);
    results.keyboardMega = await page.evaluate(() => ({
        expanded: document.querySelector('[aria-controls="mega-menu-services"]').getAttribute('aria-expanded'),
        focused: document.activeElement.textContent.trim().slice(0, 30),
    }));
    await page.keyboard.press('Escape');
    await sleep(200);
    results.keyboardMegaEsc = await page.evaluate(() => ({
        expanded: document.querySelector('[aria-controls="mega-menu-services"]').getAttribute('aria-expanded'),
        focusOnTrigger: document.activeElement === document.querySelector('[aria-controls="mega-menu-services"]'),
    }));
    await context.close();
}

// 4. Reduced motion --------------------------------------------------------------
{
    const { context, page } = await open({ width: 1440, height: 900 }, { reducedMotion: 'reduce' });
    results.reducedMotion = await page.evaluate(() => ({
        blurWords: document.querySelectorAll('.blur-word').length,
        zooming: document.querySelectorAll('.hero-photo.is-zooming').length,
        stat: document.querySelector('dd [x-data]')?.textContent,
        slideDuration: getComputedStyle(document.getElementById('home')).getPropertyValue('--slide-duration'),
        animatedHidden: [...document.querySelectorAll('[data-animate]')].filter((el) => getComputedStyle(el).opacity !== '1').length,
    }));
    await context.close();
}

await browser.close();

results.badResponses = [...badResponses];
if (badResponses.size) failures.push(`${badResponses.size} response(s) with status >= 400`);
results.failures = failures;

console.log(JSON.stringify(results, null, 2));
if (failures.length) process.exitCode = 1;
