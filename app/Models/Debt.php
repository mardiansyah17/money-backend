<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Debt extends Model
{
    protected $guarded = ['id'];

    public function schedules()
    {
        return $this->hasMany(DebtSchedule::class);
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
