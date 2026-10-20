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
