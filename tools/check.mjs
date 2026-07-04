import { promises as fs } from 'node:fs';
import path from 'node:path';
import process from 'node:process';

const root = process.cwd();
const failures = [];

const requiredFiles = [
  'README.md',
  'README.zh-CN.md',
  'LICENSE',
  '.gitignore',
  'ddys_open/conf.json',
  'ddys_open/icon.png',
  'ddys_open/install.php',
  'ddys_open/unstall.php',
  'ddys_open/upgrade.php',
  'ddys_open/setting.php',
  'ddys_open/hook/model_inc_start.php',
  'ddys_open/hook/index_inc_start.php',
  'ddys_open/hook/index_inc_route_before.php',
  'ddys_open/hook/header_link_after.htm',
  'ddys_open/hook/header_nav_home_link_after.htm',
  'ddys_open/route/ddys.php',
  'ddys_open/source/bootstrap.php',
  'ddys_open/source/security.php',
  'ddys_open/source/cache.php',
  'ddys_open/source/client.php',
  'ddys_open/source/render.php',
  'ddys_open/source/shortcode.php',
  'ddys_open/static/css/frontend.css',
  'ddys_open/static/css/admin.css',
  'ddys_open/static/js/frontend.js',
  'ddys_open/static/js/admin.js',
  'ddys_open/static/images/icon-16.png',
  'ddys_open/static/images/icon-32.png',
  'ddys_open/static/images/icon-192.png',
  'ddys_open/static/images/icon-512.png',
  'ddys_open/static/images/logo.png'
];

const shortcodes = [
  'ddys_movies',
  'ddys_latest',
  'ddys_hot',
  'ddys_search',
  'ddys_suggest',
  'ddys_calendar',
  'ddys_movie',
  'ddys_sources',
  'ddys_related',
  'ddys_comments',
  'ddys_collections',
  'ddys_collection',
  'ddys_shares',
  'ddys_share',
  'ddys_requests',
  'ddys_activities',
  'ddys_user',
  'ddys_types',
  'ddys_genres',
  'ddys_regions',
  'ddys_request_form'
];

for (const file of requiredFiles) await mustExist(file);
await checkConf();
await checkHooks();
await checkSource();
await checkDocs();
await checkIcons();
await checkForbiddenFiles();
await checkForbiddenText();

if (failures.length > 0) {
  console.error(failures.map((failure) => `- ${failure}`).join('\n'));
  process.exit(1);
}

console.log(JSON.stringify({ ok: true, files: (await listFiles(root)).length, shortcodes: shortcodes.length }, null, 2));

async function checkConf() {
  const conf = JSON.parse(await read('ddys_open/conf.json'));
  assert(conf.name === '低端影视 API', 'conf.json name should be Chinese and precise.');
  assert(conf.have_setting === 1, 'conf.json must enable setting page.');
  for (const hook of ['model_inc_start.php', 'index_inc_route_before.php', 'header_link_after.htm', 'header_nav_home_link_after.htm']) {
    assert(conf.hooks_rank && Object.prototype.hasOwnProperty.call(conf.hooks_rank, hook), `conf.json missing hook rank ${hook}`);
  }
}

async function checkHooks() {
  const modelHook = await read('ddys_open/hook/model_inc_start.php');
  const routeHook = await read('ddys_open/hook/index_inc_route_before.php');
  const navHook = await read('ddys_open/hook/header_nav_home_link_after.htm');
  assert(modelHook.includes('ddys_open_bootstrap'), 'model hook must load bootstrap.');
  assert(routeHook.includes('ddys_open_is_plugin_route') && routeHook.includes('SKIP_ROUTE'), 'route hook must intercept configured ddys route.');
  assert(navHook.includes('ddys_open_nav_item'), 'nav hook must render navigation item.');
}

