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
        'plan_type',
        'coverage_period_type',
        'mbl_type',
        'mbl_amount',
        'mbl_balance',
        'remarks',
        'created_by'
    ];
    protected $casts = [
        'effective_date' => 'date',
        'expiration_date' => 'date',
    ];

    protected $with = ['services', 'renewals'];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members()
    {
        return $this->hasMany(Member::class, 'account_id');
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
            ->withTimestamps()->whereNull('account_service.deleted_at');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // log all attributes
            ->logOnlyDirty() // log only changed values
            ->useLogName('Account');
    }

    protected static function booted()
    {
        static::retrieved(function ($account) {
            $account->autoExpire();
        });

        static::saving(function ($account) {
            $account->autoExpire();
        });
    }

    public function autoExpire()
    {
        if ($this->expiration_date && now()->greaterThan($this->expiration_date)) {

            if ($this->account_status !== 'expired') {
                $this->account_status = 'expired';

                // Prevent recursion (only save if not already saving)
                if ($this->isDirty('account_status')) {
                    $this->saveQuietly();
                }
            }
        }
    }

    public function procedures()
    {
        // Get procedures through members
        return $this->hasManyThrough(
            Procedure::class,
            Member::class,
            'account_id', // Foreign key on members table
            'member_id',  // Foreign key on procedures table
            'id',         // Local key on account table
            'id'          // Local key on member table
        )->with(['service', 'clinic', 'units']); // eager-load related data
    }
}
