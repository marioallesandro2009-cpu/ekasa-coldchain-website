/**
 * Draws the batch 2 Ekasa icons (and "network") into resources/icons/ekasa/.
 * Geometry is computed here so circles, arcs and spokes line up exactly; the
 * output follows the stock rules (viewBox 24, stroke 1.75, currentColor).
 *
 *   node scripts/draw-icons-batch2.mjs [variantDir]
 *
 * Variants for the review sheet (key contains "variant") go to variantDir.
 */
import { writeFileSync } from 'node:fs';

const OUT = 'resources/icons/ekasa';
const VARIANTS = process.argv[2] ?? OUT;
const r2 = (n) => Math.round(n * 100) / 100;
const pt = (x, y) => `${r2(x)} ${r2(y)}`;
const rad = (deg) => (deg * Math.PI) / 180;
const polar = (cx, cy, r, deg) => [cx + r * Math.cos(rad(deg)), cy + r * Math.sin(rad(deg))];

/** Full circle as a subpath (to merge several circles into one <path>). */
const circleSub = (cx, cy, r) => `M${pt(cx - r, cy)} a${r2(r)} ${r2(r)} 0 1 0 ${r2(2 * r)} 0 a${r2(r)} ${r2(r)} 0 1 0 ${r2(-2 * r)} 0`;

/** Upper intersection point of two circles [cx, cy, r]. */
function upperIntersection([x0, y0, r0], [x1, y1, r1]) {
    const d = Math.hypot(x1 - x0, y1 - y0);
    const a = (r0 * r0 - r1 * r1 + d * d) / (2 * d);
    const h = Math.sqrt(r0 * r0 - a * a);
    const xm = x0 + (a * (x1 - x0)) / d;
    const ym = y0 + (a * (y1 - y0)) / d;
    const points = [
        [xm + (h * (y1 - y0)) / d, ym - (h * (x1 - x0)) / d],
        [xm - (h * (y1 - y0)) / d, ym + (h * (x1 - x0)) / d],
    ];
    return points.sort((p, q) => p[1] - q[1])[0];
}

const svg = (body) => `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">${body}</svg>\n`;
const path = (d) => `<path d="${d}"/>`;
const circle = (cx, cy, r) => `<circle cx="${r2(cx)}" cy="${r2(cy)}" r="${r2(r)}"/>`;
const rect = (x, y, w, h, rx = 0) => `<rect x="${r2(x)}" y="${r2(y)}" width="${r2(w)}" height="${r2(h)}"${rx ? ` rx="${r2(rx)}"` : ''}/>`;

const icons = {};

// network: a hub with four nodes on the diagonals (the same diagonal build as
// chain-link). Fewer, larger nodes than a star or snowflake, legible at 16 px.
{
    const network = (cx, cy, hub, node, R, angles) => {
        const nodes = angles.map((a) => polar(cx, cy, R, a));
        const spokes = angles.map((a) => `M${pt(...polar(cx, cy, hub, a))} L${pt(...polar(cx, cy, R - node, a))}`).join(' ');
        return svg(circle(cx, cy, hub) + path(nodes.map(([x, y]) => circleSub(x, y, node)).join(' ')) + path(spokes));
    };
    icons.network = network(12, 12, 2.75, 2.1, 9.3, [-135, -45, 45, 135]);
    // Alternative for the review sheet only: three nodes.
    icons['network-variant-tri'] = network(12, 13, 2.5, 2.25, 7.75, [-90, 30, 150]);
}

// Warehouse & workflow
icons.forklift = svg(
    path('M2.75 16.25 V11 H13 V16.25 M4.5 11 V5 H10 L12 11 M13 13.5 H15.5')
    + circle(5.5, 18, 1.75) + circle(11, 18, 1.75)
    + path('M15.5 3.5 V19.75 H21.5'),
);
icons.pallet = svg(
    rect(3.5, 10, 7.5, 7, 1) + rect(13, 5, 7.5, 12, 1)
    + path('M2.5 17 H21.5 M2.5 21 H21.5 M4.5 17 V21 M12 17 V21 M19.5 17 V21'),
);
icons['barcode-scan'] = svg(
    path('M3 7.5 V4.5 A1.5 1.5 0 0 1 4.5 3 H7.5 M16.5 3 H19.5 A1.5 1.5 0 0 1 21 4.5 V7.5 M21 16.5 V19.5 A1.5 1.5 0 0 1 19.5 21 H16.5 M7.5 21 H4.5 A1.5 1.5 0 0 1 3 19.5 V16.5')
    + path('M7.5 8 V16 M10.5 8 V16 M13.5 8 V16 M16.5 8 V16'),
);
icons['clipboard-check'] = svg(
    path('M9 4.75 H7 A2 2 0 0 0 5 6.75 V19.5 A2 2 0 0 0 7 21.5 H17 A2 2 0 0 0 19 19.5 V6.75 A2 2 0 0 0 17 4.75 H15')
    + rect(9, 3, 6, 3.5, 1)
    + path('M8.75 13.75 L11 16 L15.25 11.75'),
);
icons['cross-dock'] = svg(
    path('M3 8 H17.5 M14 4.5 L17.5 8 L14 11.5')
    + path('M21 16 H6.5 M10 12.5 L6.5 16 L10 19.5'),
);

