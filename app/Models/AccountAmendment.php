<?php

namespace App\Models;

use App\Models\Hip;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountAmendment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id',
        'company_name',
        'policy_code',
        'hip_id',
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
        'approved_by',
        'old_company_name',
        'old_policy_code',
        'old_hip_id',
        'old_card_used',
        'old_effective_date',
        'old_expiration_date',
        'old_coverage_period_type',
        'old_mbl_type',
        'old_mbl_amount',
        'old_plan_type',
        'old_coverage_type',
        'coverage_type',
    ];

    public function hip()
    {
        return $this->belongsTo(Hip::class);
    }

    public function oldHip()
    {
        return $this->belongsTo(Hip::class, 'old_hip_id');
    }

    public function services()
    {
        return $this->hasMany(AccountServiceAmendment::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
