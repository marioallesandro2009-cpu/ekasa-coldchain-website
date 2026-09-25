import { reducedMotion } from './motion';

/**
 * Card with a light that follows the pointer (port of React Bits SpotlightCard):
 * pointer moves set --mouse-x / --mouse-y (one update per frame); the radial
 * gradient, the 4px lift and the shadow are CSS (.spotlight-card). Nothing is
 * tracked on touch screens (hover: none) or with reduced motion, where the CSS
 * switches every effect off as well.
 */
export default () => ({
    frame: 0,
    onMove: null,

    init() {
        if (reducedMotion() || !window.matchMedia('(hover: hover)').matches) return;

        const el = this.$el;
        let x = 0;
        let y = 0;

        this.onMove = (event) => {
            const rect = el.getBoundingClientRect();
            x = event.clientX - rect.left;
            y = event.clientY - rect.top;

            this.frame ||= requestAnimationFrame(() => {
                this.frame = 0;
                el.style.setProperty('--mouse-x', `${x}px`);
                el.style.setProperty('--mouse-y', `${y}px`);
            });
        };

        el.addEventListener('pointermove', this.onMove, { passive: true });
    },

    destroy() {
        cancelAnimationFrame(this.frame);
        if (this.onMove) this.$el.removeEventListener('pointermove', this.onMove);
    },
});
