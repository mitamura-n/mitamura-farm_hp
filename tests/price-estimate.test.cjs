const test = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const context = vm.createContext({});
vm.runInContext(fs.readFileSync(require('node:path').join(__dirname,'../public/price-estimate.js'),'utf8'),context);
const estimate = context.estimateFarmPrice;
const catalog = [
 {name:'玄米', active:true, unit_kg:10, price_yen:10000},
 {name:'白米', active:true, unit_kg:10, price_yen:10000},
 {name:'分づき米', active:true, unit_kg:10, price_yen:10000},
 {name:'米粉', active:true, unit_kg:1, price_yen:1500},
];
const fees = {5:100,10:200,20:300,30:400};
test('mixed selection includes product prices and milling, excluding shipping', () => {
 const result=estimate([{product:'玄米',units:2},{product:'白米',units:1},{product:'米粉',units:2}],catalog,fees);
 assert.equal(result.products,33000); assert.equal(result.milling,200); assert.equal(result.subtotal,33200);
});
test('duplicate white rice rows use combined weight for milling', () => {
 assert.equal(estimate([{product:'白米',units:1},{product:'白米',units:1}],catalog,fees).milling,300);
});
test('unconfirmed milling weight does not fabricate a subtotal', () => {
 const result=estimate([{product:'白米',units:4}],catalog,fees);
 assert.equal(result.millingUnknown,true); assert.equal(result.subtotal,null);
});
test('unknown product price and missing quantity do not appear as a complete subtotal', () => {
 assert.equal(estimate([{product:'米粉',units:1}],[{...catalog[3],price_yen:null}],fees).subtotal,null);
 assert.equal(estimate([{product:'白米',units:''}],catalog,fees).subtotal,null);
});
test('milling grades are calculated separately and zero product price is valid', () => {
 assert.equal(estimate([{product:'分づき米',units:1,milling:'3分'},{product:'分づき米',units:1,milling:'5分'}],catalog,fees).milling,400);
 assert.equal(estimate([{product:'米粉',units:1}],[{...catalog[3],price_yen:0}],fees).subtotal,0);
});

const delivery = {destination:'福井県',rates:{'福井県':1300,'青森県':1400,'福岡県':1500,'北海道':1800,'沖縄県':2000}};
test('single rice 10kg and 20kg include a single 100-size shipment and correct milling', () => {
 const brown=estimate([{product:'玄米',units:1}],catalog,fees,delivery);
 assert.equal(brown.shipping.amount,1300); assert.equal(brown.total,11300);
 const white=estimate([{product:'白米',units:2}],catalog,fees,delivery);
 assert.equal(white.milling,300); assert.equal(white.total,21600);
 for(const [destination,shipping] of Object.entries(delivery.rates)) {
  assert.equal(estimate([{product:'分づき米',units:1,milling:'3分'}],catalog,fees,{...delivery,destination}).total,10200+shipping);
 }
});
test('islands, special packing, mixed products, duplicate rows and 30kg need a quote', () => {
 for(const rows of [[{product:'玄米',units:3}],[{product:'米粉',units:1}],
  [{product:'玄米',units:1},{product:'白米',units:1}],
  [{product:'玄米',units:1},{product:'玄米',units:1}]]) {
  const result=estimate(rows,catalog,fees,delivery);
  assert.equal(result.shipping.amount,null); assert.equal(result.total,null);
 }
 assert.equal(estimate([{product:'玄米',units:1}],catalog,fees,{...delivery,individual:true}).total,null);
});
test('no total is invented for missing destination, quantity, price or inactive products', () => {
 const rows=[{product:'玄米',units:1}];
 assert.equal(estimate(rows,catalog,fees,{...delivery,destination:''}).total,null);
 assert.equal(estimate(rows,catalog,fees,{...delivery,destination:'不明'}).total,null);
 assert.equal(estimate([{product:'玄米',units:''}],catalog,fees,delivery).total,null);
 assert.equal(estimate(rows,[{...catalog[0],price_yen:null}],fees,delivery).total,null);
 assert.equal(estimate(rows,[{...catalog[0],active:false}],fees,delivery).total,null);
});
