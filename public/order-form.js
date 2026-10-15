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
let confirmedPayload = null;
let sent = false;
let serial = 0;
function update(row) {
 const product = row.querySelector('.item-product');
 const quantity = row.querySelector('.item-quantity');
 const milling = row.querySelector('.item-milling');
 const spec = catalog.find(p => p.name === product.value && p.active);
 const branched = !!spec?.milling;
 quantity.max = spec?.max_units || 999;
 row.querySelector('.item-spec').textContent = spec ? spec.unit_label + ' × 口数' + (spec.max_units ? '（上限' + spec.max_units + '口）' : '') : '商品を選ぶと規格が表示されます。';
 row.querySelector('.item-milling-label').hidden = !branched;
 milling.disabled = !branched;
 product.required = quantity.value !== '' || kind.value === '注文の相談';
 quantity.required = product.value !== '';
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
  row.remove(); relabel(); add.focus(); status.textContent = '商品欄を削除しました。';
 });
 items.append(row); update(row); relabel();
 if (focus) row.querySelector('.item-product').focus();
 return row;
}
add.addEventListener('click', () => addRow('', true));
kind.addEventListener('change', () => { [...items.children].forEach(update); document.querySelector('#region').required = kind.value === '注文の相談'; });
document.querySelectorAll('[data-product]').forEach(button => button.addEventListener('click', () => {
 const product = button.dataset.product;
 if (!catalog.some(p => p.name === product && p.active)) { status.textContent = product + 'は現在受付を停止しています。ご質問は相談内容へお書きください。'; return; }
 let row = [...items.children].find(r => r.querySelector('.item-product').value === product)
   || [...items.children].find(r => !r.querySelector('.item-product').value && !r.querySelector('.item-quantity').value);
 if (row) row.querySelector('.item-product').value = product;
 else row = addRow(product);
 if (!row) { status.textContent = '商品欄は10件までです。内容欄に追加のご希望をお書きください。'; return; }
 kind.value = '注文の相談'; [...items.children].forEach(update);
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
 if (!chosen.length && (kind.value === '注文の相談' || !document.querySelector('#message').value.trim())) {
  errors.textContent = '商品と数量、またはご相談内容を入力してください。'; errors.hidden = false; errors.focus(); return;
 }
 confirmedPayload = {
  submission_key: document.querySelector('#submission-key').value,
  kind: kind.value, name: document.querySelector('#name').value.trim(), email: document.querySelector('#email').value.trim(),
  region: document.querySelector('#region').value || null, message: document.querySelector('#message').value.trim() || null,
  privacy: document.querySelector('#privacy').checked, website: document.querySelector('#website').value,
  items: chosen.map(row => ({product:row.querySelector('.item-product').value,quantity:String(Number(row.querySelector('.item-quantity').value) * catalog.find(p => p.name === row.querySelector('.item-product').value).unit_kg),milling:row.querySelector('.item-product').value === '分づき米' ? row.querySelector('.item-milling').value : null})),
 };
 for (const key of ['name','email','kind','region','message']) document.querySelector('#out-'+key).textContent = document.querySelector('#'+key).value || '未入力';
 const output = document.querySelector('#out-items'); output.replaceChildren();
 for (const row of items.children) {
  const product = row.querySelector('.item-product').value;
  if (!product) continue;
  const entry = document.createElement('li');
  const spec = catalog.find(p => p.name === product);
  entry.textContent = product + '：' + spec.unit_label + ' × ' + row.querySelector('.item-quantity').value + '口'
   + (product === '分づき米' ? '（' + row.querySelector('.item-milling').value + '）' : '');
  output.append(entry);
 }
 if (!output.children.length) { const entry=document.createElement('li'); entry.textContent='商品指定なし'; output.append(entry); }
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
  document.querySelector('#confirmation-details').hidden = true;
  document.querySelector('#confirmation-title').textContent = 'お問い合わせを受け付けました';
  output.textContent = '受付番号：' + data.receipt + '\n注文はまだ確定していません。' + (data.development ? '\n開発環境のため、メールは外部送信されていません。' : data.notification_pending ? '\n内容は保存済みですが、通知処理は未完了です。受付番号をお控えください。' : '\n返信先メールアドレスへの受付メールもご確認ください。');
  document.querySelector('.close-bottom').textContent = '閉じる';
  status.textContent = '受付済み：' + data.receipt + '。新しい受付はページを再読み込みしてください。';
 } catch (error) {
  output.textContent = ''; failure.textContent = error instanceof TypeError ? '接続を確認できませんでした。同じ内容で再試行できます。' : error.message; failure.hidden = false;
 } finally { button.disabled = sent; }
});
document.querySelectorAll('.close,.close-bottom').forEach(button => button.addEventListener('click',()=>dialog.close()));
addRow();
