<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inquiry extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['name', 'email', 'items', 'region', 'message', 'submission_key', 'payload_hash'];

    protected function casts(): array
    {
        return ['name' => 'encrypted', 'email' => 'encrypted', 'items' => 'encrypted:array', 'region' => 'encrypted', 'message' => 'encrypted', 'consented_at' => 'datetime'];
    }
}