async function checkSource() {
  const security = await read('ddys_open/source/security.php');
  const client = await read('ddys_open/source/client.php');
  const render = await read('ddys_open/source/render.php');
  const shortcode = await read('ddys_open/source/shortcode.php');
  const setting = await read('ddys_open/setting.php');
  const route = await read('ddys_open/route/ddys.php');
  const frontendJs = await read('ddys_open/static/js/frontend.js');
  const adminJs = await read('ddys_open/static/js/admin.js');

  for (const shortcodeName of shortcodes) {
    assert(shortcode.includes(`'${shortcodeName}'`), `shortcode.php missing ${shortcodeName}`);
  }
  assert(setting.includes('ddys_open_shortcodes()') && setting.includes('data-ddys-generator-type'), 'setting generator must read shortcode definitions dynamically.');

  for (const marker of ['ddys_open_nonce', 'ddys_open_verify_nonce', 'ddys_open_hash_equals', 'DDYS_OPEN_XIUNO_SETTING_KEY', 'ddys_open_reserved_routes', 'ddys_open_route_base', 'ddys_open_is_plugin_route', 'ddys_open_admin_front_url', 'ddys_open_strlen']) {
    assert(security.includes(marker), `security.php missing ${marker}`);
  }
  assert(security.includes("preg_match('#^[a-zA-Z0-9_]+$#'"), 'frontend base path must avoid Xiuno hyphen/slash routing conflicts.');
  assert(security.includes("'thread'") && security.includes("'forum'") && security.includes("'user'"), 'frontend base path must reject Xiuno core routes.');
  assert(security.includes('function ddys_open_route_tail') && route.includes('ddys_open_route_tail(2'), 'detail routes must rebuild hyphenated slugs from Xiuno URL segments.');
  for (const marker of ['ddys_open_proxy_response', 'ddys_open_allowed_route', 'ddys_open_handle_request_form', 'Authorization: Bearer']) {
    assert(client.includes(marker), `client.php missing ${marker}`);
  }
  for (const marker of ['年份格式无效', '类型参数无效', '豆瓣 ID 格式无效', 'IMDb ID 格式无效', '备注不能超过 1000 个字符']) {
    assert(client.includes(marker), `request form server validation missing ${marker}`);
  }
  assert(client.includes("!ini_get('open_basedir')"), 'curl redirect handling must respect open_basedir.');
  for (const marker of ['ddys_open_render_page', 'ddys_open_render_request_form', 'ddys_open_frontend_assets', 'ddys_open_nav_item', 'ddys_open_render_calendar', 'ddys_collection']) {
    assert(render.includes(marker), `render.php missing ${marker}`);
  }
  assert(route.includes('ddys_open_proxy_response') && route.includes('ddys_open_handle_request_form'), 'route must expose proxy and request endpoints.');
  assert(setting.includes('action="<?php echo ddys_open_attr($base_url); ?>"'), 'setting form must post to clean base URL.');
  assert(setting.includes('ddys_open_admin_can_remote_request') && setting.includes('当前配置检查') && setting.includes('清理缓存'), 'setting page missing robust diagnostics.');
  assert(setting.includes('data-ddys-generator-kind') && setting.includes('data-ddys-proxy-url') && setting.includes('data-ddys-page-collection'), 'setting generator must support shortcode/page/proxy output.');
  assert(frontendJs.includes('!window.fetch') && frontendJs.includes('FormData'), 'frontend request JS must gracefully fallback without fetch/FormData.');
  assert(adminJs.includes("kind === 'page'") && adminJs.includes("kind === 'proxy'") && adminJs.includes('data-ddys-generator-output'), 'admin generator JS is incomplete.');

  for (const full of (await listFiles(path.join(root, 'ddys_open'))).filter((file) => file.endsWith('.php'))) {
    const rel = path.relative(root, full).replace(/\\/g, '/');
    await checkBalancedPhp(rel);
  }
}

