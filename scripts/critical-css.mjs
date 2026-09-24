/**
 * NOT IN USE. Critical CSS was measured as neutral locally (FCP +0.1 s with
 * gzip) and is switched off; the layout loads app.css with a normal <link>.
 * Keep this script to try again on the hosting: if PageSpeed Insights there
 * reports render-blocking CSS worth it, run it after every build and put back
 * in resources/views/layouts/app.blade.php, instead of @vite([...css, ...js]):
 *
 *   <style>{!! critical.css without its first line !!}</style>
 *   <link rel="preload" as="style" href="{{ Vite::asset('resources/css/app.css') }}"
 *         onload="this.onload=null;this.rel='stylesheet'">
 *   <noscript><link rel="stylesheet" href="{{ Vite::asset('resources/css/app.css') }}"></noscript>
 *   @vite('resources/js/app.js')
 *
 * (only when the file's first line names the current build's app.css).
 *
 * Critical CSS for the first screen (navbar + hero):
 *
 *   npm run build && node scripts/critical-css.mjs [baseUrl]
 *
 * Needs the site running (php artisan serve) and Google Chrome. Every rule of
 * the built app.css whose selector matches an element in the first screen, at
 * several screen sizes and in every language, is kept (states such as :hover
 * and :not(...) are ignored when matching, so an element keeps all its rules).
 * @layer, @media, @supports, @font-face, @property and the keyframes used are kept.
 *
 * Output: public/build/critical.css. Its first line names the stylesheet it was
 * made from; the layout ignores the file when it does not match the current
 * build (e.g. after "npm run build" without running this script again).
 */
import { readFileSync, writeFileSync } from 'node:fs';
import { gzipSync } from 'node:zlib';
import { chromium } from 'playwright-core';

const BASE = process.argv[2] ?? 'http://127.0.0.1:8000';
const URLS = ['/', '/id'];
const VIEWPORTS = [[360, 740], [390, 844], [768, 1024], [1024, 768], [1366, 650], [1440, 900], [1920, 1080]];
// First round trip: ~14 KiB compressed.
const LIMIT = 14 * 1024;

const manifest = JSON.parse(readFileSync('public/build/manifest.json', 'utf8'));
const cssFile = manifest['resources/css/app.css'].file;

const browser = await chromium.launch({ channel: 'chrome', headless: true });
const kept = new Set();
let serialize = null; // the kept rules as CSS text

for (const url of URLS) {
    for (const [width, height] of VIEWPORTS) {
        const page = await browser.newPage({ viewport: { width, height } });
        await page.goto(BASE + url, { waitUntil: 'load' });
        await page.waitForTimeout(1500);

        const result = await page.evaluate(({ cssFile }) => {
            const sheet = [...document.styleSheets].find((s) => s.href && s.href.endsWith(cssFile));
            if (!sheet) throw new Error('Stylesheet not found: ' + cssFile);

            // Elements in the first screen (plus the root elements). A hidden element
            // (closed mega menu, mobile menu) keeps its own rules, which hide it,
            // but not the rules of its contents.
            const vh = window.innerHeight;
            const visible = (el) => el.checkVisibility({ visibilityProperty: true, opacityProperty: false });
            const elements = [document.documentElement, document.body, ...document.querySelectorAll('body *')].filter((el) => {
                if (el === document.documentElement || el === document.body) return true;
                if (!visible(el) && !(el.parentElement && visible(el.parentElement))) return false;
                const r = el.getBoundingClientRect();
                return r.top < vh && r.bottom >= 0;
            });

            const STATE = /::?(hover|focus-visible|focus-within|focus|active|visited|target|checked|disabled|placeholder|before|after|marker|selection|backdrop|file-selector-button|-webkit-[\w-]+|-moz-[\w-]+)(\([^)]*\))?/g;
            const NOT = /:not\((?:[^()]|\([^()]*\))*\)/g;
            const tidy = (s) => s.trim().replace(/[>+~]\s*$/, '').replace(/(^|[\s>+~(,])(?=$|[\s>+~),])/g, '$1*') || '*';

            const matches = (selector) => {
                for (const candidate of [selector, tidy(selector.replace(STATE, '')), tidy(selector.replace(STATE, '').replace(NOT, ''))]) {
                    try {
                        if (elements.some((el) => el.matches(candidate))) return true;
                    } catch {
                        // Not a selector the browser can match: try the next form.
                    }
                }
                return false;
            };

            // "a, b" -> ["a", "b"]; commas that are escaped (Tailwind arbitrary values
            // such as .min-h-\[max\(100svh\,640px\)\]) or inside parentheses stay.
            const splitList = (text) => {
                const parts = [];
                let depth = 0;
                let current = '';
                for (let i = 0; i < text.length; i++) {
                    const ch = text[i];
                    if (ch === '\\') {
                        current += ch + (text[i + 1] ?? '');
                        i++;
                        continue;
                    }
                    if (ch === '(') depth++;
                    if (ch === ')') depth--;
                    if (ch === ',' && depth === 0) {
                        parts.push(current);
                        current = '';
                        continue;
                    }
                    current += ch;
                }
                return [...parts, current];
            };

            const found = [];
            const walk = (rules, path) => {
                [...rules].forEach((rule, i) => {
                    const p = path + '/' + i;
                    if (rule instanceof CSSStyleRule) {
                        if (splitList(rule.selectorText).some((part) => matches(part))) found.push(p);
                    } else if (rule.cssRules) {
                        walk(rule.cssRules, p);
                    }
                });
            };
            walk(sheet.cssRules, '');

            return { found };
        }, { cssFile });

        result.found.forEach((p) => kept.add(p));
        await page.close();
    }
}

