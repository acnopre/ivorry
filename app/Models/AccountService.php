<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class AccountService extends Pivot
{
    protected $table = 'account_service';

    protected $fillable = [
        'account_id',
        'service_id',
        'quantity',
        'is_unlimited',
        'remarks',
    ];

    /**
     * Relationships
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
