// Consultation estimate only: never an authoritative order or payment amount.
(function (root) {
 function shippingEstimate(rows, catalog, destination, rates, individual) {
  if (individual) return {amount: null, reason: 'individual'};
  if (!destination) return {amount: null, reason: 'destination'};
  const selected = rows.filter(row => row.product);
  if (selected.length !== 1) return {amount: null, reason: 'combination'};
  const row = selected[0];
  const product = catalog.find(p => p.active && p.name === row.product);
  const units = Number(row.units);
  if (!product || !['玄米','白米','分づき米'].includes(product.name) || product.unit_kg !== 10
   || ![1,2].includes(units) || units > (product.max_units || 999)) return {amount: null, reason: 'combination'};
  const amount = rates?.[destination];
  return Number.isSafeInteger(amount) && amount >= 0 ? {amount, reason: null} : {amount: null, reason: 'destination'};
 }
 function estimate(rows, catalog, fees, delivery = {}) {
  const lines = [];
  const millingGroups = new Map();
  let products = 0, milling = 0, incomplete = false;
  for (const row of rows) {
   const product = catalog.find(p => p.active && p.name === row.product);
   const units = Number(row.units);
   if (!product) { if (row.product) incomplete = true; continue; }
   if (!Number.isSafeInteger(units) || units < 1 || units > (product.max_units || 999)) {
    incomplete = true; continue;
   }
   const amount = Number.isSafeInteger(product.price_yen) && product.price_yen >= 0
    ? product.price_yen * units : null;
   if (amount === null) incomplete = true;
   else products += amount;
   lines.push({name: product.name, units, amount});
   if (['白米', '分づき米'].includes(product.name)) {
    // Different milling grades may need separate processing; do not combine them.
    const key = product.name + ':' + (row.milling || '');
    millingGroups.set(key, (millingGroups.get(key) || 0) + product.unit_kg * units);
   }
  }
  let millingUnknown = false;
  for (const weight of millingGroups.values()) {
   if (Object.prototype.hasOwnProperty.call(fees, weight)) milling += fees[weight];
   else millingUnknown = true;
  }
  const subtotal = !incomplete && !millingUnknown ? products + milling : null;
  const shipping = shippingEstimate(rows, catalog, delivery.destination, delivery.rates, delivery.individual);
  return {lines, products, milling, millingUnknown, incomplete, subtotal, shipping,
   total: subtotal !== null && shipping.amount !== null ? subtotal + shipping.amount : null};
 }
 if (typeof module !== 'undefined' && module.exports) module.exports = estimate;
 else root.estimateFarmPrice = estimate;
})(globalThis);