// Serialize once with everything found on every page and screen size (keyframes only when a kept rule uses them).
{
    const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
    await page.goto(BASE + '/', { waitUntil: 'load' });
    await page.waitForTimeout(500);
    serialize = await page.evaluate(({ cssFile, keptPaths }) => {
        const sheet = [...document.styleSheets].find((s) => s.href && s.href.endsWith(cssFile));
        const keep = new Set(keptPaths);
        const used = new Set();
        const out = (rules, path) => {
            let css = '';
            [...rules].forEach((rule, i) => {
                const p = path + '/' + i;
                if (rule instanceof CSSStyleRule) {
                    if (keep.has(p)) {
                        css += rule.cssText;
                        for (const name of rule.cssText.matchAll(/animation(?:-name)?:\s*([\w-]+)/g)) used.add(name[1]);
                    }
                } else if (rule instanceof CSSFontFaceRule || rule instanceof CSSPropertyRule || rule.constructor.name === 'CSSLayerStatementRule') {
                    css += rule.cssText;
                } else if (rule instanceof CSSKeyframesRule) {
                    css += '\u0000' + rule.name + '\u0000' + rule.cssText + '\u0001';
                } else if (rule.cssRules) {
                    const inner = out(rule.cssRules, p);
                    if (inner.replace(/\u0000[^\u0001]*\u0001/g, '').trim() !== '') {
                        const head = rule.constructor.name === 'CSSLayerBlockRule' ? '@layer ' + rule.name
                            : rule.constructor.name === 'CSSMediaRule' ? '@media ' + rule.conditionText
                                : rule.constructor.name === 'CSSSupportsRule' ? '@supports ' + rule.conditionText
                                    : rule.cssText.slice(0, rule.cssText.indexOf('{')).trim();
                        css += head + '{' + inner + '}';
                    }
                }
            });
            return css;
        };
        const css = out(sheet.cssRules, '');
        return css.replace(/\u0000([\w-]+)\u0000([^\u0001]*)\u0001/g, (m, name, text) => (used.has(name) ? text : ''));
    }, { cssFile, keptPaths: [...kept] });
    await page.close();
}

await browser.close();

let css = serialize.replace(/\s*\n\s*/g, ' ').replace(/\s*([{};:,])\s*/g, '$1').replace(/;}/g, '}');

// Tailwind registers every --tw-* variable it knows (@property, plus a fallback
// block for older browsers); keep only the ones the kept rules use.
const withoutRegistrations = css.replace(/@property --tw-[\w-]+\{[^}]*\}/g, '').replace(/\*,::before,::after,::backdrop\{--tw-[^}]*\}/g, '');
const usedVars = new Set(withoutRegistrations.match(/--tw-[\w-]+/g) ?? []);
css = css
    .replace(/@property (--tw-[\w-]+)\{[^}]*\}/g, (rule, name) => (usedVars.has(name) ? rule : ''))
    .replace(/(\*,::before,::after,::backdrop\{)(--tw-[^}]*)\}/g, (block, head, body) => {
        const declarations = body.split(';').filter((d) => usedVars.has(d.slice(0, d.indexOf(':'))));
        return declarations.length ? head + declarations.join(';') + '}' : '';
    })
    .replace(/@supports [^{]*\{\}/g, '')
    .replace(/@layer properties\{\}/g, '');
const output = `/*! critical: ${cssFile} */\n${css}\n`;
writeFileSync('public/build/critical.css', output);

const size = Buffer.byteLength(output);
const gzip = gzipSync(output).length;
console.log(JSON.stringify({ source: cssFile, rules: kept.size, kib: +(size / 1024).toFixed(1), gzipKib: +(gzip / 1024).toFixed(1) }, null, 2));

if (gzip > LIMIT) {
    console.error(`critical.css is ${(gzip / 1024).toFixed(1)} KiB gzip (limit ${LIMIT / 1024} KiB)`);
    process.exitCode = 1;
}
