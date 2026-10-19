<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInquiryRequest;
use App\Mail\InquiryReceived;
use App\Models\Inquiry;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InquiryController extends Controller
{
    public function store(StoreInquiryRequest $request): JsonResponse
    {
        abort_unless(config('farm.inquiries_enabled'), 503, '受付は現在準備中です。');
        $data = $request->validated();
        abort_unless(hash_equals((string) $request->session()->get('inquiry_token', ''), $data['submission_key']), 419);
        $payload = collect($data)->only(['kind', 'name', 'email', 'items', 'region', 'message'])->all();
        $hash = hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE), config('app.key'));
        $existing = Inquiry::where('submission_key', $data['submission_key'])->first();
        if ($existing) return $this->duplicate($existing, $hash);

        try {
            // 受付時点の規格を保持し、マスタ変更後も過去の受付内容を変えない。
            $payload['items'] = array_map(function ($item) {
                $product = \App\Models\Product::where('name', $item['product'])->firstOrFail();
                return $item + ['quantity_text'=>$product->quantityText($item['quantity']), 'unit_kg'=>$product->unit_kg, 'unit_label'=>$product->unit_label];
            }, $payload['items']);
            $inquiry = Inquiry::create($payload + [
                'receipt' => 'MF-'.Str::ulid(), 'submission_key' => $data['submission_key'], 'payload_hash' => $hash, 'consented_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException $error) {
            $existing = Inquiry::where('submission_key', $data['submission_key'])->first();
            if (! $existing) throw $error;
            return $this->duplicate($existing, $hash);
        }

        foreach (['admin_notice' => false, 'customer_notice' => true] as $column => $forCustomer) {
            try {
                $recipient = $forCustomer ? $inquiry->email : config('farm.email');
                if (config('farm.restrict_mail_recipients') && ! in_array(config('mail.default'), ['log','array'], true)
                    && ! in_array(strtolower($recipient), array_map('strtolower', config('farm.mail_allowed_recipients')), true)) {
                    throw new \RuntimeException('Preview mail recipient is not approved.');
                }
                Mail::to($recipient)->send(new InquiryReceived($inquiry, $forCustomer));
                $inquiry->$column = in_array(config('mail.default'), ['log', 'array'], true) ? 'logged' : 'sent';
            } catch (\Throwable $error) {
                $inquiry->$column = 'failed';
                Log::warning('Inquiry notification failed', ['receipt' => $inquiry->receipt, 'notice' => $column, 'exception' => $error::class]);
            }
            $inquiry->save();
        }
        return response()->json(['receipt' => $inquiry->receipt, 'development' => in_array(config('mail.default'), ['log', 'array'], true), 'notification_pending' => in_array('failed', [$inquiry->admin_notice, $inquiry->customer_notice], true)], 201);
    }

    private function duplicate(Inquiry $inquiry, string $hash): JsonResponse
    {
        abort_unless(hash_equals($inquiry->payload_hash, $hash), 409, '送信済みの受付です。新しいお問い合わせはページを再読み込みしてください。');
        return response()->json(['receipt' => $inquiry->receipt, 'development' => in_array(config('mail.default'), ['log', 'array'], true), 'duplicate' => true, 'notification_pending' => in_array('failed', [$inquiry->admin_notice, $inquiry->customer_notice], true)]);
    }
}
