<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    //

    protected $guarded = ['id'];

    protected $casts = [
        'balance' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    
}
