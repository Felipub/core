const { test: base, expect } = require('/tools/node_modules/@playwright/test');
const fixtureURL = process.env.FIXTURE_URL;
if (!['http://fixture-master:8090','http://fixture-manjeet:8090'].includes(fixtureURL)) throw Error('Disposable fixture sidecar required');
const test = base.extend({
  fixture: [async ({ playwright }, use) => {
    const api = await playwright.request.newContext({ baseURL: fixtureURL });
    const reset = await api.post('/reset', { timeout: 120000 });
    expect(reset.ok(), await reset.text()).toBeTruthy();
    const baseline=await api.get('/state');
    expect(baseline.ok()).toBeTruthy();
    expect((await baseline.json()).gibbonPerson.find(r=>+r.gibbonPersonID===4).firstName).toBe('Student');
    await use({
      state: async () => { const r=await api.get('/state'); expect(r.ok()).toBeTruthy(); return r.json(); },
      theme: async name => { const r=await api.post(`/theme?name=${name}`); expect(r.ok(),await r.text()).toBeTruthy(); },
      candidate: async () => { const r=await api.post('/candidate'); expect(r.ok(),await r.text()).toBeTruthy(); return r.json(); },
      workbook: async buffer => { const r=await api.post('/workbook',{multipart:{file:{name:'report.xlsx',mimeType:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',buffer}}}); expect(r.ok(),await r.text()).toBeTruthy(); return r.json(); },
    });
    await api.dispose();
  }, { auto: true }],
  networkGuard: [async ({ context, baseURL }, use) => {
    await context.route('**/*', route => {
      const url=new URL(route.request().url());
      return url.origin===baseURL || ['data:','blob:'].includes(url.protocol) ? route.continue() : route.abort();
    });
    await use();
  }, { auto: true }],
});
async function login(page, username='admin') {
  await page.goto('/');
  await page.locator('input[name="username"]').fill(username);
  await page.locator('input[name="password"]').fill('Sandbox-Only-2026!');
  await Promise.all([page.waitForNavigation(),page.locator('#loginForm [type="submit"]').click()]);
  await expect(page.locator('input[name="password"]')).toHaveCount(0);
  await healthy(page);
}
async function healthy(page) {
  await expect(page.locator('body')).not.toContainText(/Fatal error|Uncaught (?:Error|Exception)|SQLSTATE\[|Something has gone wrong: the Gibbons have escaped|An error has occurred\./);
}
async function open(page, path, params={}) {
  await page.goto(`/index.php?${new URLSearchParams({q:path,...params})}`);
  await healthy(page);
}
async function submit(page, form) {
  try {
    await Promise.all([page.waitForNavigation({timeout:30000}),form.locator('[type="submit"]').last().click()]);
  } catch (error) {
    const invalid=await form.locator('.LV_invalid_field, :invalid').evaluateAll(nodes=>nodes.map(n=>({name:n.name,value:n.value,message:n.validationMessage})));
    throw Error(`Form did not navigate. Invalid fields: ${JSON.stringify(invalid)}. ${error.message}`);
  }
  await healthy(page);
}
async function rosterIds(page) {
  return page.locator('a[href*="student_view_details.php"]').evaluateAll(links=>[...new Set(links.map(a=>Number(new URL(a.href).searchParams.get('gibbonPersonID'))).filter(Boolean))].sort((a,b)=>a-b));
}
module.exports={test,expect,login,open,submit,healthy,rosterIds};
