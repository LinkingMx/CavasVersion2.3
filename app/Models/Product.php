<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'external_sku',
    ];

    // Prepare for relationship with NichoProduct (pivot) and TransactionDetail

    /**
     * The nichos that belong to the product.
     */
    public function nichos()
    {
        return $this->belongsToMany(Nicho::class, NichoProduct::class)
            ->withPivot('quantity');
    }

    /**
     * Prepare for TransactionDetail relationship (to be implemented)
     */
    public function transactionDetails()
    {
        // return $this->hasMany(TransactionDetail::class);
    }
}
