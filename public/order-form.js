const menu = document.querySelector('.menu');
const nav = document.querySelector('nav');
menu.addEventListener('click', () => {
 const open = menu.getAttribute('aria-expanded') !== 'true';
 menu.setAttribute('aria-expanded', String(open)); nav.classList.toggle('open', open);
});
nav.querySelectorAll('a').forEach(a => a.addEventListener('click', () => {
 nav.classList.remove('open'); menu.setAttribute('aria-expanded', 'false');
}));
const form = document.querySelector('#request-form');
const items = document.querySelector('#request-items');
const add = document.querySelector('#add-item');
const kind = document.querySelector('#kind');
const status = document.querySelector('#selection-status');
const errors = document.querySelector('#form-errors');
const catalog = JSON.parse(document.querySelector('#product-master').textContent);
const millingFees = JSON.parse(document.querySelector('#milling-fees').textContent);
const shippingRates = JSON.parse(document.querySelector('#shipping-rates').textContent);
const destination = document.querySelector('#region');
const individualShipping = document.querySelector('#individual-shipping');
const individualShippingNote = '離島へのお届け・袋分けなどの個別対応を希望します。';
function renderPrice(target) {
 const rows = [...items.children].map(row => ({
  product: row.querySelector('.item-product').value,
  units: row.querySelector('.item-quantity').value,
  milling: row.querySelector('.item-milling').value,
 }));
 const estimate = estimateFarmPrice(rows, catalog, millingFees, {destination:destination.value, rates:shippingRates, individual:individualShipping.checked});
 target.replaceChildren();
 const paragraph = (text, className = '') => { const p = document.createElement('p'); p.textContent = text; p.className = className; target.append(p); };
 const yen = amount => amount.toLocaleString('ja-JP') + '円';
 if (!estimate.lines.length) {
  paragraph('商品・数量・お届け先を選ぶと、金額の目安を表示します。');
  return;
 }
 paragraph('金額の目安（税込）');
 for (const line of estimate.lines) paragraph(line.name + ' ' + farmQuantityLabel(catalog.find(p => p.name === line.name), line.units) + '：' + (line.amount === null ? '価格は個別にご案内' : yen(line.amount)));
 if (estimate.millingUnknown) {
  paragraph('精米料：お見積もりでご案内' + (estimate.milling > 0 ? '（一部の目安：' + yen(estimate.milling) + '）' : ''));
 } else paragraph('精米料：' + yen(estimate.milling));
 if (estimate.incomplete) paragraph('未選択の商品・数量や、個別見積もりの商品があります。');
 paragraph(estimate.subtotal === null
  ? '商品代金の目安：' + yen(estimate.products) + '（価格をご案内できる商品のみ）'
  : '小計（目安）：' + yen(estimate.subtotal));
 if (estimate.shipping.amount !== null) paragraph('送料（目安）：' + yen(estimate.shipping.amount));
 else paragraph(estimate.shipping.reason === 'destination' ? '送料：お届け先を選んでください' : '送料：個別にお見積もりします');
 if (estimate.total !== null) paragraph('送料込みの目安：' + yen(estimate.total), 'price-total');
 paragraph('送料の自動表示は、お米1種類・10kgまたは20kgの通常配送が対象です。離島・袋分けなどは個別にご案内します。');
 paragraph('振込・払込手数料、代引き手数料は別途必要です。最終金額はメールでご案内し、ご了承後に注文を確定します。');
}
destination.addEventListener('change', () => renderPrice(document.querySelector('#price-estimate')));
individualShipping.addEventListener('change', () => renderPrice(document.querySelector('#price-estimate')));
let confirmedPayload = null;
let sent = false;
const receiptPanel = document.createElement('div');
receiptPanel.className = 'pending';
receiptPanel.hidden = true;
receiptPanel.tabIndex = -1;
form.after(receiptPanel);
function showReceipt(receipt, notice) {
 receiptPanel.replaceChildren();
 const heading = document.createElement('h3');
 heading.textContent = 'お問い合わせを受け付けました';
 const summary = document.createElement('p');
 summary.textContent = '受付番号：' + receipt + '。注文はまだ確定していません。';
 const guidance = document.createElement('p');
 guidance.textContent = notice;
 const restart = document.createElement('a');
 restart.className = 'button';
 restart.href = '/?new-inquiry=1#contact';
 restart.textContent = '新しいお問い合わせを入力する';
 receiptPanel.append(heading, summary, guidance, restart);
 form.hidden = true;
 form.style.display = 'none';
 receiptPanel.hidden = false;
}
let serial = 0;
function update(row) {
 const product = row.querySelector('.item-product');
 const quantity = row.querySelector('.item-quantity');
 const milling = row.querySelector('.item-milling');
 const spec = catalog.find(p => p.name === product.value && p.active);
 const branched = !!spec?.milling;
 if (quantity.dataset.product !== product.value) {
  quantity.replaceChildren(new Option(spec ? '数量を選択してください' : '先に商品を選んでください', ''));
  if (spec) {
   const options = document.createDocumentFragment();
   for (const units of farmQuantityChoices(spec)) options.append(new Option(farmQuantityLabel(spec, units), String(units)));
   quantity.append(options);
  }
  quantity.dataset.product = product.value;
 }
 quantity.disabled = !spec;
 row.querySelector('.item-milling-label').hidden = !branched;
 milling.disabled = !branched;
 product.required = quantity.value !== '' || kind.value === '購入希望・お見積もり';
 quantity.required = product.value !== '';
 renderPrice(document.querySelector('#price-estimate'));
}
function relabel() {
 const rows = [...items.children];
 rows.forEach((row, index) => {
  row.querySelector('legend').textContent = '商品 ' + (index + 1);
  row.querySelector('.remove-item').hidden = rows.length === 1;
  row.querySelector('.remove-item').setAttribute('aria-label', '商品 ' + (index + 1) + ' の入力欄を削除');
 });
 add.disabled = rows.length >= 10;
}
function addRow(product = '', focus = false) {
 if (items.children.length >= 10) return null;
 const row = document.querySelector('#item-template').content.firstElementChild.cloneNode(true);
 const id = ++serial;
 for (const type of ['product','quantity','milling']) {
  const input = row.querySelector('.item-' + type);
  input.id = 'item-' + id + '-' + type;
  row.querySelector('.item-' + type + '-label').htmlFor = input.id;
 }
 row.querySelector('.item-product').value = product;
 row.addEventListener('input', () => update(row));
 row.addEventListener('change', () => update(row));
 row.querySelector('.remove-item').addEventListener('click', () => {
  row.remove(); relabel(); renderPrice(document.querySelector('#price-estimate')); add.focus(); status.textContent = '商品欄を削除しました。';
 });
 items.append(row); update(row); relabel();
 if (focus) row.querySelector('.item-product').focus();
 return row;
}
add.addEventListener('click', () => addRow('', true));
kind.addEventListener('change', () => { [...items.children].forEach(update); document.querySelector('#region').required = kind.value === '購入希望・お見積もり'; });
document.querySelectorAll('[data-product]').forEach(button => button.addEventListener('click', () => {
 const product = button.dataset.product;
 if (!catalog.some(p => p.name === product && p.active)) { status.textContent = product + 'は現在受付を停止しています。ご質問は相談内容へお書きください。'; return; }
 let row = [...items.children].find(r => r.querySelector('.item-product').value === product)
   || [...items.children].find(r => !r.querySelector('.item-product').value && !r.querySelector('.item-quantity').value);
 if (row) row.querySelector('.item-product').value = product;
 else row = addRow(product);
 if (!row) { status.textContent = '商品欄は10件までです。内容欄に追加のご希望をお書きください。'; return; }
 kind.value = '購入希望・お見積もり'; [...items.children].forEach(update);
 document.querySelector('#region').required = true;
 status.textContent = product + 'を選択しました。商品ごとの数量をご入力ください。';
 document.querySelector('#contact').scrollIntoView({behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'auto':'smooth'});
}));
const dialog = document.querySelector('#confirmation');
form.addEventListener('submit', event => {
 event.preventDefault();
 errors.hidden = true;
 if (sent) { dialog.showModal(); return; }
 const chosen = [...items.children].filter(row => row.querySelector('.item-product').value);
 if (!chosen.length && (kind.value === '購入希望・お見積もり' || !document.querySelector('#message').value.trim())) {
  errors.textContent = '商品と数量、またはご相談内容を入力してください。'; errors.hidden = false; errors.focus(); return;
 }
 const message = [individualShipping.checked ? individualShippingNote : '', document.querySelector('#message').value.trim()].filter(Boolean).join('\n');
 if (message.length > 2000) { errors.textContent = '個別対応のご希望を含め、ご相談内容は2,000文字以内で入力してください。'; errors.hidden = false; errors.focus(); return; }
 confirmedPayload = {
  submission_key: document.querySelector('#submission-key').value,
  kind: kind.value, name: document.querySelector('#name').value.trim(), email: document.querySelector('#email').value.trim(),
  region: document.querySelector('#region').value || null, message: message || null,
  privacy: document.querySelector('#privacy').checked, website: document.querySelector('#website').value,
  items: chosen.map(row => ({product:row.querySelector('.item-product').value,quantity:String(Number(row.querySelector('.item-quantity').value) * catalog.find(p => p.name === row.querySelector('.item-product').value).unit_kg),milling:row.querySelector('.item-product').value === '分づき米' ? row.querySelector('.item-milling').value : null})),
 };
 for (const key of ['name','email','kind','region']) document.querySelector('#out-'+key).textContent = document.querySelector('#'+key).value || '未入力';
 document.querySelector('#out-message').textContent = confirmedPayload.message || '未入力';
 const output = document.querySelector('#out-items'); output.replaceChildren();
 for (const row of items.children) {
  const product = row.querySelector('.item-product').value;
  if (!product) continue;
  const entry = document.createElement('li');
  const spec = catalog.find(p => p.name === product);
  entry.textContent = product + '：' + farmQuantityLabel(spec, row.querySelector('.item-quantity').value)
   + (product === '分づき米' ? '（' + row.querySelector('.item-milling').value + '）' : '');
  output.append(entry);
 }
 if (!output.children.length) { const entry=document.createElement('li'); entry.textContent='商品指定なし'; output.append(entry); }
 renderPrice(document.querySelector('#confirmation-price'));
 dialog.showModal();
});

