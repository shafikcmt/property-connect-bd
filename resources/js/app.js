import Alpine from 'alpinejs';

window.Alpine = Alpine;

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;
const post = (url) => fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' }, credentials: 'same-origin' });

/* Favorite heart (progressive enhancement: works for guests via session). */
Alpine.data('favorite', (kind, id, initial = false) => ({
    on: initial,
    pop: false,
    busy: false,
    async toggle() {
        if (this.busy) return;
        this.busy = true;
        const res = await post(`/favorites/${kind}/${id}`);
        this.busy = false;
        if (!res.ok) return;
        const data = await res.json();
        this.on = data.favorited;
        this.pop = true;
        setTimeout(() => (this.pop = false), 400);
        window.dispatchEvent(new CustomEvent('favorites:count', { detail: data.count }));
    },
}));

/* Compare checkbox with a floating tray (max 4, enforced server-side). */
Alpine.data('compare', (id, initial = false) => ({
    on: initial,
    msg: '',
    async toggle() {
        const res = await post(`/compare/${id}`);
        const data = await res.json();
        if (!res.ok) { this.msg = data.error; setTimeout(() => (this.msg = ''), 2500); return; }
        this.on = data.compared;
        window.dispatchEvent(new CustomEvent('compare:count', { detail: data.count }));
    },
}));

Alpine.data('counter', (target) => ({
    n: target,
    init() {
        if (!('IntersectionObserver' in window)) return;
        this.n = 0;
        const io = new IntersectionObserver((e) => { if (e[0].isIntersecting) { this.start(); io.disconnect(); } });
        io.observe(this.$el);
    },
    start() {
        const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reduce || target < 2) { this.n = target; return; }
        const t0 = performance.now();
        const step = (t) => {
            const p = Math.min(1, (t - t0) / 1200);
            this.n = Math.round(target * (1 - Math.pow(1 - p, 3)));
            if (p < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    },
}));

/* Autosuggest for the header/hero search. */
Alpine.data('suggest', () => ({
    q: '', items: [], open: false, loading: false, t: null,
    input() {
        clearTimeout(this.t);
        if (this.q.trim().length < 2) { this.items = []; this.open = false; return; }
        this.loading = true;
        this.t = setTimeout(async () => {
            const res = await fetch(`/search/suggest?q=${encodeURIComponent(this.q.trim())}`, { headers: { Accept: 'application/json' } });
            this.items = res.ok ? await res.json() : [];
            this.loading = false;
            this.open = true;
        }, 220);
    },
}));

/* Gallery with lightbox, keyboard + swipe. */
Alpine.data('gallery', (count) => ({
    i: 0, box: false, x0: null,
    go(n) { this.i = (n + count) % count; },
    next() { this.go(this.i + 1); },
    prev() { this.go(this.i - 1); },
    open(n) { this.i = n; this.box = true; document.documentElement.classList.add('overflow-hidden'); },
    close() { this.box = false; document.documentElement.classList.remove('overflow-hidden'); },
    ts(e) { this.x0 = e.changedTouches[0].clientX; },
    te(e) { if (this.x0 === null) return; const d = e.changedTouches[0].clientX - this.x0; if (Math.abs(d) > 40) d < 0 ? this.next() : this.prev(); this.x0 = null; },
}));

window.Alpine.start();

/* Scroll reveal: one IntersectionObserver, no library. */
const reveal = () => {
    const els = document.querySelectorAll('.reveal:not(.is-in)');
    if (!('IntersectionObserver' in window) || matchMedia('(prefers-reduced-motion: reduce)').matches) {
        els.forEach((e) => e.classList.add('is-in'));
        return;
    }
    const io = new IntersectionObserver((entries) => entries.forEach((en) => {
        if (en.isIntersecting) { en.target.classList.add('is-in'); io.unobserve(en.target); }
    }), { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
    els.forEach((e) => io.observe(e));
};
document.addEventListener('DOMContentLoaded', reveal);
