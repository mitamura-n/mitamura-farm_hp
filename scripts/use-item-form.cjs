const fs=require('node:fs');const path=require('node:path');const p=path.join(__dirname,'../resources/views/farm.blade.php');let s=fs.readFileSync(p,'utf8');
s=s.replace(/<form id="request-form">[\s\S]*?<\/form>/,"@include('request-form')");
s=s.replace(/<dt>商品<\/dt>[\s\S]*?<dt>お届け先<\/dt>/,'<dt>ご希望の商品・数量</dt><dd><ul id="out-items" class="confirmed-items"></ul></dd><dt>お届け先</dt>');
s=s.replace('<script src="app.js?v=2"></script><script src="/purchase-flow.js?v=1"></script>','<script src="/order-form.js?v=1"></script>');
s=s.replace('brand-theme.css?v=7','brand-theme.css?v=8');fs.writeFileSync(p,s);