document.querySelector('#send-inquiry').addEventListener('click', async () => {
 if (!confirmedPayload || sent) return;
 const button = document.querySelector('#send-inquiry');
 const output = document.querySelector('#send-status');
 const failure = document.querySelector('#send-errors');
 button.disabled = true; failure.hidden = true; output.textContent = '受付内容を保存しています…';
 try {
  const response = await fetch(form.action, {method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':form.querySelector('[name="_token"]').value},body:JSON.stringify(confirmedPayload)});
  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
   const message = response.status === 422 ? Object.values(data.errors || {}).flat().join('\n') : response.status === 429 ? '短時間に受付が集中しています。1分ほど待ってからお試しください。' : response.status === 419 ? '有効期限が切れました。入力内容を控え、ページを再読み込みしてください。' : response.status === 409 ? '送信済みの内容と異なります。新しい受付はページを再読み込みしてください。' : '受付を完了できませんでした。時間をおいて再度お試しください。';
   throw new Error(message);
  }
  sent = true;
  const notice = data.development
   ? '開発環境のためメールは送信されていません。内容の訂正・追加は、受付番号を添えて農園へメールでお知らせください。'
   : data.notification_pending
    ? '内容は保存済みですが、通知処理は未完了です。内容の訂正・追加は、受付番号を添えて農園へメールでお知らせください。'
    : '内容の訂正・追加は受付メールへ返信してください。別件は下のボタンから入力できます。';
  showReceipt(data.receipt, notice);
  document.querySelector('#confirmation-details').hidden = true;
  document.querySelector('#confirmation-title').textContent = 'お問い合わせを受け付けました';
  output.textContent = '受付番号：' + data.receipt + '\n注文はまだ確定していません。' + (data.development ? '\n開発環境のため、メールは外部送信されていません。' : data.notification_pending ? '\n内容は保存済みですが、通知処理は未完了です。受付番号をお控えください。' : '\n返信先メールアドレスへの受付メールもご確認ください。');
  document.querySelector('.close-bottom').textContent = '閉じる';
  const followup = document.querySelector('#receipt-followup');
  followup.hidden = false;
  followup.querySelector('p').textContent = notice;
 } catch (error) {
  output.textContent = ''; failure.textContent = error instanceof TypeError ? '接続を確認できませんでした。同じ内容で再試行できます。' : error.message; failure.hidden = false;
 } finally { button.disabled = sent; }
});
dialog.addEventListener('close', () => { if (sent) receiptPanel.focus(); });
document.querySelectorAll('.close,.close-bottom').forEach(button => button.addEventListener('click',()=>dialog.close()));
if (new URLSearchParams(location.search).get('new-inquiry') === '1') form.reset();
addRow();

document.querySelector('#region').required = kind.value === '購入希望・お見積もり';
