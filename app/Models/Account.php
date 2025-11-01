<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
        'amendment_status',
        'renewal_status'
    ];
    protected $casts = [
        'effective_date' => 'date',
        'expiration_date' => 'date',
    ];

    protected $with = ['services'];

    public function members()
    {
        return $this->hasMany(Member::class);
    }
    public function endorsementType()
    {
        return $this->belongsTo(EndorsementType::class);
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'account_service')
            ->using(AccountService::class) // use pivot model
            ->withPivot(['quantity', 'is_unlimited', 'remarks', 'default_quantity'])
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