async function checkDocs() {
  const zh = await read('README.zh-CN.md');
  const en = await read('README.md');
  assert(zh.includes('[低端影视](https://ddys.io/) API'), 'Chinese README must link low-end movie API text.');
  assert(en.includes('[DDYS](https://ddys.io/) API'), 'English README must link DDYS API text.');
  for (const shortcodeName of ['ddys_latest', 'ddys_movie', 'ddys_request_form']) {
    assert(zh.includes(shortcodeName), `Chinese README missing ${shortcodeName}`);
    assert(en.includes(shortcodeName), `English README missing ${shortcodeName}`);
  }
  for (const marker of ['?ddys-api.htm', '?ddys-request-submit.htm', '?ddys-collection-editor-choice.htm', 'RewriteRule ^ddys/?$', 'rewrite ^/ddys/?$']) {
    assert(zh.includes(marker), `Chinese README missing route marker ${marker}`);
    assert(en.includes(marker), `English README missing route marker ${marker}`);
  }
}

async function checkIcons() {
  for (const [rel, size] of [
    ['ddys_open/static/images/icon-16.png', 16],
    ['ddys_open/static/images/icon-32.png', 32],
    ['ddys_open/static/images/icon-192.png', 192],
    ['ddys_open/static/images/icon-512.png', 512],
    ['ddys_open/icon.png', 192]
  ]) {
    const buffer = await fs.readFile(path.join(root, rel));
    assert(buffer.toString('ascii', 1, 4) === 'PNG', `${rel} must be PNG.`);
    assert(buffer.readUInt32BE(16) === size && buffer.readUInt32BE(20) === size, `${rel} must be ${size}x${size}.`);
  }
}

async function checkForbiddenFiles() {
  const files = await listFiles(root);
  for (const full of files) {
    const rel = path.relative(root, full).replace(/\\/g, '/');
    if (/(^|\/)(\.env|node_modules|vendor|tmp|cache)(\/|$)/i.test(rel) || /\.(zip|log|bak)$/i.test(rel)) {
      failures.push(`Forbidden file: ${rel}`);
    }
  }
}

async function checkForbiddenText() {
  const files = await listFiles(root);
  const patterns = ['ghp' + '_', 'npm' + '_', 'OpenAI', 'AI Agent', 'GPT', 'Open' + ' API'];
  for (const full of files) {
    const rel = path.relative(root, full).replace(/\\/g, '/');
    if (/\.(png|jpg|jpeg|webp|gif)$/i.test(rel) || rel === 'tools/check.mjs') continue;
    const text = await read(rel);
    for (const pattern of patterns) {
      if (text.includes(pattern)) failures.push(`${rel} contains restricted text pattern ${pattern}`);
    }
  }
}

async function checkBalancedPhp(file) {
  const text = await read(file);
  assert(!text.startsWith('\uFEFF'), `${file} must not contain BOM.`);
  assert(!/\?>\s*$/.test(text), `${file} should omit closing PHP tag.`);
  const pairs = { '}': '{', ')': '(', ']': '[' };
  const stack = [];
  let quote = '';
  let escaped = false;
  for (let i = 0; i < text.length; i++) {
    const char = text[i];
    if (quote) {
      if (escaped) { escaped = false; continue; }
      if (char === '\\') { escaped = true; continue; }
      if (char === quote) quote = '';
      continue;
    }
    if (char === '"' || char === "'") { quote = char; continue; }
    if (char === '{' || char === '(' || char === '[') stack.push(char);
    if (char === '}' || char === ')' || char === ']') {
      const opener = stack.pop();
      if (opener !== pairs[char]) {
        failures.push(`${file} has mismatched bracket near offset ${i}.`);
        return;
      }
    }
  }
  assert(stack.length === 0, `${file} has unclosed bracket(s).`);
  assert(quote === '', `${file} has unterminated string.`);
}

async function mustExist(file) {
  try {
    await fs.access(path.join(root, file));
  } catch {
    failures.push(`Missing required file: ${file}`);
  }
}

async function read(file) {
  return fs.readFile(path.join(root, file), 'utf8');
}

async function listFiles(dir) {
  const entries = await fs.readdir(dir, { withFileTypes: true });
  const files = [];
  for (const entry of entries) {
    if (entry.name === '.git' || entry.name === 'node_modules' || entry.name === 'vendor') continue;
    const full = path.join(dir, entry.name);
    if (entry.isDirectory()) files.push(...await listFiles(full));
    else files.push(full);
  }
  return files;
}

function assert(condition, message) {
  if (!condition) failures.push(message);
}
