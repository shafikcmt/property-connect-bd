const { chromium } = require(require('child_process').execSync('npm root -g').toString().trim() + '/playwright');
const [sp, sj] = require('fs').readFileSync('/tmp/qa/slugs.txt', 'utf8').trim().split(' ');
(async () => { const b = await chromium.launch(); const p = await (await b.newContext({ viewport: { width: 1280, height: 900 } })).newPage(); const v = [];
 p.on('console', m => { if (/Content Security Policy|violat/i.test(m.text())) v.push(m.text().slice(0, 200)); });
 for (const u of ['/', '/properties', '/properties/' + sp, '/projects/' + sj, '/contact', '/faq', '/blog', '/login', '/compare']) { await p.goto('http://127.0.0.1:8099' + u, { waitUntil: 'networkidle' }); await p.waitForTimeout(300); }
 await p.click('button[aria-label="Search"]'); await p.keyboard.type('gul'); await p.waitForTimeout(600);
 console.log('CSP violations:', v.length); v.slice(0, 5).forEach(x => console.log(' -', x)); await b.close(); })();
