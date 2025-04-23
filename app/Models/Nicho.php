<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Nicho extends Model
{
    use HasFactory;

    protected $fillable = [
        'identifier',
        'restaurant_id',
        'customer_id',
        'additional_info',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'nicho_product')->withPivot('quantity');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // Método para obtener solo los productos con cantidad mayor a 0
    public function nonEmptyProducts()
    {
        return $this->belongsToMany(Product::class, 'nicho_product')
                    ->withPivot('quantity')
                    ->wherePivot('quantity', '>', 0);
    }
}
