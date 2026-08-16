<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    protected $fillable = [
        'school_id',
        'account_id',     // matches migration: account_id FK to chart_of_accounts
        'reference_number', // matches migration: reference_number
        'description',
        'debit',
        'credit',
        'date',           // matches migration: date
    ];

    protected $casts = [
        'debit'  => 'float',
        'credit' => 'float',
        'date'   => 'date',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function coa()
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }
}
