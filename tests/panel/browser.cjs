// Browser check of Toolkit's pages inside Simplify (checks.yml, job browser): a real browser logs in to a seeded panel
// (Simplify's audit seed) and opens a server's Settings page on a computer (1440 and 1100 wide) and a phone (390), and
// checks what the HTTP smoke cannot see:
// - the title and subtitle stand in one row with the buttons (ADDONS.md, "Page head"), not under an empty bar;
// - nothing makes the page scroll sideways (P-7);
// - the server icon's buttons stand under a label, not as a loose icon;
// - on a phone, the quick commands of a Minecraft console sit just above the command line, clear of the console's
//   title row.
// Writes a screenshot per screen to --out. The last line says "All steps passed" or lists the failures.
// node tests/panel/browser.cjs --base http://127.0.0.1:8001 --urls panel/urls.json --out result
const fs = require('fs'), path = require('path');
const { chromium } = require('playwright');
const arg = (n, d) => { const i = process.argv.indexOf('--' + n); return i > 0 ? process.argv[i + 1] : d; };
const base = arg('base', 'http://127.0.0.1:8001'), out = arg('out', 'result');
const urls = JSON.parse(fs.readFileSync(arg('urls'), 'utf8'));
const EMAIL = 'demo@example.com', PASSWORD = 'DemoPassword123!'; // the audit's seed.php
const SERVER = 'Survival SMP';
fs.mkdirSync(out, { recursive: true });
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const failures = [];
let page, stand;

const say = (step, ok, detail) => {
  console.log(`${ok ? 'PASS' : 'FAIL'} ${stand} ${step}${detail ? ' ' + JSON.stringify(detail) : ''}`);
  if (!ok) failures.push(`${stand} ${step}`);
};
const settle = async (ms = 800) => { await page.waitForLoadState('networkidle', { timeout: 12000 }).catch(() => {}); await sleep(ms); };
const goto = async (url) => { await page.goto(url.startsWith('http') ? url : base + url, { waitUntil: 'domcontentloaded' }); await settle(); };

async function login() {
  await goto('/login');
  await page.fill('#form\\.login', EMAIL);
  await page.fill('#form\\.password', PASSWORD);
  await page.click('button[type="submit"]');
  await page.waitForURL((u) => !u.pathname.includes('/login'), { timeout: 30000 }).catch(() => {});
  await settle();
  if (page.url().includes('/login')) throw new Error('login failed');
}

// What the page shows, measured in the browser.
const measure = () => page.evaluate(() => {
  const box = (el) => { if (!el) return null; const r = el.getBoundingClientRect(); return { top: Math.round(r.top), bottom: Math.round(r.bottom), left: Math.round(r.left), right: Math.round(r.right) }; };
  const name = (el) => el.tagName.toLowerCase() + (el.id ? '#' + el.id : '') + [...el.classList].slice(0, 4).map((c) => '.' + c).join('');
  const shown = (el) => !!el && el.getClientRects().length > 0 && getComputedStyle(el).visibility !== 'hidden';
  const form = document.querySelector('form.sm-settings');
  const title = form && form.querySelector('.fi-section-header-heading');
  const actions = [...document.querySelectorAll('.fi-header-actions-ctn')].find(shown) || null;
  // Every box in the page's content that can scroll sideways and does, with what sticks out of it.
  const sideways = [];
  for (const el of [document.documentElement, ...document.querySelectorAll('.fi-main, .fi-main *')]) {
    const style = getComputedStyle(el);
    const scrolls = el === document.documentElement || ['auto', 'scroll'].includes(style.overflowX);
    if (!scrolls || el.scrollWidth <= el.clientWidth + 1 || el.closest('.sm-settings-contents, .sm-command, .fi-modal, .fi-dropdown-panel, .fi-ta-content')) continue;
    const edge = el.getBoundingClientRect().left + el.clientWidth;
    const out = [...el.querySelectorAll('*')].filter((child) => shown(child) && child.getBoundingClientRect().right > edge + 1).slice(-3).map((child) => ({ el: name(child), right: Math.round(child.getBoundingClientRect().right), edge: Math.round(edge) }));
    sideways.push({ el: name(el), scrollWidth: el.scrollWidth, clientWidth: el.clientWidth, out });
  }
  // The server icon's upload button (Pelican's UploadIcon) and the label of the field it stands in.
  const upload = [...document.querySelectorAll('form.sm-settings button')].find((b) => shown(b) && /icon/i.test((b.getAttribute('x-tooltip') || '') + (b.getAttribute('aria-label') || '') + (b.getAttribute('title') || '')));
  const field = upload && upload.closest('.sm-settings-icon-field');
  return { title: title ? title.textContent.trim() : null, titleBox: box(title), actionsBox: box(actions), sideways, upload: !!upload, iconLabel: field ? (field.querySelector('.sm-settings-icon-label')?.textContent.trim() || '') : null };
});

