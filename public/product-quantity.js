(function (root) {
 root.farmQuantityLabel = function (product, units) {
  const weight = product.unit_kg * Number(units);
  if (product.name === '玄米') return weight + 'kg';
  if (['白米', '分づき米'].includes(product.name)) return '玄米' + weight + 'kg分';
  return units + '袋（' + (Number(units) === 1 ? product.unit_kg + 'kg入り' : '計' + weight + 'kg') + '）';
 };
})(globalThis);
