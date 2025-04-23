<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'tax_id',
    ];

    /**
     * Get the nichos for the restaurant.
     */
    public function nichos()
    {
        return $this->hasMany(Nicho::class);
    }
}
