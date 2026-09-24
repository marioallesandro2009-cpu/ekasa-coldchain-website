import { DESKTOP_QUERY, reducedMotion } from './alpine/motion';

const SPEED = 0.1;

/**
 * Hero photo parallax: the photo layer moves at 0.1x the scroll speed (desktop only,
 * transform only, one update per animation frame, only while the hero is on screen).
 * Off with prefers-reduced-motion. The layer only moves down, so the hero clips it.
 */
export default function heroParallax() {
    const layer = document.querySelector('[data-parallax]');
    const hero = layer?.closest('section');
    if (!layer || !hero || reducedMotion()) return;

    const desktop = window.matchMedia(DESKTOP_QUERY);
    let frame = 0;

    const apply = () => {
        frame = 0;
        const offset = Math.min(window.scrollY, hero.offsetHeight);
        layer.style.transform = desktop.matches && offset > 0 ? `translate3d(0, ${Math.round(offset * SPEED * 10) / 10}px, 0)` : '';
    };

    const onScroll = () => {
        if (!frame) frame = requestAnimationFrame(apply);
    };

    window.addEventListener('scroll', onScroll, { passive: true });
    desktop.addEventListener('change', apply);
    apply();
}
