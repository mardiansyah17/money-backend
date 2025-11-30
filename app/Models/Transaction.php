<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    //
    protected $guarded = ['id'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }


    public function counterparty()
    {
        return $this->belongsTo(Counterparty::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    
}
