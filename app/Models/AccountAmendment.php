<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountAmendment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id',
        'company_name',
        'policy_code',
        'hip',
        'card_used',
        'effective_date',
        'expiration_date',
        'endorsement_type',
        'endorsement_status',
        'coverage_period_type',
        'mbl_type',
        'mbl_amount',
        'remarks',
        'requested_by',
    ];

    public function services()
    {
        return $this->hasMany(AccountServiceAmendment::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
