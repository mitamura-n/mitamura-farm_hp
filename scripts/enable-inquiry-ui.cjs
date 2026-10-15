const fs=require('node:fs');const path=require('node:path');const file=path.join(__dirname,'../resources/views/farm.blade.php');let s=fs.readFileSync(file,'utf8');
s=s.replace(/<dialog id="confirmation"[\s\S]*?<\/dialog>/,"@include('inquiry-confirmation')");
s=s.replace('brand-theme.css?v=8','brand-theme.css?v=9').replace('/order-form.js?v=1','/order-form.js?v=2');
s=s.replace('ロゴ・住所・メールを反映しました。価格は暫定設定です。フォームは送信されません。','開発用プレビューです。受付はテスト用の情報でお試しください。保存は行われますが、メールは外部送信されません。');
s=s.replace('現在のプレビューでは送信されません。','開発中はテスト用の情報だけをご入力ください。');
s=s.replace('このページは制作プレビューです。注文の送信や決済はできません。受付先を確認後、正式な注文導線を設定します。','フォームからご希望をお知らせいただき、在庫・金額のご案内にご了承いただいてから確定します。このページは開発中のため、実際のご注文は受け付けていません。');
fs.writeFileSync(file,s);
