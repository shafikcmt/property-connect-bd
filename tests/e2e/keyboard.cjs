const { chromium } = require(require('child_process').execSync('npm root -g').toString().trim() + '/playwright');
const [sp] = require('fs').readFileSync('/tmp/qa/slugs.txt', 'utf8').trim().split(' ');
const ok = (n, c, x = '') => console.log((c ? 'PASS' : 'FAIL') + '  ' + n + ' ' + x);
(async () => {
  const b = await chromium.launch();
  let p = await (await b.newContext({ viewport: { width: 1280, height: 900 } })).newPage();
  await p.goto('http://127.0.0.1:8099/'); await p.keyboard.press('Tab');
  ok('skip link is first tab stop', await p.evaluate(() => document.activeElement.textContent.trim() === 'Skip to content'));
  await p.keyboard.press('Enter'); ok('skip link moves to main', await p.evaluate(() => location.hash === '#main'));
  // focus visibility on 25 tab stops
  await p.goto('http://127.0.0.1:8099/'); let bad = [];
  for (let i = 0; i < 25; i++) { await p.keyboard.press('Tab'); const r = await p.evaluate(() => { const e = document.activeElement; const s = getComputedStyle(e); const vis = (s.outlineStyle !== 'none' && parseFloat(s.outlineWidth) > 0) || s.boxShadow !== 'none' || e.matches('input,select,textarea'); return { vis, t: e.tagName + ' ' + (e.getAttribute('aria-label') || e.textContent.trim().slice(0, 25)) }; }); if (!r.vis) bad.push(r.t); }
  ok('first 25 tab stops show a focus indicator', bad.length === 0, bad.join(' | '));
  // search overlay
  await p.goto('http://127.0.0.1:8099/'); await p.click('button[aria-label="Search"]'); await p.waitForTimeout(500);
  ok('search overlay focuses its input', await p.evaluate(() => document.activeElement.name === 'q'));
  await p.keyboard.type('gul'); await p.waitForTimeout(700);
  ok('autosuggest returns results', await p.locator('ul li a').filter({ hasText: /Gulshan/ }).count() > 0);
  await p.keyboard.press('Escape'); await p.waitForTimeout(500); ok('Escape closes search', !(await p.locator('[role=dialog][aria-label="Search"]').isVisible()));
  // dropdown via hover
  await p.hover('nav[aria-label="Primary"] >> text=Properties'); await p.waitForTimeout(250);
  ok('properties dropdown opens', await p.locator('text=For rent').first().isVisible());
  // gallery lightbox
  await p.goto('http://127.0.0.1:8099/properties/' + sp); await p.locator('button[aria-label*="open photo gallery"]').click(); await p.waitForTimeout(500);
  ok('lightbox opens', await p.locator('[role=dialog][aria-label="Photo gallery"]').isVisible());
  await p.keyboard.press('ArrowRight'); ok('arrow key advances', (await p.locator('[role=dialog] p[aria-live]').innerText()).startsWith('2'));
  await p.keyboard.press('Escape'); await p.waitForTimeout(500); ok('Escape closes lightbox', !(await p.locator('[role=dialog][aria-label="Photo gallery"]').isVisible()));
  // form validation + labels
  await p.goto('http://127.0.0.1:8099/contact'); await p.click('button:has-text("Send message")'); await p.waitForTimeout(300);
  ok('native required validation blocks empty submit', await p.evaluate(() => location.pathname === '/contact' && !document.querySelector('[role=status]')));
  const unl = await p.evaluate(() => [...document.querySelectorAll('input:not([type=hidden]):not(.hidden),select,textarea')].filter(e => !e.labels?.length && !e.getAttribute('aria-label') && e.tabIndex >= 0 && e.offsetParent).map(e => e.name));
  ok('all visible form controls have labels', unl.length === 0, unl.join(','));
  // mobile menu keyboard
  const m = await (await b.newContext({ viewport: { width: 375, height: 800 } })).newPage();
  await m.goto('http://127.0.0.1:8099/'); await m.click('button[aria-label="Menu"]'); await m.waitForTimeout(500);
  ok('mobile menu opens and links reachable', await m.locator('#mobile-menu a:has-text("Properties")').first().isVisible());
  await m.keyboard.press('Escape'); await m.waitForTimeout(500); ok('Escape closes mobile menu', !(await m.locator('#mobile-menu').isVisible()));
  // reduced motion
  const rm = await (await b.newContext({ viewport: { width: 1280, height: 900 }, reducedMotion: 'reduce' })).newPage();
  await rm.goto('http://127.0.0.1:8099/'); await rm.waitForTimeout(200);
  const r = await rm.evaluate(() => ({ reveal: [...document.querySelectorAll('.reveal')].every(e => getComputedStyle(e).opacity === '1'), anim: getComputedStyle(document.querySelector('.float-a')).animationName }));
  ok('reduced motion: content visible without animation', r.reveal && r.anim === 'none', JSON.stringify(r));
  await b.close();
})();
