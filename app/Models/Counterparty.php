<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Counterparty extends Model
{
    protected $guarded = ['id'];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function debts()
    {
        return $this->hasMany(Debt::class);
    }


    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }


}
