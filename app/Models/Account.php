<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = [
        'company_name',
        'policy_code',
        'hip',
        'card_used',
        'effective_date',
        'expiration_date',
        'endorsement_type',
        'status'
    ];

    public function members()
    {
        return $this->hasMany(Member::class);
    }
    public function endorsementType()
    {
        return $this->belongsTo(EndorsementType::class);
    }

    public function basicDentalServices()
    {
        return $this->belongsToMany(\App\Models\BasicDentalService::class, 'account_basic_dental_service');
    }

    public function planEnhancements()
    {
        return $this->belongsToMany(\App\Models\PlanEnhancement::class, 'account_plan_enhancement');
    }
}
