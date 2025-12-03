<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

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
        'account_status',
        'endorsement_status',
        'remarks'
    ];
    protected $casts = [
        'effective_date' => 'date',
        'expiration_date' => 'date',
    ];

    protected $with = ['services', 'renewals'];

    public function members()
    {
        return $this->hasMany(Member::class);
    }
    public function endorsementType()
    {
        return $this->belongsTo(EndorsementType::class);
    }

    public function renewals()
    {
        return $this->hasMany(AccountRenewal::class, 'account_id');
    }


    public function services()
    {
        return $this->belongsToMany(Service::class, 'account_service')
            ->using(AccountService::class) // use pivot model
            ->withPivot([
                'quantity',
                'default_quantity',
                'is_unlimited',
                'remarks',
                'created_at',
                'updated_at',
                'deleted_at',
            ])
            ->withTimestamps();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // log all attributes
            ->logOnlyDirty() // log only changed values
            ->useLogName('Account');
    }
}
