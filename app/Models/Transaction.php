<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'nicho_id',
        'type',
        'ticket_number',
        'ticket_photo_path',
        'notes',
        'transaction_date',
    ];

    public function nicho()
    {
        return $this->belongsTo(Nicho::class);
    }

    public function details()
    {
        return $this->hasMany(TransactionDetail::class);
    }
}