async function run(browser, viewport) {
  stand = viewport.name;
  const context = await browser.newContext({ viewport: { width: viewport.width, height: viewport.height }, colorScheme: 'dark', deviceScaleFactor: 1 });
  page = await context.newPage();
  page.on('pageerror', (e) => say('page error', false, { message: String(e.message).slice(0, 200) }));
  await login();
  const entry = (urls.server[SERVER] || []).find((e) => e.class === 'Simplify\\Toolkit\\Filament\\Server\\Pages\\ServerSettings');
  say('the seed has the Settings page of a Minecraft server', !!entry, { server: SERVER });
  if (!entry) return context.close();
  await goto(entry.url);
  await page.screenshot({ path: path.join(out, `${stand}-settings.png`) }).catch(() => {});
  // A small copy in the log as well, to look at without downloading the artifact.
  const small = await page.screenshot({ type: 'jpeg', quality: 45 }).catch(() => null);
  if (small) console.log(`SHOT ${stand} ${small.toString('base64')}`);
  const m = await measure();
  console.log(`INFO ${stand} ${JSON.stringify(m)}`);
  const oneRow = m.titleBox && m.actionsBox && m.titleBox.top < m.actionsBox.bottom && m.actionsBox.top < m.titleBox.bottom;
  say('the title stands in one row with the buttons', !!oneRow, { title: m.titleBox, buttons: m.actionsBox });
  if (m.titleBox && m.actionsBox) say('the buttons do not cover the title', m.titleBox.right <= m.actionsBox.left || m.actionsBox.right <= m.titleBox.left, { title: m.titleBox, buttons: m.actionsBox });
  say('nothing scrolls sideways', m.sideways.length === 0, m.sideways.slice(0, 3));
  say('the server icon has a label', !m.upload || !!m.iconLabel, { label: m.iconLabel });
  if (stand === 'phone390') await quickCommands();
  await context.close();
}

async function quickCommands() {
  await goto(urls.servers[SERVER]);
  await page.waitForSelector('#terminal > .sm-quick-commands', { timeout: 15000 }).catch(() => {});
  await sleep(500);
  await page.screenshot({ path: path.join(out, `${stand}-console.png`) }).catch(() => {});
  const small = await page.screenshot({ type: 'jpeg', quality: 45 }).catch(() => null);
  if (small) console.log(`SHOT ${stand}-console ${small.toString('base64')}`);
  const m = await page.evaluate(() => {
    const box = (el) => { if (!el) return null; const r = el.getBoundingClientRect(); return { top: Math.round(r.top), bottom: Math.round(r.bottom), left: Math.round(r.left), right: Math.round(r.right) }; };
    const input = document.getElementById('send-command');
    return {
      row: box(document.querySelector('#terminal > .sm-quick-commands')),
      title: box(document.querySelector('#terminal > .ps-console-title')),
      terminal: box(document.getElementById('terminal')),
      line: box(input && (input.closest('div:has(> #send-command)') || input)),
    };
  });
  console.log(`INFO ${stand} console ${JSON.stringify(m)}`);
  say('a Minecraft console has quick commands', !!m.row, m);
  if (!m.row) return;
  say('the quick commands keep clear of the console title', !m.title || m.row.top >= m.title.bottom, { row: m.row, title: m.title });
  say('the quick commands sit just above the command line', !!m.line && m.row.bottom <= m.line.top + 1 && m.line.top - m.row.bottom <= 2 && m.row.top >= m.terminal.top, { row: m.row, line: m.line });
}

(async () => {
  const browser = await chromium.launch();
  try {
    for (const viewport of [{ name: 'd1440', width: 1440, height: 900 }, { name: 'd1100', width: 1100, height: 640 }, { name: 'phone390', width: 390, height: 844 }]) {
      await run(browser, viewport).catch((e) => say('run', false, { error: String(e.message).slice(0, 300) }));
    }
  } finally {
    await browser.close();
  }
  console.log(failures.length ? `FAIL ${failures.length}: ${failures.join(', ')}` : 'All steps passed');
})();
