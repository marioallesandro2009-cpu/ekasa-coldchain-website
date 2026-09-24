import { reducedMotion } from './motion';

/**
 * Splits the server-rendered text into words that come in from blur(10px),
 * opacity 0 and 20px lower, 80ms apart, once on page load. Each word drops its
 * compositor layer (will-change) once its animation has ended.
 */
export default () => ({
    init() {
        const el = this.$el;

        if (!reducedMotion()) {
            const words = el.textContent.trim().split(/\s+/);
            el.textContent = '';

            words.forEach((word, i) => {
                if (i > 0) el.append(' ');

                const span = document.createElement('span');
                span.className = 'blur-word';
                span.style.setProperty('--i', String(i));
                span.textContent = word;
                span.addEventListener('animationend', () => span.classList.add('is-done'), { once: true });
                el.append(span);
            });
        }

        el.dataset.split = '';
    },
});
