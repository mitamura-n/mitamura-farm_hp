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
        return $this->unit_label.' × '.rtrim(rtrim(number_format($units, 3, '.', ''), '0'), '.').'口（計'.$quantity.'kg'.(in_array($this->name, ['白米', '分づき米']) ? '・精米前' : '').'）';
    }
}
