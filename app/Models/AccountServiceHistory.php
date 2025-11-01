<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountServiceHistory extends Model
{
    protected $fillable = [
        'account_id',
        'service_id',
        'quantity',
        'remarks',
        'action',
        'effective_date',
        'expiry_date',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
