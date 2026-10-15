const fs = require('node:fs');
const path = require('node:path');
const target = path.join(__dirname, '../resources/views/farm.blade.php');
let html = fs.readFileSync(target, 'utf8');
if (!html.includes('brand-theme.css')) {
  html = html.replace('<link rel="stylesheet" href="style.css">', '<link rel="stylesheet" href="style.css"><link rel="stylesheet" href="/brand-theme.css?v=1">');
}
fs.writeFileSync(target, html);
