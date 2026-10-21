(() => {
 'use strict';
 const configNode = document.querySelector('#analytics-config');
 if (!configNode) return;
 let id;
 try { id = JSON.parse(configNode.textContent).measurementId; } catch { return; }
 const pages = new Set(['/', '/tokushoho', '/privacy', '/shipping']);
 if (!/^G-[A-Z0-9]+$/.test(id) || location.hostname !== 'mitamurafarm.com' || !pages.has(location.pathname)) return;
 const choice = document.querySelector('#analytics-choice');
 const settings = document.querySelector('#analytics-settings');
 const key = 'farm-analytics-consent-v1';
 const lifetime = 180 * 24 * 60 * 60 * 1000;
 let consent = null;
 let storageAvailable = true;
 let started = false;
 let pageSent = false;
 let leadSent = false;
 const readChoice = () => {
  try {
   const saved = JSON.parse(localStorage.getItem(key));
   return saved && typeof saved.allowed === 'boolean' && Number.isFinite(saved.at)
    && saved.at <= Date.now() && Date.now() - saved.at < lifetime ? saved.allowed : null;
  } catch { storageAvailable = false; return null; }
 };
 const showChoice = show => { choice.hidden = !show; settings.setAttribute('aria-expanded', String(show)); };
 const cleanReferrer = () => {
  try {
   const url = new URL(document.referrer);
   return ['http:', 'https:'].includes(url.protocol) && url.hostname !== location.hostname ? url.origin + '/' : '';
  } catch { return ''; }
 };
 // Only these fixed public paths and the referring origin are sent. No search/hash, DOM text or form values.
 const context = () => ({
  page_location: 'https://mitamurafarm.com' + location.pathname,
  page_referrer: cleanReferrer(),
  page_title: '三田村農園' + ({'/':'', '/tokushoho':'｜特定商取引法に基づく表記', '/privacy':'｜プライバシーポリシー', '/shipping':'｜送料・発送・返品'}[location.pathname]),
 });
 const command = (...args) => {
  window.dataLayer = window.dataLayer || [];
  // gtag expects an Arguments object, rather than arbitrary event payloads.
  (function () { window.dataLayer.push(arguments); })(...args);
 };
 const clearCookies = () => {
  for (const cookie of document.cookie.split(';')) {
   const name = cookie.split('=')[0].trim();
   if (name !== '_ga' && !name.startsWith('_ga_')) continue;
   for (const domain of ['', '; domain=mitamurafarm.com', '; domain=.mitamurafarm.com']) {
    document.cookie = name + '=; Max-Age=0; path=/' + domain + '; SameSite=Lax; Secure';
   }
  }
 };
 const start = () => {
  window['ga-disable-' + id] = false;
  if (!started) {
   started = true;
   command('consent', 'default', {analytics_storage:'granted', ad_storage:'denied', ad_user_data:'denied', ad_personalization:'denied'});
   command('set', {...context(), allow_google_signals:false, allow_ad_personalization_signals:false});
   command('js', new Date());
   command('config', id, {...context(), send_page_view:false, allow_google_signals:false, allow_ad_personalization_signals:false, cookie_expires:lifetime / 1000, cookie_update:false});
   const tag = document.createElement('script');
   tag.async = true;
   tag.src = 'https://www.googletagmanager.com/gtag/js?id=' + id;
   tag.referrerPolicy = 'no-referrer';
   document.head.append(tag);
  }
  if (!pageSent) { command('event', 'page_view', {...context(), send_to:id}); pageSent = true; }
 };
 const apply = allowed => {
  consent = allowed;
  if (allowed === true) start();
  else { window['ga-disable-' + id] = true; clearCookies(); }
  showChoice(allowed === null);
 };
 const choose = allowed => {
  try { localStorage.setItem(key, JSON.stringify({allowed, at:Date.now()})); } catch { storageAvailable = false; }
  apply(allowed);
 };
 settings.addEventListener('click', () => showChoice(choice.hidden));
 document.querySelector('#analytics-allow').addEventListener('click', () => choose(true));
 document.querySelector('#analytics-decline').addEventListener('click', () => choose(false));
 window.addEventListener('storage', event => { if (event.key === key || event.key === null) apply(readChoice()); });
 window.addEventListener('pageshow', () => { if (storageAvailable) apply(readChoice()); });
 // No argument is accepted: inquiry contents and receipt identifiers never enter the analytics module.
 window.farmAnalytics = Object.freeze({
  inquiryAccepted() {
   if (consent !== true || leadSent) return;
   if (storageAvailable && readChoice() !== true) { apply(readChoice()); return; }
   leadSent = true;
   command('event', 'generate_lead', {...context(), send_to:id});
  },
 });
 apply(readChoice());
})();
