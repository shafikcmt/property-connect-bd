const { chromium } = require(require('child_process').execSync('npm root -g').toString().trim() + '/playwright');
const axe = require('fs').readFileSync('' + (process.env.QA_NODE_MODULES || '/tmp/qa/node_modules') + '/axe-core/axe.min.js', 'utf8');
const [sp, sj] = require('fs').readFileSync('/tmp/qa/slugs.txt', 'utf8').trim().split(' ');
const routes = ['/', '/properties', '/properties?beds=2&type[]=apartment', '/properties/' + sp, '/projects', '/projects/' + sj, '/developers', '/locations', '/services', '/about', '/contact', '/faq', '/blog', '/privacy-policy', '/sell', '/rent-out', '/valuation', '/assistance', '/login', '/register', '/favorites', '/compare', '/search?q=a'];
(async () => {
  const b = await chromium.launch(); const bad = {};
  for (const w of [375, 1280]) {
    const p = await (await b.newContext({ viewport: { width: w, height: 900 } })).newPage();
    for (const r of routes) {
      await p.goto('http://127.0.0.1:8099' + r, { waitUntil: 'networkidle' });
      await p.evaluate(() => document.querySelectorAll('.reveal').forEach(e => e.classList.add('is-in')));
      await p.waitForTimeout(1300); await p.addScriptTag({ content: axe });
      const res = await p.evaluate(() => axe.run(document, { runOnly: ['wcag2a', 'wcag2aa', 'wcag21aa', 'best-practice'] }));
      for (const v of res.violations) { const k = v.id + ' [' + v.impact + ']'; (bad[k] ||= new Set()).add(w + ' ' + r + ' → ' + v.nodes[0].target.join(' ').slice(0, 70)); }
    }
  }
  for (const [k, v] of Object.entries(bad)) console.log(k, v.size, [...v].slice(0, 3));
  console.log('DONE', Object.keys(bad).length, 'rule types');
  await b.close();
})();
