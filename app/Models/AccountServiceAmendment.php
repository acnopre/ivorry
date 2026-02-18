<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountServiceAmendment extends Model
{
    use SoftDeletes;

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
