<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class NichoProduct extends Pivot
{
    protected $table = 'nicho_product';

    protected $fillable = [
        'nicho_id',
        'product_id',
        'quantity',
    ];

    public function nicho()
    {
        return $this->belongsTo(Nicho::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
