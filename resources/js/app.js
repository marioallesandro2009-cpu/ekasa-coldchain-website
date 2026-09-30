import intersect from '@alpinejs/intersect';
import Alpine from 'alpinejs';
import animatedContent from './alpine/animatedContent';
import animatedList from './alpine/animatedList';
import blurText from './alpine/blurText';
import countUp from './alpine/countUp';
import fleetShowcase from './alpine/fleetShowcase';
import glareHover from './alpine/glareHover';
import hero from './alpine/hero';
import locationMap from './alpine/locationMap';
import locationsIndex from './alpine/locationsIndex';
import logoLoop from './alpine/logoLoop';
import navbar from './alpine/navbar';
import rotatingText from './alpine/rotatingText';
import splitText from './alpine/splitText';
import spotlightCard from './alpine/spotlightCard';
import heroParallax from './parallax';

// Emit the fonts so the layout can preload them with Vite::asset().
import.meta.glob('../fonts/*.woff2');

Alpine.plugin(intersect);

Alpine.data('navbar', navbar);
Alpine.data('hero', hero);
Alpine.data('rotatingText', rotatingText);
Alpine.data('blurText', blurText);
Alpine.data('countUp', countUp);
Alpine.data('animatedContent', animatedContent);
Alpine.data('splitText', splitText);
Alpine.data('animatedList', animatedList);
Alpine.data('spotlightCard', spotlightCard);
Alpine.data('glareHover', glareHover);
Alpine.data('fleetShowcase', fleetShowcase);
Alpine.data('locationMap', locationMap);
Alpine.data('locationsIndex', locationsIndex);
Alpine.data('logoLoop', logoLoop);

// Links to sections that do not exist yet (e.g. a service anchor) go to #services.
document.addEventListener('click', (event) => {
    const link = event.target.closest?.('a[href^="#"]');
    const id = link?.getAttribute('href').slice(1);

    if (!id || id === 'mobile-menu' || document.getElementById(id)) return;

    event.preventDefault();
    document.getElementById('services')?.scrollIntoView();
    history.pushState(null, '', '#services');
});

// The inline safety net in the layout drops "js" if this bundle is late; restore it
// (the "js-anim" hiding class stays off, so visible content is not hidden again).
document.documentElement.classList.add('js');

window.Alpine = Alpine;
Alpine.start();

// Components below the first screen (marked x-ignore data-alpine-defer) start when
// they come within 300px of the viewport, or when the browser is idle, instead of
// adding to the work during page load.
const deferred = [...document.querySelectorAll('[data-alpine-defer]')];

if (deferred.length) {
    const wake = (el) => {
        if (!el.hasAttribute('x-ignore')) return;
        el.removeAttribute('x-ignore');
        delete el._x_ignore;
        Alpine.initTree(el);
    };
    const observer = new IntersectionObserver(
        (entries) => entries.filter((entry) => entry.isIntersecting).forEach((entry) => {
            observer.unobserve(entry.target);
            wake(entry.target);
        }),
        { rootMargin: '300px 0px' },
    );

    deferred.forEach((el) => observer.observe(el));

    const whenIdle = window.requestIdleCallback ?? ((callback) => setTimeout(callback, 2000));
    window.addEventListener('load', () => whenIdle(() => {
        observer.disconnect();
        deferred.forEach(wake);
    }, { timeout: 5000 }), { once: true });
}
heroParallax();
