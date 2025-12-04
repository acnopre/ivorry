<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountServiceAmendment extends Model
{
    protected $fillable = [
        'account_amendment_id',
        'service_id',
        'quantity',
        'default_quantity',
        'is_unlimited',
        'remarks',
    ];

    public function amendment()
    {
        return $this->belongsTo(AccountAmendment::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
