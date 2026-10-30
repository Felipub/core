const { defineConfig } = require('/tools/node_modules/@playwright/test');
const baseURL = process.env.BASE_URL;
if (!['http://127.0.0.1:8081','http://127.0.0.1:8082'].includes(baseURL)) throw Error('Sandbox only');
const name = baseURL.endsWith('8081') ? 'master' : 'manjeet';
module.exports = defineConfig({
  testDir: '/tools/tests',
  outputDir: '/results/artifacts',
  reporter: [['list'], ['html', { outputFolder: '/results/html', open: 'never' }], ['junit', { outputFile: '/results/browser.xml' }]],
  timeout: 120000,
  retries: 0,
  workers: 1,
  projects: [{ name, use: { browserName: 'chromium', baseURL, headless: true, screenshot: 'only-on-failure', trace: 'retain-on-failure' } }],
});
