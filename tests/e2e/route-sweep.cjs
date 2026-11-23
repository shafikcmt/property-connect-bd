const { chromium } = require('playwright');
const base = 'http://127.0.0.1:8099';
(async () => {
  const b = await chromium.launch();
  const ctx = await b.newContext({ viewport: { width: 1280, height: 900 } });
  const p = await ctx.newPage();
  // discover slugs
  await p.goto(base + '/properties');
  const prop = await p.$eval('a[href*="/properties/"][href$="-"], article h3 a', a => a.getAttribute('href')).catch(()=>null);
  const first = async (path, sel) => { await p.goto(base + path); return p.$eval(sel, a => a.getAttribute('href')).then(h => h.replace(base, '')).catch(() => null); };
  const pr = await first('/properties', 'article h3 a');
  const pj = await first('/projects', 'article h3 a');
  const dv = await first('/developers', 'article h3 a');
  const lc = await first('/locations', 'a[href*="/locations/"]');
  const bl = await first('/blog', 'article h3 a');
  const routes = ['/', '/properties', '/properties/for-rent', '/properties?type[]=apartment&beds=3&sort=price_asc', pr, '/projects', pj, '/developers', dv, '/locations', lc, '/services', '/about', '/contact', '/faq', '/blog', bl, '/privacy-policy', '/terms-and-conditions', '/sell', '/rent-out', '/valuation', '/assistance', '/login', '/register', '/favorites', '/compare', '/search?q=gulshan'].filter(Boolean);
  const fs = require('fs'); fs.mkdirSync('/tmp/shots', { recursive: true });
  const issues = [];
  for (const w of [375, 768, 1280, 1440]) {
    const page = await (await b.newContext({ viewport: { width: w, height: 900 } })).newPage();
    const errs = [];
    page.on('pageerror', e => errs.push('JS: ' + e.message));
    page.on('response', r => { if (r.status() >= 400) errs.push(r.status() + ' ' + r.url()); });
    for (const r of routes) {
      errs.length = 0;
      await page.goto(base + r, { waitUntil: 'networkidle' });
      // reveal everything for full-page captures
      await page.evaluate(() => document.querySelectorAll('.reveal').forEach(e => e.classList.add('is-in')));
      await page.waitForTimeout(150);
      const m = await page.evaluate(() => ({ over: document.documentElement.scrollWidth > document.documentElement.clientWidth + 1, h: document.documentElement.scrollHeight, broken: [...document.images].filter(i => i.loading !== 'lazy' && i.complete && i.naturalWidth === 0).length }));
      if (m.over) issues.push(`${w} ${r}: horizontal overflow`);
      if (m.broken) issues.push(`${w} ${r}: ${m.broken} broken images`);
      if (errs.length) issues.push(`${w} ${r}: ${errs.join(' | ')}`);
      const name = r.replace(/[^a-z0-9]+/gi, '_').slice(0, 40) || 'home';
      if ([375, 1280].includes(w) && ['/', '/properties', pr, pj, '/projects', '/developers', dv, '/locations', '/contact', '/faq', '/sell', '/compare'].includes(r)) await page.screenshot({ path: `/tmp/shots/${w}_${name}.png`, fullPage: true });
    }
  }
  console.log('ROUTES', routes.length, '\nISSUES', issues.length); issues.forEach(i => console.log(i));
  await b.close();
})();
