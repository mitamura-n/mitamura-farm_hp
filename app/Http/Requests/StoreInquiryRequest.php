<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreInquiryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'submission_key' => ['required', 'uuid'],
            'kind' => ['required', Rule::in(['商品についての相談', '注文の相談', 'その他のお問い合わせ'])],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'items' => ['present', 'array', 'max:10'],
            'items.*' => ['array:product,quantity,milling'],
            'items.*.product' => ['required', Rule::in(['玄米', '白米', '分づき米', '米粉', '米ぬか'])],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999000'],
            'items.*.milling' => ['nullable', Rule::in(['未定・指定なし', '1分', '3分', '5分', '8分'])],
            'region' => ['nullable', 'string', 'max:12', Rule::in(config('farm.prefectures'))],
            'message' => ['nullable', 'string', 'max:2000'],
            'privacy' => ['required', 'accepted'],
            'website' => ['nullable', 'string', 'max:0'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) return;
            $items = $this->input('items', []);
            if ($this->input('kind') === '注文の相談') {
                if (count($items) === 0) $validator->errors()->add('items', 'ご希望の商品と数量を入力してください。');
                if (! $this->filled('region')) $validator->errors()->add('region', '送料のご案内のため、都道府県を選択してください。');
            }
            if (count($items) === 0 && ! $this->filled('message')) $validator->errors()->add('message', '商品を指定しない場合は、ご相談内容を入力してください。');
            foreach ($items as $index => $item) {
                $product = \App\Models\Product::where('name', $item['product'])->where('active', true)->first();
                if (! $product) {
                    $validator->errors()->add("items.$index.product", 'この商品は現在受付を停止しています。');
                    continue;
                }
                $total = collect($items)->where('product', $product->name)->sum('quantity');
                if ((int) $item['quantity'] % $product->unit_kg !== 0 || $total > $product->unit_kg * ($product->max_units ?? 999)) {
                    $validator->errors()->add("items.$index.quantity", $product->name.'は'.$product->unit_label.'単位で指定してください。'.($product->max_units ? '上限は'.$product->max_units.'口です。' : ''));
                }
                if ($item['product'] !== '分づき米' && ! empty($item['milling'])) $validator->errors()->add("items.$index.milling", '精米度合いは分づき米の場合だけ指定できます。');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'required' => ':attributeを入力してください。', 'present' => ':attributeを確認してください。',
            'email' => '返信先メールアドレスの形式を確認してください。',
            'max' => ':attributeの入力が長すぎるか、上限を超えています。',
            'min' => ':attributeは1以上で入力してください。',
            'integer' => ':attributeは整数で入力してください。',
            'numeric' => ':attributeは数値で入力してください。',
            'multiple_of' => ':attributeは小数第1位までで入力してください。',
            'in' => ':attributeを選択肢から選んでください。',
            'accepted' => '個人情報の取り扱いをご確認のうえ、チェックしてください。',
            'uuid' => 'ページを再読み込みしてからお試しください。',
            'array' => ':attributeの形式を確認してください。',
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'お名前', 'email' => '返信先メールアドレス', 'kind' => 'ご用件', 'items' => '商品欄', 'items.*.product' => '商品', 'items.*.quantity' => '数量', 'items.*.milling' => '精米度合い', 'region' => '都道府県', 'message' => 'ご相談内容', 'privacy' => '個人情報の取り扱い', 'website' => '入力内容'];
    }
}
