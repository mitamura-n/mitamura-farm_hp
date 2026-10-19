<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InquiryAdminController extends Controller
{
    public const STATUSES = ['received'=>'未対応', 'in_progress'=>'対応中', 'replied'=>'案内済み', 'closed'=>'対応完了'];

    public function index(Request $request)
    {
        abort_unless($request->user()?->is_admin, 403);
        $data=$request->validate(['status'=>['nullable',Rule::in(array_keys(self::STATUSES))], 'retention'=>['nullable',Rule::in(['due'])]]);
        $inquiries=Inquiry::query()->when($data['status'] ?? null, fn ($query,$status)=>$query->where('status',$status))
            ->when(($data['retention'] ?? null)==='due', fn ($query)=>$query->where('status','closed')->where('order_outcome','not_ordered')->whereNotNull('closed_at')->whereDate('closed_at','<=',now()->subYearNoOverflow()->toDateString()))
            ->latest('id')->paginate(20)->withQueryString();
        return response()->view('admin.inquiries', ['inquiries'=>$inquiries,'statuses'=>self::STATUSES])
            ->header('Cache-Control','no-store, private');
    }

    public function show(Request $request, Inquiry $inquiry)
    {
        abort_unless($request->user()?->is_admin, 403);
        return response()->view('admin.inquiry', ['inquiry'=>$inquiry,'statuses'=>self::STATUSES])
            ->header('Cache-Control','no-store, private');
    }

    public function update(Request $request, Inquiry $inquiry)
    {
        abort_unless($request->user()?->is_admin, 403);
        $data=$request->validate(['status'=>['required',Rule::in(array_keys(self::STATUSES))], 'version'=>['required','string'], 'order_outcome'=>['sometimes','required',Rule::in(array_keys(Inquiry::OUTCOMES))]]);
        $updated=Inquiry::whereKey($inquiry->id)->where('updated_at',$data['version'])->update([
            'status'=>$data['status'], 'order_outcome'=>$data['order_outcome'] ?? $inquiry->order_outcome,
            'closed_at'=>$data['status']==='closed' ? ($inquiry->closed_at ?? now()) : null,
            'updated_at'=>now()->max($inquiry->updated_at->addSecond()),
        ]);
        if (! $updated) return back()->withErrors(['version'=>'別の画面で更新されています。再読み込みして確認してください。']);
        return redirect()->route('admin.inquiries.show',$inquiry)->with('status','対応状況を更新しました。メール送信は行っていません。');
    }
}
