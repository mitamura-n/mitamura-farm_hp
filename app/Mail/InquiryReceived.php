<?php

namespace App\Mail;

use App\Models\Inquiry;
use Illuminate\Mail\Mailable;

class InquiryReceived extends Mailable
{
    public function __construct(public Inquiry $inquiry, public bool $forCustomer) {}

    public function build(): static
    {
        return $this->replyTo($this->forCustomer ? config('farm.email') : $this->inquiry->email)
            ->subject('[三田村農園 '.$this->inquiry->receipt.'] '.($this->forCustomer ? 'お問い合わせを受け付けました（注文未確定）' : '新しいお問い合わせ'))
            ->text('mail.inquiry-received');
    }
}
