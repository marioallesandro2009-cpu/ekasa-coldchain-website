/**
 * Diagonal glare sweep on hover (port of React Bits GlareHover). Unlike
 * spotlightCard, the sweep itself is a plain CSS background-position
 * transition (.glare-hover in app.css) — no pointer tracking needed, so it
 * also plays without JS, using the CSS defaults. The only JS here is turning
 * per-instance props (glareColor + glareOpacity, angle, size, duration) into
 * the CSS custom properties .glare-hover reads, same idea as spotlightCard's
 * --mouse-x/--mouse-y but computed once instead of per pointer move.
 */
export default ({ color = '#ffffff', opacity = 0.22, angle = -30, size = 280, duration = 700 } = {}) => ({
    init() {
        const hex = color.replace('#', '');
        const bytes = hex.length === 3 ? [...hex].map((c) => c + c) : [hex.slice(0, 2), hex.slice(2, 4), hex.slice(4, 6)];
        const [r, g, b] = bytes.map((byte) => parseInt(byte, 16));

        this.$el.style.setProperty('--glare-rgba', `rgba(${r}, ${g}, ${b}, ${opacity})`);
        this.$el.style.setProperty('--glare-angle', `${angle}deg`);
        this.$el.style.setProperty('--glare-size', `${size}%`);
        this.$el.style.setProperty('--glare-duration', `${duration}ms`);
    },
});
