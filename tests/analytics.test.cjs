const test = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const source = fs.readFileSync('public/analytics.js', 'utf8');
const key = 'farm-analytics-consent-v1';

function setup({ saved, host = 'mitamurafarm.com', path = '/', storageFails = false } = {}) {
 const elements = {};
 for (const name of ['analytics-config', 'analytics-choice', 'analytics-settings', 'analytics-allow', 'analytics-decline']) {
  elements[name] = {textContent:JSON.stringify({measurementId:'G-TEST123'}), hidden:true,
   setAttribute() {}, addEventListener(type, callback) { this[type] = callback; }};
 }
 const storage = new Map(saved ? [[key, JSON.stringify(saved)]] : []);
 const scripts = [];
 const cookieWrites = [];
 const document = {referrer:'https://www.google.com/search?q=private@example.com#secret',
  querySelector(selector) { return elements[selector.slice(1)]; },
  createElement() { return {}; }, head:{append(tag) { scripts.push(tag); }} };
 Object.defineProperty(document, 'cookie', { get() { return '_ga=old; _ga_TEST123=old; session=untouched'; }, set(value) { cookieWrites.push(value); } });
 const window = { addEventListener(type, callback) { this[type] = callback; } };
 const sandbox = {document, window, URL, Date,
  localStorage:{getItem(k) { if (storageFails) throw Error(); return storage.get(k) || null; }, setItem(k,v) { if (storageFails) throw Error(); storage.set(k,v); }},
  location:{hostname:host, pathname:path, search:'?email=private@example.com', hash:'#MF-secret'},
 };
 vm.runInNewContext(source, sandbox);
 return {window, elements, storage, scripts, cookieWrites,
  events:() => Array.from(window.dataLayer || [], args => Array.from(args)).filter(args => args[0] === 'event')};
}

test('unknown or declined consent loads no Google tag and sends no event', () => {
 for (const saved of [undefined, {allowed:false, at:Date.now()}]) {
  const s = setup({saved});
  assert.equal(s.scripts.length, 0);
  s.window.farmAnalytics.inquiryAccepted();
  assert.equal(s.events().length, 0);
  assert.equal(s.elements['analytics-choice'].hidden, !!saved);
 }
});
test('opt in sends only fixed paths, titles, referrer origin and one real lead', () => {
 const s = setup();
 s.elements['analytics-allow'].click();
 assert.equal(s.scripts.length, 1);
 assert.equal(s.scripts[0].src, 'https://www.googletagmanager.com/gtag/js?id=G-TEST123');
 assert.equal(s.scripts[0].referrerPolicy, 'no-referrer');
 s.window.farmAnalytics.inquiryAccepted({name:'private', email:'private@example.com', receipt:'MF-secret'});
 s.window.farmAnalytics.inquiryAccepted();
 assert.deepEqual(s.events().map(x => x[1]), ['page_view', 'generate_lead']);
 const payload = s.events()[1][2];
 assert.deepEqual(Object.keys(payload).sort(), ['page_location','page_referrer','page_title','send_to']);
 assert.equal(payload.page_location, 'https://mitamurafarm.com/');
 assert.equal(payload.page_referrer, 'https://www.google.com/');
 const all = JSON.stringify(s.window.dataLayer.map(args => Array.from(args)));
 assert.doesNotMatch(all, /private@example|MF-secret|email=/);
 assert.match(all, /"allow_google_signals":false/);
 assert.match(all, /"ad_user_data":"denied"/);
});
test('revoking and cross-tab revocation stop leads and only clear analytics cookies', () => {
 for (const crossTab of [false, true]) {
  const s = setup({saved:{allowed:true, at:Date.now()}});
  if (crossTab) {
   s.storage.set(key, JSON.stringify({allowed:false, at:Date.now()}));
   s.window.storage({key});
  } else s.elements['analytics-decline'].click();
  const count = s.events().length;
  s.window.farmAnalytics.inquiryAccepted();
  assert.equal(s.events().length, count);
  assert.equal(s.window['ga-disable-G-TEST123'], true);
  assert.ok(s.cookieWrites.some(x => x.startsWith('_ga=')));
  assert.ok(!s.cookieWrites.some(x => x.startsWith('session=')));
 }
});
test('expired preference asks again, including expiry while a page stays open', () => {
 const expired = {allowed:true, at:Date.now() - 181 * 86400000};
 const s = setup({saved:expired});
 assert.equal(s.scripts.length, 0);
 assert.equal(s.elements['analytics-choice'].hidden, false);
 s.elements['analytics-allow'].click();
 s.storage.set(key, JSON.stringify(expired));
 s.window.farmAnalytics.inquiryAccepted();
 assert.equal(s.events().length, 1);
 assert.equal(s.window['ga-disable-G-TEST123'], true);
});
test('storage failures still allow a page-local choice without affecting the form', () => {
 const s = setup({storageFails:true});
 s.elements['analytics-allow'].click();
 s.window.farmAnalytics.inquiryAccepted();
 assert.equal(s.events().length, 2);
 s.elements['analytics-decline'].click();
 assert.equal(s.window['ga-disable-G-TEST123'], true);
});
test('no analytics module exists on admin, unknown pages or non-production hosts', () => {
 for (const options of [{host:'preview.mitamurafarm.com'}, {host:'localhost'}, {path:'/admin/inquiries/42'}, {path:'/unknown'}]) {
  const s = setup(options);
  assert.equal(s.window.farmAnalytics, undefined);
  assert.equal(s.scripts.length, 0);
 }
});
