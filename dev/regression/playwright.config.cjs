const { defineConfig } = require('/tools/node_modules/@playwright/test');
const baseURL = process.env.BASE_URL;
if (!['http://127.0.0.1:8081','http://127.0.0.1:8082'].includes(baseURL)) throw Error('Sandbox only');
const name = baseURL.endsWith('8081') ? 'master' : 'manjeet';
module.exports = defineConfig({
  testDir: '/tools/tests',
  outputDir: `/tools/results/${name}`,
  reporter: 'list',
  workers: 1,
  projects: [{ name, use: { browserName: 'chromium', baseURL, headless: true } }],
});
