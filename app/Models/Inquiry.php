<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inquiry extends Model
{
    public const OUTCOMES = ['unknown'=>'未確認', 'not_ordered'=>'注文に至らなかった', 'ordered'=>'注文になった'];

    public function retentionReviewAt(): ?\Illuminate\Support\Carbon
    {
        return $this->status === 'closed' && $this->order_outcome === 'not_ordered' && $this->closed_at
            ? $this->closed_at->copy()->addYearNoOverflow() : null;
    }
    protected $guarded = ['id'];

    protected $hidden = ['name', 'email', 'items', 'region', 'message', 'submission_key', 'payload_hash'];

    protected function casts(): array
    {
        return ['name' => 'encrypted', 'email' => 'encrypted', 'items' => 'encrypted:array', 'region' => 'encrypted', 'message' => 'encrypted', 'consented_at' => 'datetime', 'closed_at'=>'datetime'];
    }
}
