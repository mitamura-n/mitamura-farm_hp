@if(in_array(config('mail.default'), ['log', 'array'], true))
メールは外部送信されず、開発用に記録されます。
@elseif(config('farm.restrict_mail_recipients'))
テストメールは許可された宛先にだけ送信されます。返信先には農園のテスト用メールアドレスをご入力ください。
@else
農園への通知と、返信先への受付メールを送信します。
@endif
