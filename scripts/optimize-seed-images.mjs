/**
 * Shrinks the seeder photos (database/seeders/images/*.jpg) with WebAssembly
 * codecs only (@jsquash/jpeg = MozJPEG, @jsquash/resize), no native library.
 *
 *   node scripts/optimize-seed-images.mjs
 *
 * - The original is moved to database/seeders/images/_original/ (git-ignored)
 *   and is always the source, so the script can be run again safely.
 * - Replacing a photo: put the new file here AND delete its copy in _original/,
 *   otherwise the old original is used again and the new photo is overwritten.
 * - Output: JPG, quality 82, at most 2400 px wide. A hero photo that is still
 *   500 kB (500,000 bytes) or more is made narrower in 100 px steps, not below
 *   1600 px (very detailed photos, such as aerial snow, need ~1700 px at q82).
 */
import { existsSync, mkdirSync, readdirSync, readFileSync, renameSync, statSync, writeFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import { join } from 'node:path';
import decode, { init as initDecode } from '@jsquash/jpeg/decode.js';
import encode, { init as initEncode } from '@jsquash/jpeg/encode.js';
import resize, { initResize } from '@jsquash/resize';

const DIR = 'database/seeders/images';
const ORIGINALS = join(DIR, '_original');
const MAX_WIDTH = 2400;
const MIN_HERO_WIDTH = 1600;
const WIDTH_STEP = 100;
const QUALITY = 82;
const HERO_MAX_BYTES = 500_000;

// Node has no ImageData; the codecs only need this shape.
globalThis.ImageData ??= class ImageData {
    constructor(data, width, height) {
        this.data = data;
        this.width = width;
        this.height = height;
    }
};

// Load the .wasm files from node_modules (the default loaders expect a browser fetch).
const require = createRequire(import.meta.url);
const wasm = (file) => WebAssembly.compile(readFileSync(require.resolve(file)));
await initDecode(await wasm('@jsquash/jpeg/codec/dec/mozjpeg_dec.wasm'));
await initEncode(await wasm('@jsquash/jpeg/codec/enc/mozjpeg_enc.wasm'));
await initResize(await wasm('@jsquash/resize/lib/resize/pkg/squoosh_resize_bg.wasm'));

mkdirSync(ORIGINALS, { recursive: true });

const kb = (bytes) => `${Math.round(bytes / 1000)} kB`;
const rows = [];

for (const name of readdirSync(DIR).filter((file) => /\.jpe?g$/i.test(file))) {
    const target = join(DIR, name);
    const original = join(ORIGINALS, name);

    if (!existsSync(original)) renameSync(target, original);

    const source = await decode(readFileSync(original));
    // Home hero slides (hero-*.jpg) and service page heroes (*-hero.jpg).
    const isHero = /^hero-|-hero\.jpe?g$/i.test(name);
    let width = Math.min(MAX_WIDTH, source.width);
    let output;

    for (;;) {
        const height = Math.round((source.height * width) / source.width);
        const pixels = width === source.width ? source : await resize(source, { width, height, method: 'lanczos3' });
        output = Buffer.from(await encode(pixels, { quality: QUALITY, progressive: true, optimize_coding: true }));

        if (!isHero || output.length < HERO_MAX_BYTES || width - WIDTH_STEP < MIN_HERO_WIDTH) break;
        width -= WIDTH_STEP;
    }

    writeFileSync(target, output);
    rows.push({
        file: name,
        original: `${source.width}x${source.height}, ${kb(statSync(original).size)}`,
        result: `${width}x${Math.round((source.height * width) / source.width)}, ${kb(output.length)}`,
        heroUnder500KB: isHero ? output.length < HERO_MAX_BYTES : '-',
    });
}

console.table(rows);
