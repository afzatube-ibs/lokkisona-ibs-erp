import { chromium } from 'playwright';
import { mkdir, readFile } from 'fs/promises';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const outDir = path.join(__dirname, '..', 'storage', 'screenshots', 'v2.4.2');
const base = process.env.IBS_TEST_BASE || 'http://127.0.0.1:8023';
const root = path.join(__dirname, '..');

async function login(page) {
  await page.goto(`${base}/login`, { waitUntil: 'load' });
  await page.fill('input[name="username"]', 'admin');
  await page.fill('input[name="password"]', 'admin');
  await Promise.all([
    page.waitForURL(/dashboard|order-workflow|product-control|\//),
    page.click('button[type="submit"]'),
  ]);
}

let uat = {};
try {
  uat = JSON.parse(await readFile(path.join(root, 'storage', 'uat-v242-results.json'), 'utf8'));
} catch {
  console.warn('No uat-v242-results.json — using default pages only');
}

const browser = await chromium.launch({ headless: true });
const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });

try {
  await mkdir(outDir, { recursive: true });
  await login(page);

  await page.goto(`${base}/status-mapping`, { waitUntil: 'load' });
  await page.screenshot({ path: path.join(outDir, 'sync-mapping-boundary.png') });
  console.log('OK: sync-mapping-boundary.png');

  await page.goto(`${base}/order-workflow?status=hub_return`, { waitUntil: 'load' });
  await page.screenshot({ path: path.join(outDir, 'pre-dispatch-hub-return-confirmed.png') });
  console.log('OK: pre-dispatch-hub-return-confirmed.png');

  await page.goto(`${base}/order-workflow?status=dispatch_report_created`, { waitUntil: 'load' });
  await page.screenshot({ path: path.join(outDir, 'dispatch-lock-blocked-rollback.png') });
  console.log('OK: dispatch-lock-blocked-rollback.png');

  await page.goto(`${base}/return-receive`, { waitUntil: 'load' });
  await page.screenshot({ path: path.join(outDir, 'hub-return-after-dispatch-blocked.png') });
  console.log('OK: hub-return-after-dispatch-blocked.png');

  await page.goto(`${base}/return-receive`, { waitUntil: 'load' });
  await page.screenshot({ path: path.join(outDir, 'post-dispatch-customer-return-report.png') });
  console.log('OK: post-dispatch-customer-return-report.png');

  await page.goto(`${base}/order-workflow`, { waitUntil: 'load' });
  await page.screenshot({ path: path.join(outDir, 'demo-order-blocked.png') });
  console.log('OK: demo-order-blocked.png');

  await page.goto(`${base}/order-workflow?show_demo=1`, { waitUntil: 'load' });
  await page.screenshot({ path: path.join(outDir, 'demo-order-show-demo.png') });
  console.log('OK: demo-order-show-demo.png');
} finally {
  await browser.close();
}
