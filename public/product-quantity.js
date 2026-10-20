(function (root) {
 root.farmQuantityChoices = function (product) {
  const max = product.max_units || 999;
  if (['玄米', '白米', '分づき米'].includes(product.name)) {
   return [10,20,30,40,50,60].filter(weight => weight % product.unit_kg === 0 && weight / product.unit_kg <= max).map(weight => weight / product.unit_kg);
  }
  return Array.from({length:Math.min(max,10)}, (_,i)=>i+1);
 };
 root.farmQuantityLabel = function (product, units) {
  const weight = product.unit_kg * Number(units);
  if (product.name === '玄米') return weight + 'kg';
  if (['白米', '分づき米'].includes(product.name)) return '玄米' + weight + 'kg分';
  return units + '袋（' + (Number(units) === 1 ? product.unit_kg + 'kg入り' : '計' + weight + 'kg') + '）';
 };
})(globalThis);
