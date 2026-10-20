<dialog id="confirmation" aria-labelledby="confirmation-title">
 <button class="close" aria-label="閉じる">×</button>
 <p class="eyebrow">お問い合わせ内容の確認</p>
 <h2 id="confirmation-title">この内容でよろしいですか？</h2>
 <div id="confirmation-details"><dl>
  <dt>お名前</dt><dd id="out-name"></dd>
  <dt>返信先メールアドレス</dt><dd id="out-email"></dd>
  <dt>ご用件</dt><dd id="out-kind"></dd>
  <dt>ご希望の商品・数量</dt><dd><ul id="out-items" class="confirmed-items"></ul></dd>
  <dt>お届け先の都道府県</dt><dd id="out-region"></dd>
  <dt>ご相談内容</dt><dd id="out-message"></dd>
 </dl><p>受付後、金額などをメールでご案内します。注文はまだ確定しません。</p>
 <button id="send-inquiry" type="button" class="button">この内容で受付する →</button></div>
 <p id="send-status" role="status" aria-live="polite"></p>
 <div id="send-errors" class="form-errors" role="alert" hidden></div>
 <div id="receipt-followup" hidden><p></p><a class="button" href="/?new-inquiry=1#contact">新しいお問い合わせを入力する</a></div>
 <button type="button" class="close-bottom">入力画面に戻る</button>
</dialog>
