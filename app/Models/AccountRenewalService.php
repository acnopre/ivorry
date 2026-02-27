<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountRenewalService extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'renewal_id',
        'service_id',
        'default_quantity',
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
