<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'email',
        'birthday',
        'additional_info',
    ];

    /**
     * Get the nichos for the customer.
     */
    public function nichos(): HasMany
    {
        return $this->hasMany(Nicho::class);
    }
}
