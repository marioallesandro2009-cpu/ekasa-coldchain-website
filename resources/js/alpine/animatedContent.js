import { reducedMotion } from './motion';

/**
 * Fade + rise 24px over 0.6s once visible (x-intersect.once="show()"),
 * delayed by data-delay (seconds). The hidden state only applies once JS runs.
 */
export default () => ({
    init() {
        this.$el.style.setProperty('--delay', `${parseFloat(this.$el.dataset.delay || '0')}s`);

        if (reducedMotion()) this.show();
    },

    show() {
        this.$el.classList.add('is-in');
    },
});
