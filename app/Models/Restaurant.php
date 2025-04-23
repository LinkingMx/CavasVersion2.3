<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\User;

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

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'restaurant_user');
        // ->withTimestamps(); // Uncomment if you want timestamps
    }
}
