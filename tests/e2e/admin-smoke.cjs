const { chromium } = require(require('child_process').execSync('npm root -g').toString().trim() + '/playwright');
const B = 'http://127.0.0.1:8098';
(async () => {
  const b = await chromium.launch(); const issues = [];
  for (const w of [1280, 375]) {
    const p = await (await b.newContext({ viewport: { width: w, height: 900 } })).newPage();
    p.on('pageerror', e => issues.push(`${w} JS: ${e.message.slice(0, 100)}`));
    await p.goto(B + '/admin/login'); await p.fill('input[type=email]', 'qa-admin@example.test'); await p.fill('input[type=password]', process.env.QA_ADMIN_PASSWORD); await p.click('button[type=submit]'); await p.waitForURL(/\/admin$/, { timeout: 15000 }).catch(() => issues.push(w + ' login failed'));
    for (const u of ['', '/properties', '/properties/create', '/projects', '/projects/create', '/developers', '/developers/create', '/locations', '/locations/create', '/amenities', '/property-types', '/leads', '/faqs', '/blog-posts', '/users', '/settings', '/audit-logs']) {
      const r = await p.goto(B + '/admin' + u, { waitUntil: 'networkidle' }); 
      const over = await p.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth + 2);
      if (r.status() >= 400) issues.push(`${w} /admin${u} → ${r.status()}`);
      if (over && w === 1280) issues.push(`${w} /admin${u} overflow`);
      const name = (u || 'dash').replace(/\W+/g, '_'); if ((w === 1280 && ['', '/properties', '/properties/create', '/leads'].includes(u)) || (w === 375 && ['', '/properties'].includes(u))) await p.screenshot({ path: `/tmp/shots/admin_${w}_${name}.png` });
    }
  }
  console.log('ISSUES', issues.length); issues.forEach(i => console.log(i)); await b.close();
})();
