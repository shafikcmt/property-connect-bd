const { chromium } = require(require('child_process').execSync('npm root -g').toString().trim() + '/playwright');
const fs = require('fs');
(async () => {
  const lighthouse = (await import('lighthouse')).default;
  const pages = JSON.parse(process.argv[2]);
  const out = {};
  for (const [name, url] of Object.entries(pages)) {
    const b = await chromium.launch({ args: ['--remote-debugging-port=9333'] });
    const r = await lighthouse('http://127.0.0.1:8099' + url, { port: 9333, output: 'json', logLevel: 'error', formFactor: process.argv[3] || 'mobile',
      screenEmulation: process.argv[3] === 'desktop' ? { mobile: false, width: 1350, height: 940, deviceScaleFactor: 1, disabled: false } : undefined,
      throttlingMethod: 'simulate', onlyCategories: ['performance', 'accessibility', 'best-practices', 'seo'] });
    const l = r.lhr; const c = l.categories; const a = l.audits;
    out[name] = { perf: Math.round(c.performance.score * 100), a11y: Math.round(c.accessibility.score * 100), bp: Math.round(c['best-practices'].score * 100), seo: Math.round(c.seo.score * 100),
      lcp: a['largest-contentful-paint'].displayValue, cls: a['cumulative-layout-shift'].displayValue, tbt: a['total-blocking-time'].displayValue, fcp: a['first-contentful-paint'].displayValue,
      fails: Object.values(a).filter(x => x.score !== null && x.score < 0.9 && x.scoreDisplayMode !== 'informative' && x.scoreDisplayMode !== 'notApplicable').map(x => x.id + ':' + Math.round(x.score*100)).slice(0, 14) };
    await b.close();
  }
  console.log(JSON.stringify(out, null, 1));
})();
