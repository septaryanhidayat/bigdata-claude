<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavingsTransaction extends Model
{
    protected $fillable = [
        'student_id',
        'type',         // matches migration enum: DEPOSIT, WITHDRAWAL, TRANSFER_SPP
        'amount',
        'balance_after',
        'description',  // matches migration column (was 'notes' in controller – now fixed)
        'teller_id',
    ];

    protected $casts = [
        'amount'        => 'float',
        'balance_after' => 'float',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function teller()
    {
        return $this->belongsTo(Employee::class, 'teller_id');
    }
}
