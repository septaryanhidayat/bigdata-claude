<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChartOfAccount extends Model
{
    protected $fillable = [
        'school_id',
        'code',
        'name',
        'type',
        'current_balance',
    ];

    protected $casts = [
        'current_balance' => 'float',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
