<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountService extends Pivot
{
    use SoftDeletes;
    protected $table = 'account_service';

    protected $fillable = [
        'account_id',
        'service_id',
        'default_quantity',
        'quantity',
        'is_unlimited',
        'remarks',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
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
