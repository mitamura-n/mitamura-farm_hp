<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = ['id', 'name', 'milling'];

    protected function casts(): array
    {
        return ['unit_kg'=>'integer', 'max_units'=>'integer', 'price_yen'=>'integer', 'active'=>'boolean', 'milling'=>'boolean'];
    }

    public function quantityText(int|float|string $quantity): string
    {
        $units = (float) $quantity / $this->unit_kg;
        if ($this->name === '玄米') return $quantity.'kg';
        if (in_array($this->name, ['白米', '分づき米'])) return '玄米'.$quantity.'kg分';
        $count=rtrim(rtrim(number_format($units, 3, '.', ''), '0'), '.');
        return $count.'袋（'.($units === 1.0 ? $this->unit_kg.'kg入り' : '計'.$quantity.'kg').'）';
    }
}
