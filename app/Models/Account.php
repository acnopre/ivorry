<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\BasicDentalService;
use App\Models\PlanEnhancement;

class Account extends Model
{

    use LogsActivity;

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

    protected $with = ['basicDentalServices', 'planEnhancements'];

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
        return $this->belongsToMany(
            BasicDentalService::class,
            'account_basic_dental_service',
            'account_id',
            'basic_dental_service_id'
        )->withPivot('quantity')->withTimestamps();
    }
    
    public function planEnhancements()
    {
        return $this->belongsToMany(
            PlanEnhancement::class,
            'account_plan_enhancement',
            'account_id',
            'plan_enhancement_id'
        )->withPivot('quantity')->withTimestamps();
    }
    
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // log all attributes
            ->logOnlyDirty() // log only changed values
            ->useLogName('Account');
    }
}
