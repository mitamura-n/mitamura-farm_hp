document.querySelectorAll('[data-product]').forEach(button => button.addEventListener('click', () => {
 const product = button.dataset.product;
 document.querySelector('#milling').value = ['玄米','白米'].includes(product) ? product : product === '分づき米' ? '分づき米（度合いは内容欄へ）' : '未定・指定なし';
 document.querySelector('#selection-status').textContent = product + 'が選択されています。数量やお届け先をご入力ください。';
}));
document.querySelector('#request-form').addEventListener('submit', () => {
 for (const key of ['quantity','milling','region']) {
  document.querySelector('#out-'+key).textContent = document.querySelector('#'+key).value || '未入力';
 }
});
