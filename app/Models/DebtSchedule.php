<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DebtSchedule extends Model
{
    protected $guarded = ['id'];

    public function debt()
    {
        return $this->belongsTo(Debt::class);
    }
}
