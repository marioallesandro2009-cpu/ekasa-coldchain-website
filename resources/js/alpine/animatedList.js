import { reducedMotion } from './motion';

// Low enough that an item taller than the screen (a long FAQ answer) still triggers.
const THRESHOLD = 0.2;

/**
 * Lists of cards or FAQ items (port of the React Bits AnimatedList entrance, no
 * motion library): every direct child scales from 0.7 and fades in over 0.2s as
 * soon as it enters the viewport (CSS: [data-animated-list] > *). Children that
 * enter together are staggered by their order in that batch, so a child far down
 * the list does not wait for the ones above it. Runs once per child.
 */
export default () => ({
    observer: null,

    init() {
        const items = [...this.$el.children];

        if (reducedMotion() || items.length === 0) return;

        this.observer = new IntersectionObserver((entries) => {
            const entering = entries
                .filter((entry) => entry.isIntersecting)
                .map((entry) => entry.target)
                .sort((a, b) => items.indexOf(a) - items.indexOf(b));

            entering.forEach((item, i) => {
                item.style.setProperty('--i', String(i));
                item.classList.add('is-in');
                this.observer.unobserve(item);
            });
        }, { threshold: THRESHOLD });

        items.forEach((item) => this.observer.observe(item));
    },

    destroy() {
        this.observer?.disconnect();
    },
});
