const { chromium } = require(require('child_process').execSync('npm root -g').toString().trim() + '/playwright');
const B = 'http://127.0.0.1:8098', IMG = '/tmp/qa/img/';
const log = []; const ok = (n, c, x = '') => { log.push((c ? 'PASS' : 'FAIL') + '  ' + n + ' ' + x); };
const T = Date.now().toString().slice(-5);
async function pick(p, label, text) {
  const wrap = p.locator('.fi-fo-field', { has: p.locator('label', { hasText: new RegExp('^\\s*' + label) }) }).first();
  await wrap.locator('button, [role=combobox], input').first().click(); await p.waitForTimeout(250);
  await p.getByRole('option', { name: new RegExp(text) }).first().click(); await p.waitForTimeout(200);
}
async function upload(p, files, collection = 'Gallery image') {
  await p.getByRole('button', { name: /Upload/ }).first().click(); await p.waitForTimeout(600);
  await p.locator('input[type=file]').last().setInputFiles(files); await p.waitForTimeout(2500);
  await p.locator('[role=dialog], .fi-modal-window').getByRole('button', { name: 'Submit' }).click(); await p.waitForTimeout(3500);
}
(async () => {
  const b = await chromium.launch(); const ctx = await b.newContext({ viewport: { width: 1280, height: 1000 } }); const p = await ctx.newPage();
  const errs = []; p.on('pageerror', e => errs.push(e.message.slice(0, 120)));
  await p.goto(B + '/admin/login'); await p.fill('input[type=email]', 'qa-admin@example.test'); await p.fill('input[type=password]', process.env.QA_ADMIN_PASSWORD); await p.click('button[type=submit]'); await p.waitForURL(/\/admin$/);
  const devName = `E2E Developers ${T}`, projName = `E2E Residence ${T}`, propTitle = `E2E Lakeview Apartment ${T}`;

  // 1 developer
  await p.goto(B + '/admin/developers/create'); await p.waitForLoadState('networkidle');
  await p.getByLabel('Name').first().fill(devName); await p.getByLabel('About').fill('E2E Developers is a QA fixture company used to exercise the admin and public website end to end.');
  await p.getByRole('button', { name: 'Create', exact: true }).first().click(); await p.waitForURL(/developers\/\d+\/edit/); ok('create developer', true);
  await p.getByRole('button', { name: 'Publish' }).first().click(); await p.waitForTimeout(500);
  await p.locator('.fi-modal-window:visible').getByRole('button', { name: 'Confirm' }).click(); await p.waitForTimeout(1500);
  ok('publish blocked without logo', await p.getByText(/Not ready to publish/).first().isVisible().catch(() => false));
  await upload(p, IMG + 'logo.png'); await p.screenshot({ path: '/tmp/shots/e2e_media.png' });
  ok('logo uploaded (media row)', await p.locator('table').last().locator('tbody tr').count() >= 1);
  await p.getByRole('button', { name: 'Publish' }).first().click(); await p.waitForTimeout(500); await p.locator('.fi-modal-window:visible').getByRole('button', { name: 'Confirm' }).click(); await p.waitForTimeout(1500);
  ok('developer published', await p.getByText('Published', { exact: true }).first().isVisible().catch(() => false) || !(await p.getByRole('button', { name: 'Publish' }).first().isVisible().catch(() => false)));
  await p.getByRole('button', { name: 'Mark verified' }).first().click(); await p.waitForTimeout(500); await p.getByLabel(/How was this verified/).fill('QA document check'); await p.locator('.fi-modal-window:visible').getByRole('button', { name: /Confirm|Submit/ }).click(); await p.waitForTimeout(1500);
  ok('developer verified', await p.getByRole('button', { name: 'Revoke verification' }).first().isVisible().catch(() => false));

  // 2 project
  await p.goto(B + '/admin/projects/create'); await p.waitForLoadState('networkidle');
  await p.getByLabel('Name').first().fill(projName); await pick(p, 'Developer', devName); await pick(p, 'Location', 'Gulshan');
  await p.getByLabel('Description', { exact: true }).fill('A QA fixture project used to verify end-to-end publishing. ' + 'It has detailed text so that the publish validator is satisfied with the length requirement. '.repeat(2));
  await p.getByRole('button', { name: 'Create', exact: true }).first().click(); await p.waitForURL(/projects\/\d+\/edit/); ok('create project', true);
  await upload(p, [IMG + 'e2e0.jpg', IMG + 'e2e1.jpg', IMG + 'e2e2.jpg']);
  await p.getByRole('button', { name: 'Publish' }).first().click(); await p.waitForTimeout(500); await p.locator('.fi-modal-window:visible').getByRole('button', { name: 'Confirm' }).click(); await p.waitForTimeout(2000);
  const projSlug = projName.toLowerCase().replace(/[^a-z0-9]+/g, '-');
  const pr = await ctx.request.get(B + '/projects/' + projSlug); ok('project visible publicly', pr.status() === 200, '(' + pr.status() + ')');

  // 3 property
  await p.goto(B + '/admin/properties/create'); await p.waitForLoadState('networkidle');
  await p.getByLabel('Title').first().fill(propTitle); await pick(p, 'Property type', 'Apartment'); await pick(p, 'Location', 'Gulshan'); await pick(p, 'Developer', devName);
  await p.getByLabel('Description', { exact: true }).fill('A bright three bedroom E2E apartment with lake views, great light and a short walk to shops, schools and transport.');
  await p.getByRole('tab', { name: /Price/ }).click(); await p.locator('input[id="form.price"]').fill('9500000'); await p.locator('input[id="form.size_sqft"]').fill('1450'); await p.locator('input[id="form.bedrooms"]').fill('3'); await p.locator('input[id="form.bathrooms"]').fill('2');
  await p.getByRole('button', { name: 'Create', exact: true }).first().click(); await p.waitForURL(/properties\/\d+\/edit/); ok('create property', true);
  await p.getByRole('button', { name: 'Publish' }).first().click(); await p.waitForTimeout(500); await p.locator('.fi-modal-window:visible').getByRole('button', { name: 'Confirm' }).click(); await p.waitForTimeout(1500);
  ok('property publish blocked without image', await p.getByText(/Not ready to publish/).first().isVisible().catch(() => false));
  await upload(p, [IMG + 'e2e0.jpg', IMG + 'e2e3.jpg']);
  await p.getByRole('button', { name: 'Publish' }).first().click(); await p.waitForTimeout(500); await p.locator('.fi-modal-window:visible').getByRole('button', { name: 'Confirm' }).click(); await p.waitForTimeout(2000);
  const slug = propTitle.toLowerCase().replace(/[^a-z0-9]+/g, '-');

  // 4 public journey (fresh anonymous context)
  const pub = await (await b.newContext({ viewport: { width: 1280, height: 900 } })).newPage();
  await pub.goto(B + '/properties/' + slug); ok('property page loads', (await pub.title()).includes('E2E Lakeview'), await pub.title());
  ok('price & specs shown', await pub.getByText('95 Lac').first().isVisible() && await pub.getByText('1,450 sqft').first().isVisible());
  ok('gallery shows 2 photos', await pub.getByText('2 photos').first().isVisible());
  await pub.goto(B + '/properties?q=' + encodeURIComponent('Lakeview Apartment ' + T)); ok('keyword search finds it', await pub.getByText(propTitle).first().isVisible());
  await pub.goto(B + '/properties?beds=3&price_max=10000000&type[]=apartment'); ok('filters include it', await pub.getByText(propTitle).first().isVisible());
  await pub.goto(B + '/properties?beds=5'); ok('filters exclude it', !(await pub.getByText(propTitle).count()));
  await pub.goto(B + '/properties?q=' + encodeURIComponent(propTitle));
  await pub.locator('article', { hasText: propTitle }).getByRole('button', { name: /favorites/i }).click(); await pub.waitForTimeout(700);
  await pub.goto(B + '/favorites'); ok('favorite saved (guest)', await pub.getByText(propTitle).first().isVisible());
  await pub.goto(B + '/properties?q=' + encodeURIComponent(propTitle)); await pub.locator('article', { hasText: propTitle }).getByLabel('Compare').check(); await pub.waitForTimeout(600);
  await pub.goto(B + '/properties?beds=3'); const other = pub.locator('article').filter({ hasNotText: propTitle }).first(); if (await other.count()) await other.getByLabel('Compare').check(); await pub.waitForTimeout(600);
  await pub.goto(B + '/compare'); ok('compare shows 2 columns', (await pub.locator('thead th').count()) >= 3);
  await pub.goto(B + '/properties/' + slug);
  const f = pub.locator('#inquiry'); await f.getByLabel('Full name').fill('Rahim QA'); await f.getByLabel(/Phone/).fill('01712345678'); await f.getByLabel(/I agree to be contacted/).check(); await pub.waitForTimeout(3500); await f.getByRole('button', { name: /Send inquiry/ }).click(); await pub.waitForTimeout(1000);
  ok('inquiry confirmation shown', await pub.getByText(/received your request/).first().isVisible());
  await pub.goto(B + '/properties/' + slug); const v = pub.locator('#visit'); const d = new Date(Date.now() + 5 * 864e5).toISOString().slice(0, 10);
  await v.getByLabel('Preferred date').fill(d); await v.getByLabel('Full name').fill('Visit QA'); await v.getByLabel(/Phone/).fill('01812345678'); await v.getByLabel(/I agree to be contacted/).check(); await pub.waitForTimeout(3500); await v.getByRole('button', { name: /Request site visit/ }).click(); await pub.waitForTimeout(1000);
  ok('site visit confirmation shown', await pub.getByText(/site visit request has been received/).first().isVisible());

  // 5 admin sees inquiries
  await p.goto(B + '/admin/leads'); await p.waitForLoadState('networkidle'); ok('inquiry inbox lists both', (await p.getByText('Rahim QA').count()) >= 1 && (await p.getByText('Visit QA').count()) >= 1);
  await p.screenshot({ path: '/tmp/shots/e2e_leads.png' });

  // 6 owner submission
  const ow = await (await b.newContext({ viewport: { width: 1280, height: 1000 } })).newPage();
  await ow.goto(B + '/login'); await ow.fill('input[name=email]', 'qa-buyer@example.test'); await ow.fill('input[name=password]', process.env.QA_BUYER_PASSWORD); await ow.getByRole('button', { name: 'Log in' }).click(); await ow.waitForURL(/account/);
  await ow.goto(B + '/submit-property'); const sub = `E2E Owner Flat ${T}`;
  await ow.getByLabel('Listing title').fill(sub); await ow.getByLabel('Property type').selectOption({ label: 'Flat' }); await ow.getByLabel('Price (৳)').fill('6500000'); await ow.getByLabel('Size (sqft)').fill('1100');
  await ow.getByLabel(/Description/).fill('A well kept owner submitted flat close to everything you need for daily life, with good light and ventilation.');
  await ow.locator('input[type=file]').setInputFiles([IMG + 'e2e1.jpg']); await ow.getByLabel('Your name').fill('QA Owner'); await ow.getByLabel('Phone').last().fill('01911111111'); await ow.getByLabel(/I am authorised/).check();
  await ow.getByRole('button', { name: 'Submit for review' }).click(); await ow.waitForURL(/submissions/); ok('owner submission accepted (pending)', await ow.getByText('Under review').first().isVisible());
  const subSlug = sub.toLowerCase().replace(/[^a-z0-9]+/g, '-'); ok('pending submission NOT public', (await ctx.request.get(B + '/properties/' + subSlug)).status() === 404);
  await p.goto(B + '/admin/properties?tableFilters[publish_status][value]=pending_review'); await p.waitForLoadState('networkidle'); await p.waitForTimeout(1500); await p.screenshot({ path: '/tmp/shots/e2e_pending.png' }); ok('admin sees submission pending', (await p.locator('text=Pending Review').count()) > 0);
  await p.getByText(sub).first().click(); await p.waitForURL(/edit/); await p.waitForLoadState('networkidle'); await p.getByRole('button', { name: 'Publish' }).first().click(); await p.waitForTimeout(600); await p.locator('.fi-modal-window:visible').getByRole('button', { name: 'Confirm' }).click(); await p.waitForTimeout(1500); ok('publish gate asks admin to add a location', await p.getByText(/Choose a location/).first().isVisible().catch(() => false)); await pick(p, 'Location', 'Gulshan'); await p.getByRole('button', { name: 'Save changes' }).first().click(); await p.waitForTimeout(1500); await p.getByRole('button', { name: 'Publish' }).first().click(); await p.waitForTimeout(500); await p.locator('.fi-modal-window:visible').getByRole('button', { name: 'Confirm' }).click(); await p.waitForTimeout(2000);
  ok('approved submission is public', (await ctx.request.get(B + '/properties/' + subSlug)).status() === 200);

  log.forEach(l => console.log(l)); console.log('JS errors:', errs.length, errs.slice(0, 3)); await b.close();
})().catch(e => { log.forEach(l => console.log(l)); console.log('ABORT:', e.message.slice(0, 500)); process.exit(1); });