// Time & monitoring
icons['calendar-check'] = svg(
    rect(3.5, 5, 17, 16, 2)
    + path('M8 3 V7 M16 3 V7 M3.5 10 H20.5')
    + path('M9 15.25 L11 17.25 L15 13.25'),
);
icons.stopwatch = svg(
    circle(12, 13.5, 7.5)
    + path('M10 3 H14 M12 3 V6 M18 7.5 L19.5 6 M12 13.5 V9.5'),
);
icons['monitor-pulse'] = svg(
    rect(2.5, 4, 19, 13, 2)
    + path('M9 21 H15 M12 17 V21')
    + path('M5.5 11.5 H8.5 L10 8.5 L12.5 14 L14 11.5 H18.5'),
);
icons.bell = svg(
    path('M6 16.5 V11 A6 6 0 0 1 18 11 V16.5 L19.5 18.25 H4.5 Z M12 3.25 V5')
    + path('M10.25 20.75 A1.9 1.9 0 0 0 13.75 20.75'),
);

// Security & quality
icons.lock = svg(
    rect(5, 10.5, 14, 10.5, 2)
    + path('M8 10.5 V7.5 A4 4 0 0 1 16 7.5 V10.5')
    + circle(12, 15, 1.25) + path('M12 16.25 V18'),
);
icons.cctv = svg(
    path('M3 9 L16.5 4.5 L18.5 10.5 L5 15 Z')
    + path('M11.25 13 L12 17.5 H3.25 M3.25 14.5 V20.5'),
);
icons.droplet = svg(
    path('M12 3.25 C12 3.25 5.5 10 5.5 14.25 A6.5 6.5 0 0 0 18.5 14.25 C18.5 10 12 3.25 12 3.25 Z')
    + path('M9 14.5 A3 3 0 0 0 12 17.5'),
);

// Industries & goods
icons.fish = svg(
    path('M2.75 12 C6 7.25 12.5 6.75 16.5 12 C12.5 17.25 6 16.75 2.75 12 Z')
    + path('M16.5 12 L21.25 8.25 V15.75 Z')
    + circle(6.75, 11.25, 0.4)
    + path('M10 9.25 C11 10.75 11 13.25 10 14.75'),
);
icons.leaf = svg(
    path('M5.5 18.5 C5 10 10.5 4.5 20 4 C19.5 13.5 14 19 5.5 18.5 Z')
    + path('M3 21 L14 10'),
);
icons['milk-carton'] = svg(
    path('M7 21 V10 L9.5 6.5 H14.5 L17 10 V21 Z')
    + path('M9.5 6.5 V3.5 H14.5 V6.5 M7 10 H17')
    + path('M10 14.5 H14'),
);
icons['ice-cream'] = svg(
    path('M7.5 10 L12 21 L16.5 10')
    + path('M6 10 A6 6 0 0 1 18 10 Z')
    + path('M9.4 14 L14.9 11.4'),
);
{
    // Capsule on the diagonal, split in the middle.
    const r = 3.6;
    const a = [8, 16];
    const b = [16, 8];
    const off = ([x, y], s) => [x + s * r * Math.SQRT1_2, y + s * r * Math.SQRT1_2];
    icons.capsule = svg(
        path(`M${pt(...off(a, -1))} L${pt(...off(b, -1))} A${r} ${r} 0 0 1 ${pt(...off(b, 1))} L${pt(...off(a, 1))} A${r} ${r} 0 0 1 ${pt(...off(a, -1))} Z`)
        + path(`M${pt(...off([12, 12], -1))} L${pt(...off([12, 12], 1))}`),
    );
}

// Systems & technology
{
    const arc = (r, from, to) => `M${pt(...polar(12, 11.5, r, from))} A${r} ${r} 0 0 1 ${pt(...polar(12, 11.5, r, to))}`;
    icons['iot-sensor'] = svg(
        rect(8.5, 13.5, 7, 7.5, 1.5)
        + circle(12, 17.25, 0.4)
        + path(`${arc(3.5, -140, -40)} ${arc(7, -140, -40)}`),
    );
}
{
    // Cloud: three circles above a flat base.
    const left = [6.75, 14.75, 4.25];
    const main = [11.75, 10.25, 5.5];
    const right = [17.25, 14.75, 4.25];
    const base = 19;
    const lm = upperIntersection(left, main);
    const mr = upperIntersection(main, right);
    icons['cloud-sync'] = svg(
        path(`M${pt(right[0], base)} H${r2(left[0])} A${left[2]} ${left[2]} 0 0 1 ${pt(...lm)} A${main[2]} ${main[2]} 0 0 1 ${pt(...mr)} A${right[2]} ${right[2]} 0 0 1 ${pt(right[0], base)} Z`)
        + path('M10 12.25 V16.5 M8.5 15 L10 16.5 L11.5 15')
        + path('M14 16.5 V12.25 M12.5 13.75 L14 12.25 L15.5 13.75'),
    );
}
{
    const c = [12, 13.5];
    const r = 8.5;
    icons.gauge = svg(
        path(`M${pt(...polar(...c, r, 135))} A${r} ${r} 0 1 1 ${pt(...polar(...c, r, 45))}`)
        + path(`M${pt(...c)} L${pt(...polar(...c, 5.5, -45))}`)
        + circle(...c, 1.25),
    );
}
icons.plug = svg(
    path('M9 3 V7 M15 3 V7')
    + path('M6.5 7 H17.5 V11 A5.5 5.5 0 0 1 6.5 11 Z')
    + path('M12 16.5 V21'),
);

for (const [key, markup] of Object.entries(icons)) {
    writeFileSync(`${key.includes('variant') ? VARIANTS : OUT}/${key}.svg`, markup);
}
console.log(`${Object.keys(icons).length} icons: ${Object.keys(icons).join(', ')}`);
