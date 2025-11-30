<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountRenewalService extends Model
{
    protected $fillable = [
        'renewal_id',
        'service_id',
        'quantity',
        'is_unlimited',
        'remarks'
    ];

    public function renewal(): BelongsTo
    {
        return $this->belongsTo(AccountRenewal::class, 'renewal_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
