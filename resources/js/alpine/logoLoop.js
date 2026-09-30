import { reducedMotion } from './motion';

const SMOOTH_TAU = 0.25;

/**
 * Continuous horizontal logo marquee (#clients section), ported from React
 * Bits' LogoLoop to Alpine — same easing/measuring approach (a velocity that
 * eases toward a target with requestAnimationFrame, rather than a CSS
 * @keyframes loop) so hovering can decelerate smoothly instead of stepping.
 * The list itself is duplicated server-side (x-sections.clients renders it
 * twice, back to back) rather than JS calculating how many copies fit: two
 * copies is always enough here because the logo strip is short and the
 * container is capped at a sane max-width, unlike the original component's
 * general-purpose "any number of logos, any container width" case.
 *
 * reverse: true scrolls right instead of left (used for a second row moving
 * the opposite way — a common "wall of logos" treatment, and a cheap way to
 * make a handful of logos read as a fuller wall without repeating one row).
 */
export default ({ speed = 40, reverse = false }) => ({
    speed,
    reverse,
    hovered: false,
    rafId: null,
    lastTimestamp: null,
    offset: 0,
    velocity: 0,
    sequenceWidth: 0,
    reduced: false,

    init() {
        this.reduced = reducedMotion();

        if (this.reduced) {
            return;
        }

        this.measure();
        const onResize = () => this.measure();
        window.addEventListener('resize', onResize);
        this.rafId = requestAnimationFrame(this.tick.bind(this));

        this.$watch('hovered', () => {});
        this.$el.addEventListener('mouseenter', () => {
            this.hovered = true;
        });
        this.$el.addEventListener('mouseleave', () => {
            this.hovered = false;
        });

        this.$cleanup = () => {
            window.removeEventListener('resize', onResize);
            if (this.rafId !== null) cancelAnimationFrame(this.rafId);
        };
    },

    destroy() {
        this.$cleanup?.();
    },

    measure() {
        this.sequenceWidth = Math.ceil(this.$refs.sequence?.getBoundingClientRect().width ?? 0);
    },

    tick(timestamp) {
        if (this.lastTimestamp === null) this.lastTimestamp = timestamp;
        const delta = Math.max(0, timestamp - this.lastTimestamp) / 1000;
        this.lastTimestamp = timestamp;

        const target = this.hovered ? this.speed * 0.25 : this.speed;
        const ease = 1 - Math.exp(-delta / SMOOTH_TAU);
        this.velocity += (target - this.velocity) * ease;

        if (this.sequenceWidth > 0) {
            this.offset = ((this.offset + this.velocity * delta) % this.sequenceWidth + this.sequenceWidth) % this.sequenceWidth;
            const signedOffset = this.reverse ? this.offset - this.sequenceWidth : -this.offset;
            this.$refs.track.style.transform = `translate3d(${signedOffset}px, 0, 0)`;
        }

        this.rafId = requestAnimationFrame(this.tick.bind(this));
    },
});
