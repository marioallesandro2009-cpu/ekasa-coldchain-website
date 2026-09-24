import { reducedMotion } from './motion';

const DURATION = 2000;

/**
 * Counts from 0 to the final value (already in the HTML) once visible,
 * formatted for the page language (<html lang>: "1,250" / "1.250"). Used with
 * x-intersect.once="start()".
 */
export default ({ to, decimals = 0 }) => ({
    // Declared here so each counter keeps its own formatter and frame (in the hero
    // they are nested components; undeclared properties would be shared via the hero).
    format: null,
    frame: null,

    init() {
        this.format = new Intl.NumberFormat(document.documentElement.lang || 'en', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        });

        if (!reducedMotion()) this.$el.textContent = this.format.format(0);
    },

    destroy() {
        cancelAnimationFrame(this.frame);
    },

    start() {
        if (reducedMotion()) return;

        const startedAt = performance.now();
        const tick = (now) => {
            const progress = Math.min(1, (now - startedAt) / DURATION);
            const eased = 1 - Math.pow(1 - progress, 3);

            this.$el.textContent = this.format.format(to * eased);

            if (progress < 1) this.frame = requestAnimationFrame(tick);
        };

        this.frame = requestAnimationFrame(tick);
    },
});
