const test = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const path = require('node:path');
const context=vm.createContext({});
vm.runInContext(fs.readFileSync(path.join(__dirname,'../public/product-quantity.js'),'utf8'),context);
test('rice quantities show weight without requiring unit multiplication',()=>{
 assert.equal(context.farmQuantityLabel({name:'玄米',unit_kg:10},2),'20kg');
 assert.equal(context.farmQuantityLabel({name:'白米',unit_kg:10},1),'玄米10kg分');
 assert.equal(context.farmQuantityLabel({name:'分づき米',unit_kg:5},3),'玄米15kg分');
});
test('bag quantities show bag count and weight using current master size',()=>{
 assert.equal(context.farmQuantityLabel({name:'米粉',unit_kg:1},1),'1袋（1kg入り）');
 assert.equal(context.farmQuantityLabel({name:'米ぬか',unit_kg:1},2),'2袋（計2kg）');
 assert.equal(context.farmQuantityLabel({name:'米粉',unit_kg:2},2),'2袋（計4kg）');
});
test('common rice choices stop at 60kg and respect master limits',()=>{
 assert.equal(JSON.stringify(context.farmQuantityChoices({name:'白米',unit_kg:10})), '[1,2,3,4,5,6]');
 assert.equal(JSON.stringify(context.farmQuantityChoices({name:'玄米',unit_kg:10,max_units:3})), '[1,2,3]');
 assert.equal(JSON.stringify(context.farmQuantityChoices({name:'分づき米',unit_kg:20})), '[1,2,3]');
});
test('bag choices stay short and respect smaller master limits',()=>{
 assert.equal(context.farmQuantityChoices({name:'米粉',unit_kg:1}).length,10);
 assert.equal(context.farmQuantityChoices({name:'米ぬか',unit_kg:1,max_units:4}).length,4);
});
