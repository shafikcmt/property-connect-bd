const { chromium } = require(require('child_process').execSync('npm root -g').toString().trim() + '/playwright');
(async () => {
  const lighthouse = (await import('lighthouse')).default;
  const url = process.argv[2]; const ff = process.argv[3] || 'mobile';
  const b = await chromium.launch({ args: ['--remote-debugging-port=9334'] });
  const r = await lighthouse('http://127.0.0.1:8099' + url, { port: 9334, output: 'json', logLevel: 'error', formFactor: ff, screenEmulation: ff === 'desktop' ? { mobile: false, width: 1350, height: 940, deviceScaleFactor: 1, disabled: false } : undefined });
  const l = r.lhr; console.log(url, ff, Object.entries(l.categories).map(([k, v]) => k + ':' + Math.round(v.score * 100)).join(' '), 'LCP', l.audits['largest-contentful-paint'].displayValue, 'CLS', l.audits['cumulative-layout-shift'].displayValue, 'TBT', l.audits['total-blocking-time'].displayValue);
  for (const id of (process.argv[4] || 'color-contrast,target-size,heading-order,label-content-name-mismatch,is-crawlable,layout-shifts,link-name,image-alt,meta-description,robots-txt').split(',')) {
    const a = l.audits[id]; if (!a || a.score === 1 || a.score === null) continue;
    console.log('\n##', id, a.score, a.title);
    (a.details?.items || []).slice(0, 8).forEach(i => console.log('  ', JSON.stringify(i.node ? { s: i.node.selector, snip: (i.node.snippet || '').slice(0, 110), why: (i.node.explanation || '').slice(0, 220) } : i).slice(0, 380)));
  }
  await b.close();
})();
